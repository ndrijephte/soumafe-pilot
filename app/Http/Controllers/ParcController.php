<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, AutreRessource, Caisse, Chauffeur, Depense, Emprunt, JourFerie, LigneInterventionPanne, MaintenancePreventive, MouvementCaisse, Panne, PaiementPenalite, ParametreParc, Penalite, PretAccorde, Recouvrement, RecetteVehicule, RemboursementEmprunt, RemboursementPret, TypeParc, User, Vehicule, VersementRecouvrement};
use App\Services\AlerteParcService;
use App\Services\ExerciceParc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

class ParcController extends Controller
{
    /* ─── Accès ─── */
    private function autoriser(Request $r): void
    {
        abort_unless($r->user()->peutGererParc(), 403);
    }

    /* ─── Détection type de jour ─── */
    private function detecterTypeJour(string $dateStr): string
    {
        $date = \Carbon\Carbon::parse($dateStr);
        if ($date->isSunday()) return 'dimanche';
        if (Schema::hasTable('jours_feries') && JourFerie::whereDate('date', $date)->exists()) return 'ferie';
        return 'ouvrable';
    }

    /* ===== INDEX (onglets) ===== */
    public function index(Request $r)
    {
        $this->autoriser($r);
        AlerteParcService::verifier();
        $vehicules = Vehicule::with('chauffeurs')->orderBy('immatriculation')->get();
        $chauffeurs = Schema::hasTable('chauffeurs')
            ? Chauffeur::with('vehicules')->orderBy('nom')->get()
            : collect();
        $collaborateurs = User::where('actif', true)->orderBy('name')->get();

        // ── Exercice affiché : toutes les listes, totaux et indicateurs sont bornés à cet exercice ──
        $exercicesParc = ExerciceParc::liste(18);
        $exerciceCourant = ExerciceParc::cle();
        $exercice = $r->get('exercice', $exerciceCourant);
        if ($exercice !== 'tous' && !isset($exercicesParc[$exercice])) $exercice = $exerciceCourant;
        [$exDebut, $exFin] = $exercice === 'tous' ? [null, null] : ExerciceParc::bornes($exercice);
        $borne = fn($q, string $col) => $exDebut ? $q->whereBetween($col, [$exDebut, $exFin]) : $q;

        $pannes = $borne(Panne::with(['vehicule', 'emprunt', 'lignes']), 'date_declaration')->orderByDesc('date_declaration')->get();
        $recettes = $borne(RecetteVehicule::with('vehicule'), 'date')->orderByDesc('date')->limit(2000)->get();
        // Maintenance : réalisée → date de réalisation ; planifiée → date d'échéance
        $maintenances = MaintenancePreventive::with('vehicule')
            ->when($exDebut, fn($q) => $q->where(fn($w) => $w
                ->where(fn($a) => $a->where('statut', 'realisee')->whereBetween('date_realisation', [$exDebut, $exFin]))
                ->orWhere(fn($b) => $b->where(fn($s) => $s->whereNull('statut')->orWhere('statut', '!=', 'realisee'))->whereBetween('prochaine_echeance', [$exDebut, $exFin]))))
            ->orderBy('prochaine_echeance')->get();
        $penalites = $borne(Penalite::with(['vehicule', 'paiements']), 'date_infraction')->orderByDesc('date_infraction')->get();

        $kpi = $this->calculerKpi($vehicules, $exDebut, $exFin);
        $typesPenalite = TypeParc::penalites();
        $typesMaintenance = TypeParc::maintenances();
        $motifsAbsence = TypeParc::motifsAbsence();

        // --- Grille journalière recettes (par défaut = hier, la recette versée est toujours celle de la veille) ---
        $dateJour = $r->get('date_recette', \Carbon\Carbon::yesterday()->format('Y-m-d'));
        $grilleJour = $this->construireGrilleJour($vehicules, $dateJour, $collaborateurs);

        // --- Alertes jours manquants (7 derniers jours) ---
        $alertesManquants = $this->detecterJoursManquants($vehicules);

        // --- Recouvrements ---
        $recouvrements = Schema::hasTable('recouvrements')
            ? $borne(Recouvrement::with(['vehicule', 'versements']), 'date_recette')->orderByDesc('date_recette')->get()
            : collect();

        // --- Dépenses (hors maintenance = dépense véhicule, pas "autre dépense") ---
        $depenses = Schema::hasTable('depenses')
            ? $borne(Depense::with('vehicule')->where('type_depense', '!=', 'maintenance'), 'date_depense')->orderByDesc('date_depense')->get()
            : collect();
        $typesDepense = TypeParc::depenses();

        // --- Emprunts ---
        $emprunts = Schema::hasTable('emprunts')
            ? $borne(Emprunt::with(['vehicule', 'remboursements']), 'date_emprunt')->orderByDesc('date_emprunt')->get()
            : collect();

        // --- Prêts accordés ---
        $pretsAccordes = Schema::hasTable('prets_accordes')
            ? $borne(PretAccorde::with(['chauffeur', 'remboursements']), 'date_pret')->orderByDesc('date_pret')->get()
            : collect();

        // --- Autres ressources ---
        $autresRessources = Schema::hasTable('autres_ressources')
            ? $borne(AutreRessource::with('vehicule'), 'date_ressource')->orderByDesc('date_ressource')->get()
            : collect();
        $typesRessource = TypeParc::autresRessources();

        // --- Caisses ---
        $caisses = Schema::hasTable('caisses') ? Caisse::where('actif', true)->get() : collect();

        $reports = $exDebut ? $this->reportsAnterieurs($exDebut) : [];

        return view('parc.index', compact(
            'vehicules', 'chauffeurs', 'collaborateurs', 'pannes', 'recettes',
            'maintenances', 'penalites', 'kpi', 'typesPenalite', 'typesMaintenance',
            'motifsAbsence', 'dateJour', 'grilleJour', 'alertesManquants', 'recouvrements',
            'depenses', 'typesDepense', 'emprunts',
            'pretsAccordes', 'autresRessources', 'typesRessource', 'caisses',
            'exercicesParc', 'exerciceCourant', 'exercice', 'reports'
        ));
    }

    /* ─── Construire la grille du jour (chauffeur-centric) ─── */
    private function construireGrilleJour($vehicules, string $dateStr, $collaborateurs): array
    {
        $date = \Carbon\Carbon::parse($dateStr);
        $typeJour = $this->detecterTypeJour($dateStr);
        $estDimanche = $date->isSunday();

        $existantsAll = RecetteVehicule::with('vehicule')->whereDate('date', $dateStr)->get();
        // Indexer par clé composite vehicule_id + chauffeur pour recettes individuelles
        $existantsParChauffeur = $existantsAll->keyBy(fn($r) => $r->vehicule_id . '_' . ($r->chauffeur ?? ''));
        // Garder aussi l'index par vehicule_id (pour véhicules sans chauffeur nommé)
        $existantsParVehicule = $existantsAll->keyBy('vehicule_id');

        // ── Alternance : déduire repos si chauffeur a travaillé la veille (véhicules roulent 7j/7) ──
        $veilleOuvree = $date->copy()->subDay();
        $recettesVeille = RecetteVehicule::whereDate('date', $veilleOuvree->toDateString())
            ->where('recette', '>', 0)->get();
        // Map vehicle_id → chauffeur qui a travaillé la veille
        $chauffeurVeille = [];
        foreach ($recettesVeille as $rv) {
            if ($rv->chauffeur) {
                $chauffeurVeille[$rv->vehicule_id] = $rv->chauffeur;
            }
        }

        // 1. Chauffeurs actifs avec véhicule en service
        $chauffeursActifs = Schema::hasTable('chauffeurs')
            ? Chauffeur::where('statut', 'actif')
                ->with(['vehicules' => fn($q) => $q->wherePivot('actif', true)->where('statut', 'en_service')])
                ->orderBy('nom')->get()
            : collect();

        // 1b. Chauffeurs inactifs ayant une recette déjà saisie ce jour
        $chauffeursInactifs = collect();
        if (Schema::hasTable('chauffeurs') && $existantsAll->isNotEmpty()) {
            $nomsExistants = $existantsAll->pluck('chauffeur')->filter()->unique()->toArray();
            $chauffeursInactifs = Chauffeur::where('statut', '!=', 'actif')
                ->with(['vehicules' => fn($q) => $q->wherePivot('actif', true)])
                ->orderBy('nom')->get()
                ->filter(fn($ch) => in_array($ch->nomComplet(), $nomsExistants));
        }

        $grille = [];
        $vehiculesTraites = [];

        foreach ($chauffeursActifs as $ch) {
            $veh = $ch->vehicules->sortByDesc(fn($v) => $v->pivot->role === 'principal' ? 1 : 0)->first();
            if (!$veh) continue;

            $vehiculesTraites[] = $veh->id . '_' . $ch->id;
            $estRepos = $estDimanche && $veh->categorie === 'vtc';
            $objectif = ParametreParc::objectifRecette($veh->categorie, $typeJour);
            if ($objectif <= 0) $objectif = $veh->objectif_recette ?? 0;

            // Alternance : ce chauffeur a travaillé la veille → repos aujourd'hui
            $reposAlternance = false;
            $nbChauffeurs = $veh->chauffeurs()->wherePivot('actif', true)->count();
            if (!$estRepos && $nbChauffeurs >= 2 && isset($chauffeurVeille[$veh->id])) {
                if ($chauffeurVeille[$veh->id] === $ch->nomComplet()) {
                    $reposAlternance = true;
                    $estRepos = true;
                }
            }

            // Chercher recette individuelle de CE chauffeur pour CE véhicule
            $cle = $veh->id . '_' . $ch->nomComplet();
            $dejaSaisi = $existantsParChauffeur->has($cle);
            $recetteExistante = $dejaSaisi ? $existantsParChauffeur->get($cle) : null;
            // Vérifier si un AUTRE chauffeur a déjà une recette pour ce véhicule ce jour
            $autreRecette = $existantsAll->first(fn($r) => $r->vehicule_id == $veh->id && $r->chauffeur !== $ch->nomComplet());

            $grille[] = [
                'chauffeur_obj' => $ch,
                'chauffeur' => $ch->nomComplet(),
                'chauffeur_actif' => true,
                'vehicule' => $veh,
                'objectif' => $estRepos ? 0 : $objectif,
                'est_repos' => $estRepos,
                'repos_alternance' => $reposAlternance,
                'nb_chauffeurs_vehicule' => $nbChauffeurs,
                'est_dimanche_taxi' => $estDimanche && $veh->categorie === 'taxi',
                'type_jour' => $typeJour,
                'deja_saisi' => $dejaSaisi,
                'recette' => $recetteExistante,
                'vehicule_pris_par' => $autreRecette ? $autreRecette->chauffeur : null,
            ];
        }

        // 1c. Chauffeurs inactifs avec recette existante
        foreach ($chauffeursInactifs as $ch) {
            $veh = $ch->vehicules->first();
            if (!$veh) continue;
            $cleTraite = $veh->id . '_' . $ch->id;
            if (in_array($cleTraite, $vehiculesTraites)) continue;

            $vehiculesTraites[] = $cleTraite;
            $estRepos = $estDimanche && $veh->categorie === 'vtc';
            $objectif = ParametreParc::objectifRecette($veh->categorie, $typeJour);
            if ($objectif <= 0) $objectif = $veh->objectif_recette ?? 0;

            $cle = $veh->id . '_' . $ch->nomComplet();
            $dejaSaisi = $existantsParChauffeur->has($cle);
            $recetteExistante = $dejaSaisi ? $existantsParChauffeur->get($cle) : null;
            $autreRecette = $existantsAll->first(fn($r) => $r->vehicule_id == $veh->id && $r->chauffeur !== $ch->nomComplet());

            $grille[] = [
                'chauffeur_obj' => $ch,
                'chauffeur' => $ch->nomComplet(),
                'chauffeur_actif' => false,
                'vehicule' => $veh,
                'objectif' => $estRepos ? 0 : $objectif,
                'est_repos' => $estRepos,
                'repos_alternance' => false,
                'nb_chauffeurs_vehicule' => 0,
                'est_dimanche_taxi' => $estDimanche && $veh->categorie === 'taxi',
                'type_jour' => $typeJour,
                'deja_saisi' => $dejaSaisi,
                'recette' => $recetteExistante,
                'vehicule_pris_par' => $autreRecette ? $autreRecette->chauffeur : null,
            ];
        }

        // 2. Véhicules en service sans chauffeur affecté
        $vehIdsTraites = collect($vehiculesTraites)->map(fn($s) => (int) explode('_', $s)[0])->unique()->toArray();
        $actifsSansChauffeur = $vehicules->where('statut', 'en_service')
            ->reject(fn($v) => in_array($v->id, $vehIdsTraites));

        foreach ($actifsSansChauffeur as $v) {
            $estRepos = $estDimanche && $v->categorie === 'vtc';
            $objectif = ParametreParc::objectifRecette($v->categorie, $typeJour);
            if ($objectif <= 0) $objectif = $v->objectif_recette ?? 0;
            $dejaSaisi = $existantsParVehicule->has($v->id);
            $recetteExistante = $dejaSaisi ? $existantsParVehicule->get($v->id) : null;

            $grille[] = [
                'chauffeur_obj' => null,
                'chauffeur' => '',
                'chauffeur_actif' => true,
                'vehicule' => $v,
                'objectif' => $estRepos ? 0 : $objectif,
                'est_repos' => $estRepos,
                'repos_alternance' => false,
                'nb_chauffeurs_vehicule' => 0,
                'est_dimanche_taxi' => $estDimanche && $v->categorie === 'taxi',
                'type_jour' => $typeJour,
                'deja_saisi' => $dejaSaisi,
                'recette' => $recetteExistante,
                'vehicule_pris_par' => null,
            ];
        }

        return $grille;
    }

    /* ─── Détecter jours manquants (7 derniers jours) ─── */
    private function detecterJoursManquants($vehicules): array
    {
        $actifs = $vehicules->where('statut', 'en_service');
        if ($actifs->isEmpty()) return [];

        $alertes = [];
        $aujourdhui = \Carbon\Carbon::today();

        for ($i = 1; $i <= 7; $i++) {
            $jour = $aujourdhui->copy()->subDays($i);
            $dateStr = $jour->format('Y-m-d');
            $estDimanche = $jour->isSunday();

            // Véhicules qui doivent verser ce jour
            $doiventVerser = $actifs->filter(function ($v) use ($estDimanche) {
                // Dimanche VTC = repos, pas besoin de recette
                if ($estDimanche && $v->categorie === 'vtc') return false;
                return true;
            });

            if ($doiventVerser->isEmpty()) continue;

            $idsDoivent = $doiventVerser->pluck('id')->toArray();
            $saisis = RecetteVehicule::whereDate('date', $dateStr)
                ->whereIn('vehicule_id', $idsDoivent)
                ->pluck('vehicule_id')->toArray();

            $manquants = array_diff($idsDoivent, $saisis);
            if (!empty($manquants)) {
                $noms = $doiventVerser->whereIn('id', $manquants)->pluck('immatriculation')->toArray();
                $alertes[] = [
                    'date' => $dateStr,
                    'date_label' => $jour->translatedFormat('l d/m'),
                    'count' => count($manquants),
                    'vehicules' => implode(', ', $noms),
                ];
            }
        }
        return $alertes;
    }

    /* ===== VÉHICULES CRUD ===== */
    public function storeVehicule(Request $r)
    {
        $this->autoriser($r);
        $r->validate([
            'immatriculation' => 'required|string|max:20|unique:vehicules,immatriculation',
            'categorie' => 'required|in:taxi,vtc',
            'marque' => 'nullable|string|max:50',
            'modele' => 'nullable|string|max:50',
            'numero_chassis' => 'nullable|string|max:30',
            'nombre_places' => 'nullable|integer|min:1|max:50',
            'proprietaire' => 'nullable|string|max:100',
            'date_mise_en_service' => 'nullable|date',
            'chauffeur_affecte' => 'nullable|string|max:100',
            'kilometrage' => 'nullable|integer|min:0',
            'statut' => 'required|in:en_service,immobilise,reforme',
            'objectif_recette' => 'nullable|integer|min:0',
            'observations' => 'nullable|string|max:500',
        ]);

        $vehicule = Vehicule::create(array_merge(
            $r->only(['immatriculation', 'categorie', 'marque', 'modele', 'numero_chassis',
                'nombre_places', 'proprietaire', 'date_mise_en_service',
                'chauffeur_affecte', 'kilometrage', 'statut', 'observations']),
            ['objectif_recette' => $r->objectif_recette ?? 0]
        ));

        $this->syncChauffeurs($vehicule, $r);

        AuditLog::log('creation', 'vehicule', $vehicule->immatriculation);
        return redirect()->route('parc.index', ['tab' => 'vehicules'])->with('succes', 'Véhicule ajouté.');
    }

    public function updateVehicule(Request $r, Vehicule $vehicule)
    {
        $this->autoriser($r);
        $r->validate([
            'immatriculation' => 'required|string|max:20|unique:vehicules,immatriculation,' . $vehicule->id,
            'categorie' => 'required|in:taxi,vtc',
            'marque' => 'nullable|string|max:50',
            'modele' => 'nullable|string|max:50',
            'numero_chassis' => 'nullable|string|max:30',
            'nombre_places' => 'nullable|integer|min:1|max:50',
            'proprietaire' => 'nullable|string|max:100',
            'date_mise_en_service' => 'nullable|date',
            'chauffeur_affecte' => 'nullable|string|max:100',
            'kilometrage' => 'nullable|integer|min:0',
            'statut' => 'required|in:en_service,immobilise,reforme',
            'objectif_recette' => 'nullable|integer|min:0',
            'observations' => 'nullable|string|max:500',
        ]);

        $vehicule->update(array_merge(
            $r->only(['immatriculation', 'categorie', 'marque', 'modele', 'numero_chassis',
                'nombre_places', 'proprietaire', 'date_mise_en_service',
                'chauffeur_affecte', 'kilometrage', 'statut', 'observations']),
            ['objectif_recette' => $r->objectif_recette ?? 0]
        ));

        $this->syncChauffeurs($vehicule, $r);

        AuditLog::log('modification', 'vehicule', $vehicule->immatriculation);
        return redirect()->route('parc.index', ['tab' => 'vehicules'])->with('succes', 'Véhicule mis à jour.');
    }

    public function destroyVehicule(Request $r, Vehicule $vehicule)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'vehicule', $vehicule->immatriculation, "Motif: " . $r->motif_suppression);
        $vehicule->delete();
        return redirect()->route('parc.index', ['tab' => 'vehicules'])->with('succes', 'Véhicule supprimé.');
    }

    /* ─── Sync chauffeurs via pivot ─── */
    private function syncChauffeurs(Vehicule $vehicule, Request $r): void
    {
        $ids = $r->input('chauffeurs_ids', []);
        $roles = $r->input('chauffeurs_roles', []);
        if (empty($ids)) {
            $vehicule->chauffeurs()->detach();
            return;
        }
        $sync = [];
        foreach ($ids as $i => $chauffeurId) {
            if ($chauffeurId) {
                $sync[(int) $chauffeurId] = ['role' => $roles[$i] ?? 'principal', 'actif' => true];
            }
        }
        $vehicule->chauffeurs()->sync($sync);
    }

    /* ===== PANNES CRUD ===== */
    public function storePanne(Request $r)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'date_declaration' => 'nullable|date',
            'description' => 'required|string|max:500',
            'urgence' => 'required|in:faible,moyenne,haute,critique',
            'diagnostic' => 'nullable|string|max:500',
            'decision' => 'nullable|string|max:60',
            'date_debut_intervention' => 'nullable|date',
            'date_cloture' => 'nullable|date',
            'cout_reel' => 'nullable|integer|min:0',
            'statut' => 'required|in:declaree,en_diagnostic,en_intervention,cloturee',
        ]);

        if (!empty($data['date_declaration']) && !empty($data['date_cloture'])) {
            $data['delai_resolution'] = \Carbon\Carbon::parse($data['date_declaration'])->diffInDays(\Carbon\Carbon::parse($data['date_cloture']));
        }

        $p = Panne::create($data);

        // Emprunt si source_financement = emprunt et cout_reel > 0
        if ($r->source_financement === 'emprunt' && !empty($data['cout_reel']) && $data['cout_reel'] > 0) {
            $emp = Emprunt::create([
                'montant' => $data['cout_reel'],
                'date_emprunt' => $data['date_cloture'] ?? now()->toDateString(),
                'preteur' => $r->preteur ?? 'Non précisé',
                'motif' => 'Réparation panne — ' . ($p->vehicule?->immatriculation ?? ''),
                'source_type' => 'panne',
                'source_id' => $p->id,
                'vehicule_id' => $data['vehicule_id'],
                'cree_par' => auth()->user()->name,
            ]);
            $p->update(['emprunt_id' => $emp->id]);
        }

        AuditLog::log('creation', 'panne', $p->vehicule?->immatriculation);
        return redirect()->route('parc.index', ['tab' => 'pannes'])->with('succes', 'Panne enregistrée.');
    }

    public function updatePanne(Request $r, Panne $panne)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'date_declaration' => 'nullable|date',
            'description' => 'required|string|max:500',
            'urgence' => 'required|in:faible,moyenne,haute,critique',
            'diagnostic' => 'nullable|string|max:500',
            'decision' => 'nullable|string|max:60',
            'date_debut_intervention' => 'nullable|date',
            'date_cloture' => 'nullable|date',
            'cout_reel' => 'nullable|integer|min:0',
            'statut' => 'required|in:declaree,en_diagnostic,en_intervention,cloturee',
        ]);

        if (!empty($data['date_declaration']) && !empty($data['date_cloture'])) {
            $data['delai_resolution'] = \Carbon\Carbon::parse($data['date_declaration'])->diffInDays(\Carbon\Carbon::parse($data['date_cloture']));
        }

        $panne->update($data);

        // Gérer emprunt sur modification
        if ($r->source_financement === 'emprunt' && !empty($data['cout_reel']) && $data['cout_reel'] > 0 && !$panne->emprunt_id) {
            $emp = Emprunt::create([
                'montant' => $data['cout_reel'],
                'date_emprunt' => $data['date_cloture'] ?? now()->toDateString(),
                'preteur' => $r->preteur ?? 'Non précisé',
                'motif' => 'Réparation panne — ' . ($panne->vehicule?->immatriculation ?? ''),
                'source_type' => 'panne',
                'source_id' => $panne->id,
                'vehicule_id' => $data['vehicule_id'],
                'cree_par' => auth()->user()->name,
            ]);
            $panne->update(['emprunt_id' => $emp->id]);
        }

        AuditLog::log('modification', 'panne', $panne->vehicule?->immatriculation);
        return redirect()->route('parc.index', ['tab' => 'pannes'])->with('succes', 'Panne mise à jour.');
    }

    public function destroyPanne(Request $r, Panne $panne)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'panne', $panne->vehicule?->immatriculation, "Motif: " . $r->motif_suppression);
        $panne->delete();
        return redirect()->route('parc.index', ['tab' => 'pannes'])->with('succes', 'Panne supprimée.');
    }

    /* ===== LIGNES INTERVENTION PANNE ===== */
    public function storeLigneIntervention(Request $r, Panne $panne)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'designation'   => 'required|string|max:200',
            'quantite'      => 'required|integer|min:1',
            'prix_unitaire' => 'required|integer|min:0',
        ]);
        $data['montant'] = $data['quantite'] * $data['prix_unitaire'];
        $data['panne_id'] = $panne->id;

        $ligne = LigneInterventionPanne::create($data);
        $panne->recalculerCout();
        AuditLog::log('creation', 'ligne_intervention', "Panne #{$panne->id}", "Ligne: {$data['designation']}, Montant: {$data['montant']} F");

        if ($r->ajax()) {
            return response()->json([
                'success' => true,
                'ligne' => ['id' => $ligne->id, 'designation' => $ligne->designation, 'quantite' => $ligne->quantite, 'prix_unitaire' => $ligne->prix_unitaire, 'montant' => $ligne->montant],
                'cout_reel' => $panne->fresh()->cout_reel,
            ]);
        }
        return redirect()->route('parc.index', ['tab' => 'pannes'])->with('succes', 'Ligne ajoutee.');
    }

    public function updateLigneIntervention(Request $r, LigneInterventionPanne $ligne)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'designation'   => 'required|string|max:200',
            'quantite'      => 'required|integer|min:1',
            'prix_unitaire' => 'required|integer|min:0',
        ]);
        $data['montant'] = $data['quantite'] * $data['prix_unitaire'];

        $ligne->update($data);
        $ligne->panne->recalculerCout();
        AuditLog::log('modification', 'ligne_intervention', "Panne #{$ligne->panne_id}", "Ligne: {$data['designation']}");

        if ($r->ajax()) {
            return response()->json([
                'success' => true,
                'ligne' => ['id' => $ligne->id, 'designation' => $ligne->designation, 'quantite' => $ligne->quantite, 'prix_unitaire' => $ligne->prix_unitaire, 'montant' => $ligne->montant],
                'cout_reel' => $ligne->panne->fresh()->cout_reel,
            ]);
        }
        return redirect()->route('parc.index', ['tab' => 'pannes'])->with('succes', 'Ligne mise a jour.');
    }

    public function destroyLigneIntervention(Request $r, LigneInterventionPanne $ligne)
    {
        $this->autoriser($r);
        $panneId = $ligne->panne_id;
        $panne = $ligne->panne;
        $desig = $ligne->designation;
        AuditLog::log('suppression', 'ligne_intervention', "Panne #{$panneId}", "Ligne: {$desig}");
        $ligne->delete();
        $panne->recalculerCout();

        if ($r->ajax()) {
            return response()->json([
                'success' => true,
                'cout_reel' => $panne->fresh()->cout_reel,
            ]);
        }
        return redirect()->route('parc.index', ['tab' => 'pannes'])->with('succes', 'Ligne supprimee.');
    }

    /* ===== RECETTES CRUD ===== */
    public function storeRecette(Request $r)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'date' => 'required|date',
            'vehicule_id' => 'required|exists:vehicules,id',
            'chauffeur' => ['nullable', \Illuminate\Validation\Rule::in(Chauffeur::nomsValides())],
            'recette' => 'required|integer|min:0',
            'pointage' => 'required|integer|min:0',
            'mode_remise' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'controle_par' => 'nullable|string|max:100',
            'observations' => 'nullable|string|max:500',
            'motif_absence' => 'nullable|string|max:80',
            'perspective' => 'nullable|string|max:500',
            'justification_valable' => 'nullable|in:0,1',
        ], ['chauffeur.in' => "Chauffeur inconnu : choisissez un chauffeur enregistré dans l'onglet Chauffeurs."]);

        $montant = (int) ($data['recette'] ?? 0);

        // Recette > 0 → mode_remise obligatoire
        if ($montant > 0 && empty($data['mode_remise'])) {
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette'])
                ->withInput()->withErrors(['mode_remise' => 'Le mode de remise est obligatoire.']);
        }
        // Recette = 0 → motif obligatoire, mode_remise facultatif
        if ($montant == 0 && empty($data['motif_absence'])) {
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette'])
                ->withInput()->withErrors(['motif_absence' => 'Le motif est obligatoire lorsque la recette est à 0.']);
        }
        // Recette = 0 → vider mode_remise
        if ($montant == 0) {
            $data['mode_remise'] = null;
        }
        try {
            $v = Vehicule::find($data['vehicule_id']);
            $data['type_jour'] = $this->detecterTypeJour($data['date']);

            // Bloquer si un autre chauffeur a déjà une recette pour ce véhicule ce jour
            $conflit = RecetteVehicule::where('vehicule_id', $data['vehicule_id'])
                ->whereDate('date', $data['date'])
                ->where('chauffeur', '!=', $data['chauffeur'] ?? '')
                ->first();
            if ($conflit) {
                return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette'])
                    ->withInput()->withErrors(['vehicule_id' => 'Ce véhicule est déjà utilisé par ' . $conflit->chauffeur . ' le ' . $conflit->date->format('d/m/Y')]);
            }

            $categorie = $v->categorie ?? 'taxi';
            $objectifParam = ParametreParc::objectifRecette($categorie, $data['type_jour']);
            $data['objectif'] = $objectifParam > 0 ? $objectifParam : ($v->objectif_recette ?? 0);
            $data['ecart'] = $data['recette'] - $data['objectif'];

            // Recette < objectif et > 0 → motif et perspective obligatoires
            if ($montant > 0 && $montant < $data['objectif'] && empty($data['motif_absence'])) {
                return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette'])
                    ->withInput()->withErrors(['motif_absence' => 'Motif obligatoire : objectif non atteint.']);
            }

            // Recette >= objectif → vider le motif (pas besoin de justification)
            if ($montant > 0 && $montant >= $data['objectif']) {
                $data['motif_absence'] = null;
            }

            // Validation gestionnaire : justification_valable
            $justifValable = filter_var($data['justification_valable'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $deficit = $montant < $data['objectif'] ? ($data['objectif'] - $montant) : 0;
            $data['justification_valable'] = ($deficit > 0) ? $justifValable : null;
            $data['valide_par'] = $r->user()->name;
            $data['valide_le'] = now();

            $rec = RecetteVehicule::create($data);

            // Mouvement caisse pour recette
            if ($montant > 0 && Schema::hasTable('mouvements_caisse')) {
                MouvementCaisse::create([
                    'caisse_code' => 'generale', 'sens' => 'entree', 'montant' => $montant,
                    'date_mouvement' => $data['date'], 'source_type' => 'recette', 'source_id' => $rec->id,
                    'libelle' => "Recette — " . ($v->immatriculation ?? '') . " / " . ($data['chauffeur'] ?? ''),
                    'cree_par' => auth()->user()->name,
                ]);
            }
            // Mouvement caisse pour pointage
            if (($data['pointage'] ?? 0) > 0 && Schema::hasTable('mouvements_caisse')) {
                MouvementCaisse::create([
                    'caisse_code' => 'pointage', 'sens' => 'entree', 'montant' => $data['pointage'],
                    'date_mouvement' => $data['date'], 'source_type' => 'pointage', 'source_id' => $rec->id,
                    'libelle' => "Pointage — " . ($v->immatriculation ?? '') . " / " . ($data['chauffeur'] ?? ''),
                    'cree_par' => auth()->user()->name,
                ]);
            }

            // Création recouvrement si déficit ET justification non valable (anti-doublon)
            if ($deficit > 0 && !$justifValable && Schema::hasTable('recouvrements') && !empty($data['chauffeur'])) {
                $dejaRecouvr = Recouvrement::where('chauffeur_nom', $data['chauffeur'])
                    ->where('vehicule_id', $data['vehicule_id'])
                    ->whereDate('date_recette', $data['date'])
                    ->first();
                if (!$dejaRecouvr) {
                    Recouvrement::create([
                        'chauffeur_nom' => $data['chauffeur'],
                        'vehicule_id' => $data['vehicule_id'],
                        'date_recette' => $data['date'],
                        'objectif' => $data['objectif'],
                        'recette_realisee' => $data['recette'],
                        'deficit' => $deficit,
                        'montant_recouvre' => 0,
                        'solde' => $deficit,
                        'justification_valable' => false,
                        'statut' => 'actif',
                        'cree_par' => $r->user()->name,
                    ]);
                    AuditLog::log('creation', 'recouvrement', $data['chauffeur'], "Déficit {$deficit} F — justification non valable (saisie individuelle)");
                }
            }

            // Flash résumé pour dialog post-soumission
            $resumeRecette = [
                'chauffeur' => $data['chauffeur'] ?? '—',
                'vehicule' => $v->immatriculation ?? '—',
                'date' => \Carbon\Carbon::parse($data['date'])->format('d/m/Y'),
                'montant' => $montant,
                'objectif' => $data['objectif'],
                'ecart' => $data['ecart'],
                'pointage' => $data['pointage'] ?? 0,
                'mode_remise' => $data['mode_remise'] ?? '',
                'deficit' => $deficit,
                'recette_id' => $rec->id,
                'statut' => $montant >= $data['objectif'] ? 'Objectif atteint' : 'En attente de validation',
            ];

            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $data['date']])
                ->with('succes', 'Recette enregistrée.')
                ->with('resume_recette', $resumeRecette);
        } catch (\Exception $e) {
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date')])
                ->withErrors(['Erreur : ' . $e->getMessage()]);
        }
    }

    public function updateRecette(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent modifier une recette.');

        // Bloquer modification si déjà validée par le gestionnaire
        if ($recette->valide_par && !$r->user()->estAdmin()) {
            return back()->withErrors(['Recette déjà validée par ' . $recette->valide_par . '. Aucune modification autorisée.']);
        }

        $data = $r->validate([
            'date' => 'required|date',
            'vehicule_id' => 'required|exists:vehicules,id',
            'chauffeur' => ['nullable', \Illuminate\Validation\Rule::in(Chauffeur::nomsValides())],
            'recette' => 'required|integer|min:0',
            'pointage' => 'required|integer|min:0',
            'mode_remise' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'controle_par' => 'nullable|string|max:100',
            'observations' => 'nullable|string|max:500',
            'motif_absence' => 'nullable|string|max:80',
            'perspective' => 'nullable|string|max:500',
            'justification_valable' => 'nullable|in:0,1',
        ], ['chauffeur.in' => "Chauffeur inconnu : choisissez un chauffeur enregistré dans l'onglet Chauffeurs."]);

        $montant = (int) ($data['recette'] ?? 0);

        if ($montant > 0 && empty($data['mode_remise'])) {
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette', 'edit_recette_id' => $recette->id])
                ->withInput()->withErrors(['mode_remise' => 'Le mode de remise est obligatoire.']);
        }
        if ($montant == 0 && empty($data['motif_absence'])) {
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette', 'edit_recette_id' => $recette->id])
                ->withInput()->withErrors(['motif_absence' => 'Le motif est obligatoire lorsque la recette est à 0.']);
        }
        if ($montant == 0) {
            $data['mode_remise'] = null;
        }

        try {
            $v = Vehicule::find($data['vehicule_id']);
            $data['type_jour'] = $this->detecterTypeJour($data['date']);

            $conflit = RecetteVehicule::where('vehicule_id', $data['vehicule_id'])
                ->whereDate('date', $data['date'])
                ->where('chauffeur', '!=', $data['chauffeur'] ?? '')
                ->where('id', '!=', $recette->id)
                ->first();
            if ($conflit) {
                return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette', 'edit_recette_id' => $recette->id])
                    ->withInput()->withErrors(['vehicule_id' => 'Ce véhicule est déjà utilisé par ' . $conflit->chauffeur . ' le ' . $conflit->date->format('d/m/Y')]);
            }

            $categorie = $v->categorie ?? 'taxi';
            $objectifParam = ParametreParc::objectifRecette($categorie, $data['type_jour']);
            $data['objectif'] = $objectifParam > 0 ? $objectifParam : ($v->objectif_recette ?? 0);
            $data['ecart'] = $data['recette'] - $data['objectif'];

            if ($montant > 0 && $montant < $data['objectif'] && empty($data['motif_absence'])) {
                return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette', 'edit_recette_id' => $recette->id])
                    ->withInput()->withErrors(['motif_absence' => 'Motif obligatoire : objectif non atteint.']);
            }

            // Recette >= objectif → vider le motif (pas besoin de justification)
            if ($montant > 0 && $montant >= $data['objectif']) {
                $data['motif_absence'] = null;
            }

            // Validation gestionnaire
            $justifValable = filter_var($data['justification_valable'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $deficit = $data['recette'] < $data['objectif'] ? ($data['objectif'] - $data['recette']) : 0;
            $data['justification_valable'] = ($deficit > 0) ? $justifValable : null;
            $data['valide_par'] = $r->user()->name;
            $data['valide_le'] = now();

            $recette->update($data);

            AuditLog::log('modification', 'recette', "Recette #{$recette->id}", "Modifiee par " . $r->user()->name . " — {$data['chauffeur']} / " . ($v->immatriculation ?? '') . " du " . $data['date']);

            // Mise a jour mouvements caisse
            if (Schema::hasTable('mouvements_caisse')) {
                MouvementCaisse::where('source_type', 'recette')->where('source_id', $recette->id)->delete();
                MouvementCaisse::where('source_type', 'pointage')->where('source_id', $recette->id)->delete();
                if ($montant > 0) {
                    MouvementCaisse::create([
                        'caisse_code' => 'generale', 'sens' => 'entree', 'montant' => $montant,
                        'date_mouvement' => $data['date'], 'source_type' => 'recette', 'source_id' => $recette->id,
                        'libelle' => "Recette — " . ($v->immatriculation ?? '') . " / " . ($data['chauffeur'] ?? ''),
                        'cree_par' => auth()->user()->name,
                    ]);
                }
                if (($data['pointage'] ?? 0) > 0) {
                    MouvementCaisse::create([
                        'caisse_code' => 'pointage', 'sens' => 'entree', 'montant' => $data['pointage'],
                        'date_mouvement' => $data['date'], 'source_type' => 'pointage', 'source_id' => $recette->id,
                        'libelle' => "Pointage — " . ($v->immatriculation ?? '') . " / " . ($data['chauffeur'] ?? ''),
                        'cree_par' => auth()->user()->name,
                    ]);
                }
            }

            // Création recouvrement si déficit ET justification non valable
            if ($deficit > 0 && !$justifValable && Schema::hasTable('recouvrements') && !empty($data['chauffeur'])) {
                $dejaRecouvr = Recouvrement::where('chauffeur_nom', $data['chauffeur'])
                    ->where('vehicule_id', $data['vehicule_id'])
                    ->whereDate('date_recette', $data['date'])
                    ->first();
                if (!$dejaRecouvr) {
                    Recouvrement::create([
                        'chauffeur_nom' => $data['chauffeur'],
                        'vehicule_id' => $data['vehicule_id'],
                        'date_recette' => $data['date'],
                        'objectif' => $data['objectif'],
                        'recette_realisee' => $data['recette'],
                        'deficit' => $deficit,
                        'montant_recouvre' => 0,
                        'solde' => $deficit,
                        'justification_valable' => false,
                        'statut' => 'actif',
                        'cree_par' => $r->user()->name,
                    ]);
                    AuditLog::log('creation', 'recouvrement', $data['chauffeur'], "Déficit {$deficit} F — justification non valable (modification recette)");
                }
            }

            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $data['date']])->with('succes', 'Recette mise à jour.');
        } catch (\Exception $e) {
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $r->input('date'), 'open_dlg' => 'recette'])
                ->withErrors(['Erreur : ' . $e->getMessage()]);
        }
    }

    public function destroyRecette(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'recette', "Recette #{$recette->id}", "Motif: " . $r->motif_suppression);
        // Le déficit de cette recette n'existe plus : son recouvrement est annulé s'il n'a encore rien perçu
        if (Schema::hasTable('recouvrements')) {
            Recouvrement::where('recette_vehicule_id', $recette->id)->where('statut', 'actif')
                ->where('montant_recouvre', 0)->update(['statut' => 'annule']);
        }
        $recette->delete();
        return redirect()->route('parc.index', ['tab' => 'recettes'])->with('succes', 'Recette supprimée.');
    }

    /* ===== VALIDATION EN MASSE JOURNÉE ===== */
    public function validerJournee(Request $r)
    {
        $this->autoriser($r);
        $dateJour = $r->input('date_jour');
        $lignes = $r->input('lignes', []);

        if (!$dateJour || empty($lignes)) {
            return redirect()->route('parc.index', ['tab' => 'recettes'])->withErrors(['Données manquantes.']);
        }

        $typeJour = $this->detecterTypeJour($dateJour);
        $nomsChauffeurs = Chauffeur::nomsValides();
        $erreurs = [];
        $nbCrees = 0;
        $nbMaj = 0;

        try {
            foreach ($lignes as $i => $ligne) {
                $vehiculeId = $ligne['vehicule_id'] ?? null;
                if (!$vehiculeId) continue;

                $v = Vehicule::find($vehiculeId);
                if (!$v) continue;

                if (!empty($ligne['chauffeur']) && !in_array($ligne['chauffeur'], $nomsChauffeurs, true)) {
                    $erreurs[] = "{$ligne['chauffeur']} : chauffeur inconnu, choisissez un chauffeur enregistré";
                    continue;
                }

                $estRepos = isset($ligne['est_repos']) && $ligne['est_repos'];
                $montant = (int) ($ligne['recette'] ?? 0);
                $motif = $ligne['motif_absence'] ?? null;
                $perspective = $ligne['perspective'] ?? null;

                $categorie = $v->categorie ?? 'taxi';
                $objectifParam = ParametreParc::objectifRecette($categorie, $typeJour);
                $objectif = $estRepos ? 0 : ($objectifParam > 0 ? $objectifParam : ($v->objectif_recette ?? 0));

                if ($estRepos) {
                    $montant = 0;
                    $motif = 'jour_repos';
                    $perspective = null;
                } else {
                    // Recette = 0 sans motif = erreur
                    if ($montant == 0 && empty($motif)) {
                        $erreurs[] = ($ligne['chauffeur'] ?? $v->immatriculation) . ' : motif obligatoire (recette = 0)';
                        continue;
                    }
                    // Recette < objectif et > 0 → motif souhaité, perspective recommandée
                    if ($montant > 0 && $montant < $objectif && empty($motif)) {
                        $motif = 'sous_objectif';
                    }
                    // Recette >= objectif → nettoyer
                    if ($montant >= $objectif) {
                        $motif = null;
                        $perspective = null;
                    }
                }

                $donnees = [
                    'date' => $dateJour,
                    'vehicule_id' => $vehiculeId,
                    'chauffeur' => $ligne['chauffeur'] ?? null,
                    'recette' => $montant,
                    'objectif' => $objectif,
                    'ecart' => $montant - $objectif,
                    'pointage' => (int) ($ligne['pointage'] ?? 0),
                    'type_jour' => $typeJour,
                    'mode_remise' => $ligne['mode_remise'] ?? null,
                    'controle_par' => $ligne['controle_par'] ?? null,
                    'observations' => $ligne['observations'] ?? null,
                    'motif_absence' => $motif,
                    'perspective' => $perspective,
                ];

                // Recette individuelle : chercher par véhicule + chauffeur + date
                $chauffeurNomLigne = $ligne['chauffeur'] ?? null;
                $existant = RecetteVehicule::where('vehicule_id', $vehiculeId)
                    ->whereDate('date', $dateJour)
                    ->where('chauffeur', $chauffeurNomLigne)
                    ->first();

                // Bloquer si un AUTRE chauffeur a déjà une recette pour ce véhicule ce jour
                $autreRecette = RecetteVehicule::where('vehicule_id', $vehiculeId)
                    ->whereDate('date', $dateJour)
                    ->where('chauffeur', '!=', $chauffeurNomLigne ?? '')
                    ->first();
                if ($autreRecette && !$estRepos) {
                    $erreurs[] = ($chauffeurNomLigne ?? $v->immatriculation) . ' : véhicule déjà utilisé par ' . $autreRecette->chauffeur . ' ce jour';
                    continue;
                }
                $aColVerrou = Schema::hasColumn('recettes_vehicules', 'valide_par');
                if ($existant) {
                    // Vérifier verrouillage : seul charge_operations peut modifier une recette validée
                    $estChargeOps = auth()->user()->estAdmin();
                    if ($aColVerrou && $existant->valide_par && !$estChargeOps) {
                        $erreurs[] = ($ligne['chauffeur'] ?? $v->immatriculation) . ' : recette verrouillée, modification non autorisée';
                        continue;
                    }
                    if ($aColVerrou && $existant->valide_par && $estChargeOps) {
                        $donnees['motif_modification'] = $ligne['motif_modification'] ?? 'Correction par ' . auth()->user()->name;
                        $donnees['modifie_par'] = auth()->user()->name;
                        $donnees['modifie_le'] = now();
                    }
                    $existant->update($donnees);
                    $recetteObj = $existant;
                    $nbMaj++;
                } else {
                    if ($aColVerrou) {
                        $donnees['valide_par'] = auth()->user()->name;
                        $donnees['valide_le'] = now();
                    }
                    $recetteObj = RecetteVehicule::create($donnees);
                    $nbCrees++;

                    // Mouvement caisse pour recette
                    if ($montant > 0 && Schema::hasTable('mouvements_caisse')) {
                        MouvementCaisse::create([
                            'caisse_code' => 'generale', 'sens' => 'entree', 'montant' => $montant,
                            'date_mouvement' => $dateJour, 'source_type' => 'recette', 'source_id' => $recetteObj->id,
                            'libelle' => "Recette — " . ($v->immatriculation ?? '') . " / " . ($chauffeurNomLigne ?? ''),
                            'cree_par' => auth()->user()->name,
                        ]);
                    }
                    // Mouvement caisse pour pointage
                    $ptg = (int) ($ligne['pointage'] ?? 0);
                    if ($ptg > 0 && Schema::hasTable('mouvements_caisse')) {
                        MouvementCaisse::create([
                            'caisse_code' => 'pointage', 'sens' => 'entree', 'montant' => $ptg,
                            'date_mouvement' => $dateJour, 'source_type' => 'pointage', 'source_id' => $recetteObj->id,
                            'libelle' => "Pointage — " . ($v->immatriculation ?? '') . " / " . ($chauffeurNomLigne ?? ''),
                            'cree_par' => auth()->user()->name,
                        ]);
                    }
                }

                // ─── Gestion recouvrement si déficit ───
                if (!$estRepos && Schema::hasTable('recouvrements')) {
                    $justifValable = filter_var($ligne['justification_valable'] ?? true, FILTER_VALIDATE_BOOLEAN);
                    $deficit = $montant < $objectif ? ($objectif - $montant) : 0;
                    $chauffeurNom = $ligne['chauffeur'] ?? null;

                    // Chercher chauffeur_id
                    $chauffeurId = null;
                    if ($chauffeurNom && Schema::hasTable('chauffeurs')) {
                        $ch = Chauffeur::whereRaw("CONCAT(nom,' ',prenom) = ?", [$chauffeurNom])
                            ->orWhereRaw("CONCAT(prenom,' ',nom) = ?", [$chauffeurNom])
                            ->first();
                        $chauffeurId = $ch?->id;
                    }

                    $existantRecouv = Recouvrement::where('recette_vehicule_id', $recetteObj->id)
                        ->where('statut', 'actif')->first();

                    if ($montant >= $objectif || $justifValable) {
                        // Supprimer seulement si aucun versement effectué
                        if ($existantRecouv && $existantRecouv->montant_recouvre == 0) {
                            $existantRecouv->delete();
                        }
                    } elseif ($deficit > 0 && !$justifValable) {
                        if ($existantRecouv && $existantRecouv->montant_recouvre == 0) {
                            // MAJ recouvrement existant sans versement
                            $existantRecouv->update([
                                'montant_verse' => $montant,
                                'objectif' => $objectif,
                                'deficit' => $deficit,
                                'solde' => $deficit,
                                'motif' => $motif,
                            ]);
                        } elseif (!$existantRecouv) {
                            Recouvrement::create([
                                'recette_vehicule_id' => $recetteObj->id,
                                'chauffeur_id' => $chauffeurId,
                                'chauffeur_nom' => $chauffeurNom,
                                'vehicule_id' => $vehiculeId,
                                'date_recette' => $dateJour,
                                'montant_verse' => $montant,
                                'objectif' => $objectif,
                                'deficit' => $deficit,
                                'montant_recouvre' => 0,
                                'solde' => $deficit,
                                'statut' => 'actif',
                                'motif' => $motif,
                                'justification_valable' => false,
                                'cree_par' => $ligne['controle_par'] ?? auth()->user()->name,
                            ]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $dateJour])
                ->withErrors(['Erreur lors de la validation : ' . $e->getMessage()]);
        }

        $msg = "Journée du " . \Carbon\Carbon::parse($dateJour)->format('d/m/Y') . " validée : {$nbCrees} créée(s), {$nbMaj} mise(s) à jour.";
        AuditLog::log($nbMaj > 0 ? 'modification' : 'creation', 'recettes', $dateJour, "{$nbCrees} créée(s), {$nbMaj} MAJ");

        // Flash résumé pour dialog post-soumission (dernière ligne traitée)
        if (isset($recetteObj) && isset($v)) {
            $resumeRecette = [
                'chauffeur' => $recetteObj->chauffeur ?? '—',
                'vehicule' => $v->immatriculation ?? '—',
                'date' => \Carbon\Carbon::parse($dateJour)->format('d/m/Y'),
                'montant' => $recetteObj->recette ?? 0,
                'objectif' => $recetteObj->objectif ?? 0,
                'ecart' => $recetteObj->ecart ?? 0,
                'pointage' => $recetteObj->pointage ?? 0,
                'mode_remise' => $recetteObj->mode_remise ?? '',
                'deficit' => max(0, ($recetteObj->objectif ?? 0) - ($recetteObj->recette ?? 0)),
                'recette_id' => $recetteObj->id,
                'statut' => ($recetteObj->recette ?? 0) >= ($recetteObj->objectif ?? 0) ? 'Objectif atteint' : 'En attente de validation',
            ];
            session()->flash('resume_recette', $resumeRecette);
        }

        if (!empty($erreurs)) {
            $msg .= ' Erreurs : ' . implode(' | ', $erreurs);
            return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $dateJour])->with('succes', $msg)->withErrors($erreurs);
        }
        return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $dateJour])->with('succes', $msg);
    }

    /* ===== MAINTENANCE CRUD ===== */
    public function storeMaintenance(Request $r)
    {
        $this->autoriser($r);
        $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'type_echeance' => 'required|string|max:50',
            'derniere_realisation' => 'nullable|date',
            'prochaine_echeance' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ]);

        $data = $r->only(['vehicule_id', 'type_echeance', 'derniere_realisation', 'prochaine_echeance', 'observations']);

        // Auto-calcul prochaine échéance si non renseignée et derniere_realisation fournie
        if (empty($data['prochaine_echeance']) && !empty($data['derniere_realisation'])) {
            $tp = TypeParc::where('categorie', 'maintenance')->where('code', $data['type_echeance'])->first();
            if ($tp && $tp->intervalle_jours) {
                $data['prochaine_echeance'] = \Carbon\Carbon::parse($data['derniere_realisation'])
                    ->addDays($tp->intervalle_jours)->toDateString();
            }
        }

        $m = MaintenancePreventive::create($data);
        AuditLog::log('creation', 'maintenance', "Maintenance #{$m->id}", "Type: {$m->type_echeance}, Véhicule ID: {$m->vehicule_id}");

        return redirect()->route('parc.index', ['tab' => 'maint'])->with('succes', 'Échéance ajoutée.');
    }

    public function updateMaintenance(Request $r, MaintenancePreventive $maintenance)
    {
        $this->autoriser($r);
        $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'type_echeance' => 'required|string|max:50',
            'derniere_realisation' => 'nullable|date',
            'prochaine_echeance' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ]);

        $maintenance->update($r->only([
            'vehicule_id', 'type_echeance', 'derniere_realisation', 'prochaine_echeance', 'observations',
        ]));
        if ($maintenance->depense_id) {
            Depense::where('id', $maintenance->depense_id)->update(['vehicule_id' => $maintenance->vehicule_id]);
        }
        AuditLog::log('modification', 'maintenance', "Maintenance #{$maintenance->id}", "Type: {$maintenance->type_echeance}");

        return redirect()->route('parc.index', ['tab' => 'maint'])->with('succes', 'Échéance mise à jour.');
    }

    public function realiserMaintenance(Request $r, MaintenancePreventive $maintenance)
    {
        $this->autoriser($r);
        $r->validate([
            'date_realisation' => 'required|date',
            'cout'             => 'required|integer|min:1',
            'prestataire'      => 'required|string|max:150',
            'prochaine_echeance_suivante' => 'nullable|date|after:date_realisation',
            'observations'     => 'nullable|string|max:500',
        ]);

        $maintenance->statut = 'realisee';
        $maintenance->date_realisation = $r->date_realisation;
        $maintenance->cout = $r->cout;
        $maintenance->prestataire = $r->prestataire;
        $maintenance->derniere_realisation = $r->date_realisation;
        if ($r->observations) $maintenance->observations = $r->observations;

        // Créer la dépense liée au véhicule
        if ($r->cout > 0) {
            $depData = [
                'date_depense'  => $r->date_realisation,
                'type_depense'  => 'maintenance',
                'vehicule_id'   => $maintenance->vehicule_id,
                'montant'       => $r->cout,
                'commentaire'   => 'Maintenance ' . $maintenance->typeLabel() . ' — ' . ($maintenance->vehicule->immatriculation ?? ''),
                'cree_par'      => auth()->user()->name,
            ];

            // Emprunt si source_financement = emprunt
            if ($r->source_financement === 'emprunt') {
                $emp = Emprunt::create([
                    'montant' => $r->cout,
                    'date_emprunt' => $r->date_realisation,
                    'preteur' => $r->preteur ?? 'Non précisé',
                    'motif' => 'Maintenance ' . $maintenance->typeLabel() . ' — ' . ($maintenance->vehicule->immatriculation ?? ''),
                    'source_type' => 'maintenance',
                    'source_id' => $maintenance->id,
                    'vehicule_id' => $maintenance->vehicule_id,
                    'cree_par' => auth()->user()->name,
                ]);
                $depData['emprunt_id'] = $emp->id;
            }

            $dep = Depense::create($depData);
            $maintenance->depense_id = $dep->id;
        }

        $maintenance->save();
        AuditLog::log('realisation', 'maintenance', "Maintenance #{$maintenance->id}", "Coût: {$maintenance->cout} F, Véhicule: " . ($maintenance->vehicule->immatriculation ?? ''));

        // Auto-créer la prochaine échéance
        $msg = 'Maintenance réalisée.';
        $dateProchaineEch = $r->prochaine_echeance_suivante;

        // Si pas de date prochaine fournie, calculer automatiquement depuis l'intervalle du type
        if (!$dateProchaineEch) {
            $tp = TypeParc::where('categorie', 'maintenance')->where('code', $maintenance->type_echeance)->first();
            if ($tp && $tp->intervalle_jours) {
                $dateProchaineEch = \Carbon\Carbon::parse($r->date_realisation)
                    ->addDays($tp->intervalle_jours)->toDateString();
            }
        }

        if ($dateProchaineEch) {
            $next = MaintenancePreventive::create([
                'vehicule_id' => $maintenance->vehicule_id,
                'type_echeance' => $maintenance->type_echeance,
                'derniere_realisation' => $r->date_realisation,
                'prochaine_echeance' => $dateProchaineEch,
                'observations' => null,
            ]);
            AuditLog::log('creation', 'maintenance', "Maintenance #{$next->id}", "Prochaine échéance auto — Type: {$next->type_echeance}, Véhicule: " . ($maintenance->vehicule->immatriculation ?? ''));
            $msg = 'Maintenance réalisée. Prochaine échéance planifiée au ' . date('d/m/Y', strtotime($dateProchaineEch)) . '.';
        }

        return redirect()->route('parc.index', ['tab' => 'maint'])->with('succes', $msg);
    }

    public function destroyMaintenance(Request $r, MaintenancePreventive $maintenance)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'maintenance', "Maintenance #{$maintenance->id}", "Type: {$maintenance->type_echeance}, Motif: " . $r->motif_suppression);
        if ($maintenance->depense_id) Depense::where('id', $maintenance->depense_id)->delete();
        $maintenance->delete();
        return redirect()->route('parc.index', ['tab' => 'maint'])->with('succes', 'Échéance supprimée.');
    }

    /* ===== PÉNALITÉS CRUD ===== */
    public function storePenalite(Request $r)
    {
        $this->autoriser($r);
        $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'date_infraction' => 'required|date',
            'type' => 'required|string|max:50',
            'chauffeur' => ['required', \Illuminate\Validation\Rule::in(Chauffeur::nomsValides())],
            'lieu' => 'required|string|max:120',
            'description' => 'required|string|max:500',
            'montant' => 'required|integer|min:0',
            'statut' => 'required|in:non_payee,payee,en_cours,contestee',
            'date_paiement' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ], ['chauffeur.in' => "Chauffeur inconnu : choisissez un chauffeur enregistré dans l'onglet Chauffeurs."]);

        $data = $r->only([
            'vehicule_id', 'date_infraction', 'type', 'chauffeur', 'lieu',
            'description', 'montant', 'statut', 'date_paiement', 'observations',
        ]);
        $data['montant_paye'] = 0;

        $p = Penalite::create($data);

        // Créer un recouvrement lié si non payée (anti-doublon sur penalite_id)
        if ($p->statut !== 'payee' && $p->montant > 0 && Schema::hasTable('recouvrements') && Schema::hasColumn('recouvrements', 'penalite_id')) {
            $dejaRecouvrPenal = Recouvrement::where('penalite_id', $p->id)->exists();
            if (!$dejaRecouvrPenal) {
                $chauffeurId = null;
                if ($p->chauffeur && Schema::hasTable('chauffeurs')) {
                    $ch = Chauffeur::whereRaw("CONCAT(nom,' ',prenom) = ?", [$p->chauffeur])
                        ->orWhereRaw("CONCAT(prenom,' ',nom) = ?", [$p->chauffeur])->first();
                    $chauffeurId = $ch?->id;
                }
                Recouvrement::create([
                    'penalite_id' => $p->id,
                    'chauffeur_id' => $chauffeurId,
                    'chauffeur_nom' => $p->chauffeur,
                    'vehicule_id' => $p->vehicule_id,
                    'date_recette' => $p->date_infraction,
                    'montant_verse' => 0,
                    'objectif' => $p->montant,
                    'deficit' => $p->montant,
                    'montant_recouvre' => 0,
                    'solde' => $p->montant,
                    'statut' => 'actif',
                    'type_recouvrement' => 'penalite',
                    'motif' => 'Pénalité — ' . $p->typeLabel(),
                    'cree_par' => auth()->user()->name,
                ]);
            }
        }

        AuditLog::log('creation', 'penalite', "Pénalité #{$p->id}", "Type: {$p->type}, Montant: {$p->montant} F, Chauffeur: {$p->chauffeur}");
        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Pénalité enregistrée.');
    }

    public function updatePenalite(Request $r, Penalite $penalite)
    {
        $this->autoriser($r);
        $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'date_infraction' => 'required|date',
            'type' => 'required|string|max:50',
            'chauffeur' => ['required', \Illuminate\Validation\Rule::in(Chauffeur::nomsValides())],
            'lieu' => 'required|string|max:120',
            'description' => 'required|string|max:500',
            'montant' => 'required|integer|min:0',
            'statut' => 'required|in:non_payee,payee,en_cours,contestee',
            'date_paiement' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ], ['chauffeur.in' => "Chauffeur inconnu : choisissez un chauffeur enregistré dans l'onglet Chauffeurs."]);

        $penalite->update($r->only([
            'vehicule_id', 'date_infraction', 'type', 'chauffeur', 'lieu',
            'description', 'montant', 'statut', 'date_paiement', 'observations',
        ]));
        AuditLog::log('modification', 'penalite', "Pénalité #{$penalite->id}", "Type: {$penalite->type}, Montant: {$penalite->montant} F");

        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Pénalité mise à jour.');
    }

    public function destroyPenalite(Request $r, Penalite $penalite)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'penalite', "Pénalité #{$penalite->id}", "Type: {$penalite->type}, Montant: {$penalite->montant}, Motif: " . $r->motif_suppression);
        $penalite->delete();
        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Pénalité supprimée.');
    }

    /* ===== PAIEMENTS PÉNALITÉS ===== */
    public function storePaiementPenalite(Request $r, Penalite $penalite)
    {
        $this->autoriser($r);
        $r->validate([
            'montant' => 'required|integer|min:1|max:' . $penalite->soldeRestant(),
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|in:especes,mobile_money,cheque,virement,autre',
            'source_paiement' => 'required|in:pointage,chauffeur,entreprise',
            'observations' => 'nullable|string|max:500',
        ]);

        $sourcePaiement = $r->source_paiement;

        $ppData = [
            'penalite_id' => $penalite->id,
            'montant' => $r->montant,
            'date_paiement' => $r->date_paiement,
            'mode_paiement' => $r->mode_paiement,
            'source_paiement' => $sourcePaiement,
            'recu_par' => auth()->user()->name,
            'observations' => $r->observations,
        ];

        // Emprunt si source_financement = emprunt ET source = entreprise
        if ($sourcePaiement === 'entreprise' && $r->source_financement === 'emprunt') {
            $emp = Emprunt::create([
                'montant' => $r->montant,
                'date_emprunt' => $r->date_paiement,
                'preteur' => $r->preteur ?? 'Non précisé',
                'motif' => 'Paiement pénalité — ' . ($penalite->chauffeur ?? $penalite->vehicule?->immatriculation ?? ''),
                'source_type' => 'penalite',
                'source_id' => $penalite->id,
                'vehicule_id' => $penalite->vehicule_id,
                'cree_par' => auth()->user()->name,
            ]);
            $ppData['emprunt_id'] = $emp->id;
        }

        PaiementPenalite::create($ppData);

        $penalite->recalculerPaiement();

        // Synchroniser avec le recouvrement lié via VersementRecouvrement
        if (Schema::hasTable('recouvrements') && Schema::hasColumn('recouvrements', 'penalite_id')) {
            $recouv = Recouvrement::where('penalite_id', $penalite->id)->where('statut', 'actif')->first();
            if ($recouv) {
                // Créer un VersementRecouvrement pour que le reporting fonctionne
                $versData = [
                    'recouvrement_id' => $recouv->id,
                    'montant' => $r->montant,
                    'date_versement' => $r->date_paiement,
                    'mode_paiement' => $r->mode_paiement,
                    'source_paiement' => $sourcePaiement,
                    'recu_par' => auth()->user()->name,
                    'observations' => 'Via paiement pénalité #' . $penalite->id,
                ];

                // Si entreprise paie → créer une dépense liée
                if ($sourcePaiement === 'entreprise') {
                    $dep = Depense::create([
                        'date_depense' => $r->date_paiement,
                        'type_depense' => 'recouvrement',
                        'vehicule_id' => $penalite->vehicule_id,
                        'montant' => $r->montant,
                        'commentaire' => "Pénalité payée par l'entreprise : {$penalite->typeLabel()} de {$penalite->chauffeur} du " . $penalite->date_infraction->format('d/m/Y'),
                        'cree_par' => auth()->user()->name,
                    ]);
                    $versData['depense_id'] = $dep->id;
                }

                VersementRecouvrement::create($versData);
                $recouv->recalculerSolde();
            }
        }

        AuditLog::log('creation', 'paiement_penalite', "Pénalité #{$penalite->id}", "Versement: {$r->montant} F ({$sourcePaiement}), Solde: {$penalite->soldeRestant()} F");
        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Paiement enregistré. Solde : ' . number_format($penalite->soldeRestant(), 0, ',', ' ') . ' F');
    }

    /* ===== CHAUFFEURS CRUD ===== */
    public function storeChauffeur(Request $r)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'nom' => 'required|string|max:60',
            'prenom' => 'required|string|max:60',
            'date_naissance' => 'nullable|date',
            'adresse' => 'nullable|string|max:200',
            'numero_cni' => 'nullable|string|max:30',
            'date_embauche' => 'nullable|date',
            'personne_contact' => 'nullable|string|max:150',
            'numero_permis' => 'nullable|string|max:30|unique:chauffeurs,numero_permis',
            'telephone' => 'nullable|string|max:20',
            'statut' => 'required|in:actif,inactif',
            'observations' => 'nullable|string',
        ]);
        $ch = Chauffeur::create($data);
        AuditLog::log('creation', 'chauffeur', "Chauffeur #{$ch->id}", "{$data['nom']} {$data['prenom']}");
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur enregistré.');
    }

    public function updateChauffeur(Request $r, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'nom' => 'required|string|max:60',
            'prenom' => 'required|string|max:60',
            'date_naissance' => 'nullable|date',
            'adresse' => 'nullable|string|max:200',
            'numero_cni' => 'nullable|string|max:30',
            'date_embauche' => 'nullable|date',
            'personne_contact' => 'nullable|string|max:150',
            'numero_permis' => 'nullable|string|max:30|unique:chauffeurs,numero_permis,' . $chauffeur->id,
            'telephone' => 'nullable|string|max:20',
            'statut' => 'required|in:actif,inactif',
            'observations' => 'nullable|string',
        ]);
        $chauffeur->update($data);
        AuditLog::log('modification', 'chauffeur', "Chauffeur #{$chauffeur->id}", "{$data['nom']} {$data['prenom']}");
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur mis à jour.');
    }

    public function destroyChauffeur(Request $r, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'chauffeur', "Chauffeur #{$chauffeur->id}", "{$chauffeur->nom} {$chauffeur->prenom}, Motif: " . $r->motif_suppression);
        $chauffeur->delete();
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur supprimé.');
    }

    public function affecterChauffeur(Request $r)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403, 'La direction ne peut pas affecter un chauffeur.');
        $data = $r->validate([
            'chauffeur_id' => 'required|exists:chauffeurs,id',
            'vehicule_id' => 'required|exists:vehicules,id',
            'role' => 'required|in:principal,alternant',
        ]);
        $v = Vehicule::findOrFail($data['vehicule_id']);
        $v->chauffeurs()->syncWithoutDetaching([$data['chauffeur_id'] => ['role' => $data['role'], 'actif' => true]]);
        $chAff = Chauffeur::find($data['chauffeur_id']);
        AuditLog::log('affectation', 'chauffeur', "Chauffeur #{$data['chauffeur_id']}", ($chAff ? "{$chAff->nom} {$chAff->prenom}" : '') . " → {$v->immatriculation} ({$data['role']})");
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur affecté.');
    }

    public function detacherChauffeur(Request $r, Vehicule $vehicule, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        if ($r->user()->aRole('direction')) {
            return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('erreur', 'La direction ne peut pas détacher un chauffeur.');
        }
        $vehicule->chauffeurs()->detach($chauffeur->id);
        AuditLog::log('detachement', 'chauffeur', "Chauffeur #{$chauffeur->id}", "Détaché de véhicule {$vehicule->immatriculation}");
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur détaché.');
    }

    /* ===== FICHE RÉSUMÉ VÉHICULE (JSON) ===== */
    public function ficheVehicule(Request $r, Vehicule $vehicule)
    {
        $this->autoriser($r);
        $vehicule->load(['chauffeurs', 'pannes', 'recettes', 'maintenances', 'penalites']);

        [$moisDebut, $moisFin] = $this->bornesFiche($r);

        // KPIs mois en cours
        $recetteMois = (int) $vehicule->recettes->whereBetween('date', [$moisDebut, $moisFin])->sum('recette');
        $objectifMois = (int) $vehicule->recettes->whereBetween('date', [$moisDebut, $moisFin])->sum('objectif');
        $pointageMois = (int) $vehicule->recettes->whereBetween('date', [$moisDebut, $moisFin])->sum('pointage');
        $nbJoursSaisis = $vehicule->recettes->whereBetween('date', [$moisDebut, $moisFin])->count();
        $pannesOuvertes = $vehicule->pannes->whereIn('statut', ['declaree', 'en_diagnostic', 'en_intervention'])->count();
        $echeancesRetard = $vehicule->maintenances->filter(fn($m) => $m->prochaine_echeance && $m->prochaine_echeance < today())->count();
        $penalitesImpayees = $vehicule->penalites->where('statut', 'non_payee')->count();
        $penalitesMontant = (int) $vehicule->penalites->where('statut', 'non_payee')->sum('montant');

        // Recouvrement perçu ce mois (versements chauffeur)
        $recouvMois = 0;
        if (Schema::hasTable('versements_recouvrement') && Schema::hasTable('recouvrements')) {
            $recouvMois = (int) VersementRecouvrement::recetteChauffeurs()
                ->where('recouvrements.vehicule_id', $vehicule->id)
                ->whereBetween('versements_recouvrement.date_versement', [$moisDebut, $moisFin])
                ->sum('versements_recouvrement.montant');
        }
        $recetteMoisAvecRecouv = $recetteMois + $recouvMois;

        // Maintenance mois
        $maintenanceMois = (int) MaintenancePreventive::where('vehicule_id', $vehicule->id)->where('statut', 'realisee')
            ->whereBetween('date_realisation', [$moisDebut, $moisFin])->sum('cout');

        // Chauffeurs actuels
        $chauffeursActifs = $vehicule->chauffeurs->where('pivot.actif', true)->map(fn($c) => [
            'nom' => $c->nomComplet(), 'role' => $c->pivot->role === 'principal' ? 'Principal' : 'Alternant',
            'telephone' => $c->telephone, 'permis' => $c->numero_permis,
        ])->values();
        // Chauffeurs passés
        $chauffeursPasses = $vehicule->chauffeurs->where('pivot.actif', false)->map(fn($c) => [
            'nom' => $c->nomComplet(), 'role' => $c->pivot->role === 'principal' ? 'Principal' : 'Alternant',
        ])->values();

        // Historique recettes (30 dernières)
        $recettesHistorique = RecetteVehicule::where('vehicule_id', $vehicule->id)
            ->orderByDesc('date')->limit(30)->get()
            ->map(fn($rv) => [
                'date' => $rv->date->format('d/m/Y'),
                'chauffeur' => $rv->chauffeur ?? '—',
                'recette' => (int) $rv->recette, 'objectif' => (int) $rv->objectif,
                'ecart' => (int) $rv->ecart, 'pointage' => (int) ($rv->pointage ?? 0),
                'motif' => TypeParc::libelleMotif($rv->motif_absence),
            ]);

        // Pannes (20 dernières)
        $pannes = $vehicule->pannes->sortByDesc('date_declaration')->take(20)->map(fn($pa) => [
            'date' => $pa->date_declaration->format('d/m/Y'),
            'description' => mb_substr($pa->description ?? '', 0, 80),
            'cout' => (int) ($pa->cout_reel ?? 0),
            'urgence' => $pa->urgence,
            'statut' => $pa->statut,
        ])->values();

        // Pénalités (20 dernières)
        $penalites = collect();
        if (Schema::hasTable('penalites')) {
            $penalites = Penalite::where('vehicule_id', $vehicule->id)
                ->orderByDesc('date_infraction')->limit(20)->get()
                ->map(fn($p) => [
                    'date' => $p->date_infraction->format('d/m/Y'),
                    'type' => $p->type_penalite,
                    'chauffeur' => $p->chauffeur ?? '—',
                    'montant' => (int) $p->montant, 'statut' => $p->statut,
                ]);
        }

        // Maintenance préventive
        $maintenances = $vehicule->maintenances->sortByDesc('prochaine_echeance')->map(fn($m) => [
            'type' => $m->type_echeance,
            'echeance' => $m->prochaine_echeance?->format('d/m/Y') ?? '—',
            'retard' => $m->prochaine_echeance && $m->prochaine_echeance < today(),
            'derniere_realisation' => $m->derniere_realisation?->format('d/m/Y') ?? '—',
            'date_realisation' => $m->date_realisation?->format('d/m/Y') ?? null,
            'statut' => $m->statut ?? 'planifiee',
            'cout' => (int) ($m->cout ?? 0),
        ])->values();

        // Dépenses véhicule retirées (bureau uniquement, pas pertinent dans fiche véhicule)

        // Recouvrements liés au véhicule
        $recouvrements = Recouvrement::where('vehicule_id', $vehicule->id)
            ->orderByDesc('date_recette')->limit(20)->get()
            ->map(fn($rc) => [
                'date' => $rc->date_recette->format('d/m/Y'),
                'chauffeur' => $rc->chauffeur_nom ?? '—',
                'deficit' => (int) $rc->deficit,
                'recouvre' => (int) $rc->montant_recouvre,
                'solde' => (int) $rc->solde, 'statut' => $rc->statut,
            ]);
        $soldeRecouvr = (int) Recouvrement::where('vehicule_id', $vehicule->id)->where('statut', 'actif')->sum('solde');

        // Emprunts liés
        $emprunts = collect();
        if (Schema::hasTable('emprunts')) {
            $emprunts = Emprunt::where('vehicule_id', $vehicule->id)
                ->orderByDesc('date_emprunt')->limit(10)->get()
                ->map(fn($e) => [
                    'date' => $e->date_emprunt->format('d/m/Y'),
                    'preteur' => $e->preteur, 'montant' => (int) $e->montant,
                    'rembourse' => (int) $e->montant_rembourse,
                    'solde' => $e->soldeRestant(), 'statut' => $e->statutLabel(),
                ]);
        }

        return response()->json([
            'id' => $vehicule->id,
            'immatriculation' => $vehicule->immatriculation,
            'categorie' => $vehicule->categorie,
            'categorie_label' => $vehicule->categorieLabel(),
            'marque' => $vehicule->marque, 'modele' => $vehicule->modele,
            'statut' => $vehicule->statut, 'statut_label' => $vehicule->statutLabel(),
            'kilometrage' => $vehicule->kilometrage,
            'date_mise_en_service' => $vehicule->date_mise_en_service?->format('d/m/Y'),
            'observations' => $vehicule->observations,
            'objectif_recette' => $vehicule->objectif_recette,
            // KPIs
            'recette_mois' => $recetteMois, 'recouvrement_mois' => $recouvMois,
            'recette_mois_avec_recouv' => $recetteMoisAvecRecouv,
            'objectif_mois' => $objectifMois,
            'taux_recette' => $objectifMois > 0 ? round(($recetteMoisAvecRecouv / $objectifMois) * 100) : 0,
            'nb_jours' => $nbJoursSaisis, 'pointage_mois' => $pointageMois,
            'pannes_ouvertes' => $pannesOuvertes, 'pannes_total' => $vehicule->pannes->count(),
            'echeances_retard' => $echeancesRetard,
            'penalites_impayees' => $penalitesImpayees, 'penalites_montant' => $penalitesMontant,
            'maintenance_mois' => $maintenanceMois,
            'solde_recouvrement' => $soldeRecouvr,
            // Historiques
            'chauffeurs_actifs' => $chauffeursActifs, 'chauffeurs_passes' => $chauffeursPasses,
            'recettes_historique' => $recettesHistorique,
            'pannes' => $pannes, 'penalites' => $penalites,
            'maintenances' => $maintenances,
            'recouvrements' => $recouvrements, 'emprunts' => $emprunts,
        ]);
    }

    private function bornesFiche(Request $r): array
    {
        $ex = $r->get('exercice');
        if ($ex === 'tous') return [\Carbon\Carbon::create(2000, 1, 1), today()->endOfDay()];
        return $ex && preg_match('/^\d{4}-\d{2}$/', $ex) ? ExerciceParc::bornes($ex) : ExerciceParc::courant();
    }

    /* ===== FICHE RÉSUMÉ CHAUFFEUR (JSON) ===== */
    public function ficheChauffeur(Request $r, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        [$moisDebut, $moisFin] = $this->bornesFiche($r);
        $nomComplet = $chauffeur->nomComplet();

        // Véhicules affectés (actifs)
        $vehiculesActifs = $chauffeur->vehicules()
            ->wherePivot('actif', true)->get()
            ->map(fn($v) => [
                'immatriculation' => $v->immatriculation,
                'categorie' => $v->categorieLabel(),
                'role' => $v->pivot->role === 'principal' ? 'Principal' : 'Alternant',
            ]);
        // Véhicules passés (inactifs)
        $vehiculesPasses = $chauffeur->vehicules()
            ->wherePivot('actif', false)->get()
            ->map(fn($v) => [
                'immatriculation' => $v->immatriculation,
                'categorie' => $v->categorieLabel(),
                'role' => $v->pivot->role === 'principal' ? 'Principal' : 'Alternant',
            ]);

        // Recettes ce mois
        $recetteMois = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])->sum('recette');
        $objectifMois = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])->sum('objectif');
        $nbJoursSaisis = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])->count();
        $pointageMois = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])->sum('pointage');

        // Recouvrement perçu ce mois (versements chauffeur)
        $recouvMois = 0;
        if (Schema::hasTable('versements_recouvrement') && Schema::hasTable('recouvrements')) {
            $recouvMois = (int) VersementRecouvrement::recetteChauffeurs()
                ->where('recouvrements.chauffeur_nom', $nomComplet)
                ->whereBetween('versements_recouvrement.date_versement', [$moisDebut, $moisFin])
                ->sum('versements_recouvrement.montant');
        }
        $recetteTotale = (int) $recetteMois + $recouvMois;

        // Historique recettes (30 derniers jours)
        $recettesHistorique = RecetteVehicule::where('chauffeur', $nomComplet)
            ->orderByDesc('date')->limit(30)->get()
            ->map(fn($rv) => [
                'date' => $rv->date->format('d/m/Y'),
                'vehicule' => $rv->vehicule?->immatriculation ?? '—',
                'recette' => (int) $rv->recette,
                'objectif' => (int) $rv->objectif,
                'ecart' => (int) $rv->ecart,
                'pointage' => (int) ($rv->pointage ?? 0),
                'motif' => TypeParc::libelleMotif($rv->motif_absence),
            ]);

        // Pénalités (toutes, triées)
        $penalites = collect();
        if (Schema::hasTable('penalites')) {
            $penalites = Penalite::where('chauffeur', $nomComplet)
                ->orderByDesc('date_infraction')->limit(20)->get()
                ->map(fn($p) => [
                    'date' => $p->date_infraction->format('d/m/Y'),
                    'type' => $p->type_penalite,
                    'montant' => (int) $p->montant,
                    'statut' => $p->statut,
                    'vehicule' => $p->vehicule?->immatriculation ?? '—',
                ]);
        }
        $penalitesImpayees = Schema::hasTable('penalites')
            ? Penalite::where('chauffeur', $nomComplet)->where('statut', 'non_payee')->count() : 0;
        $penalitesMontant = Schema::hasTable('penalites')
            ? (int) Penalite::where('chauffeur', $nomComplet)->where('statut', 'non_payee')->sum('montant') : 0;

        // Recouvrements
        $recouvrements = Recouvrement::where('chauffeur_nom', $nomComplet)
            ->orderByDesc('date_recette')->limit(20)->get()
            ->map(fn($rc) => [
                'date' => $rc->date_recette->format('d/m/Y'),
                'deficit' => (int) $rc->deficit,
                'recouvre' => (int) $rc->montant_recouvre,
                'solde' => (int) $rc->solde,
                'statut' => $rc->statut,
            ]);
        $recouvrActifs = Recouvrement::where('chauffeur_nom', $nomComplet)->where('statut', 'actif')->get();
        $soldeRecouvr  = (int) $recouvrActifs->sum('solde');

        // Pannes liées aux véhicules du chauffeur
        $vehiculeIds = $chauffeur->vehicules->pluck('id');
        $pannes = collect();
        if ($vehiculeIds->isNotEmpty()) {
            $pannes = Panne::whereIn('vehicule_id', $vehiculeIds)
                ->orderByDesc('date_declaration')->limit(15)->get()
                ->map(fn($pa) => [
                    'date' => $pa->date_declaration->format('d/m/Y'),
                    'vehicule' => $pa->vehicule?->immatriculation ?? '—',
                    'description' => mb_substr($pa->description ?? '', 0, 60),
                    'cout' => (int) ($pa->cout_reel ?? 0),
                    'statut' => $pa->statut,
                ]);
        }

        // Score global : jours atteints / jours saisis
        $joursAtteints = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])
            ->whereColumn('recette', '>=', 'objectif')->count();

        return response()->json([
            'nom' => $nomComplet,
            'statut' => $chauffeur->statut,
            'telephone' => $chauffeur->telephone,
            'permis' => $chauffeur->numero_permis,
            'date_embauche' => $chauffeur->date_embauche ? \Carbon\Carbon::parse($chauffeur->date_embauche)->format('d/m/Y') : null,
            'observations' => $chauffeur->observations,
            'vehicules_actifs' => $vehiculesActifs,
            'vehicules_passes' => $vehiculesPasses,
            'recette_mois' => (int) $recetteMois,
            'recouvrement_mois' => $recouvMois,
            'recette_totale' => $recetteTotale,
            'objectif_mois' => (int) $objectifMois,
            'taux_recette' => $objectifMois > 0 ? round(($recetteTotale / $objectifMois) * 100) : 0,
            'jours_saisis' => $nbJoursSaisis,
            'jours_atteints' => $joursAtteints,
            'pointage_mois' => (int) $pointageMois,
            'recettes_historique' => $recettesHistorique,
            'penalites' => $penalites,
            'penalites_impayees' => $penalitesImpayees,
            'penalites_montant' => $penalitesMontant,
            'recouvrements' => $recouvrements,
            'recouvrements_actifs' => $recouvrActifs->count(),
            'solde_recouvrement' => $soldeRecouvr,
            'pannes' => $pannes,
        ]);
    }

    /* ===== KPI ===== */
    /** Éléments d'exercices antérieurs encore ouverts, signalés dans les onglets. */
    private function reportsAnterieurs(\Carbon\Carbon $debut): array
    {
        $avant = fn($q, string $col) => $q->where($col, '<', $debut);
        $res = [
            'pannes' => ['nb' => $avant(Panne::query(), 'date_declaration')->whereIn('statut', ['declaree', 'en_diagnostic', 'en_intervention'])->count(), 'libelle' => 'panne(s) non clôturée(s)'],
            'maint' => ['nb' => MaintenancePreventive::where(fn($s) => $s->whereNull('statut')->orWhere('statut', '!=', 'realisee'))->where('prochaine_echeance', '<', $debut)->count(), 'libelle' => 'maintenance(s) en retard'],
        ];
        if (Schema::hasTable('penalites')) {
            $q = $avant(Penalite::query(), 'date_infraction')->where('statut', '!=', 'payee');
            $res['penal'] = ['nb' => (clone $q)->count(), 'libelle' => 'pénalité(s) non soldée(s)', 'montant' => (int) $q->sum(DB::raw('montant - COALESCE(montant_paye, 0)'))];
        }
        if (Schema::hasTable('recouvrements')) {
            $q = $avant(Recouvrement::query(), 'date_recette')->where('statut', 'actif');
            $res['recouv'] = ['nb' => (clone $q)->count(), 'libelle' => 'recouvrement(s) encore actif(s)', 'montant' => (int) $q->sum('solde')];
        }
        if (Schema::hasTable('emprunts')) {
            $q = $avant(Emprunt::query(), 'date_emprunt')->where('statut', 'en_cours');
            $res['emprunts'] = ['nb' => (clone $q)->count(), 'libelle' => 'emprunt(s) en cours', 'montant' => (int) $q->sum(DB::raw('montant - montant_rembourse'))];
        }
        if (Schema::hasTable('prets_accordes')) {
            $q = $avant(PretAccorde::query(), 'date_pret')->whereNotIn('statut', ['rembourse', 'irrécouvrable']);
            $res['prets'] = ['nb' => (clone $q)->count(), 'libelle' => 'prêt(s) non remboursé(s)', 'montant' => (int) $q->sum(DB::raw('montant - COALESCE(montant_rembourse, 0)'))];
        }
        return array_filter($res, fn($x) => $x['nb'] > 0);
    }

    private function calculerKpi($vehicules, ?\Carbon\Carbon $debut = null, ?\Carbon\Carbon $fin = null): array
    {
        $actifs = $vehicules->where('statut', 'en_service')->count();
        $taxis = $vehicules->where('categorie', 'taxi')->count();
        $vtcs = $vehicules->where('categorie', 'vtc')->count();
        // Sans borne (« toutes les périodes ») : de l'origine à aujourd'hui
        $moisDebut = $debut ?? \Carbon\Carbon::create(2000, 1, 1);
        $moisFin = $fin ?? today()->endOfDay();

        $pannesMois = Panne::whereBetween('date_declaration', [$moisDebut, $moisFin])->count();
        $pannesOuvertes = Panne::whereIn('statut', ['declaree', 'en_diagnostic', 'en_intervention'])->count();
        $coutMois = (int) Panne::whereBetween('date_declaration', [$moisDebut, $moisFin])->sum('cout_reel');
        $delaiMoyen = Panne::whereBetween('date_declaration', [$moisDebut, $moisFin])
            ->whereNotNull('delai_resolution')->avg('delai_resolution');

        $recetteMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('recette');
        $objectifMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('objectif');
        $pointageMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('pointage');

        // Recouvrement perçu ce mois (versements chauffeur)
        $recouvMois = 0;
        if (Schema::hasTable('versements_recouvrement') && Schema::hasTable('recouvrements')) {
            $recouvMois = (int) VersementRecouvrement::recetteChauffeurs()
                ->whereBetween('versements_recouvrement.date_versement', [$moisDebut, $moisFin])
                ->sum('versements_recouvrement.montant');
        }
        $recetteTotale = $recetteMois + $recouvMois;
        $tauxRecette = $objectifMois > 0 ? round(($recetteTotale / $objectifMois) * 100) : 0;

        $echeancesRetard = MaintenancePreventive::whereNotNull('prochaine_echeance')
            ->whereDate('prochaine_echeance', '<', today())->count();

        $penalitesMois = 0;
        $penalitesNonPayees = 0;
        if (Schema::hasTable('penalites')) {
            $penalitesMois = (int) Penalite::whereBetween('date_infraction', [$moisDebut, $moisFin])->sum('montant');
            $penalitesNonPayees = Penalite::where('statut', 'non_payee')->count();
        }

        return [
            'actifs' => $actifs,
            'total' => $vehicules->count(),
            'taxis' => $taxis,
            'vtcs' => $vtcs,
            'pannes_mois' => $pannesMois,
            'pannes_ouvertes' => $pannesOuvertes,
            'cout_mois' => $coutMois,
            'delai_moyen' => $delaiMoyen !== null ? round($delaiMoyen, 1) : 0,
            'recette_mois' => $recetteMois,
            'recouvrement_mois' => $recouvMois,
            'recette_totale' => $recetteTotale,
            'pointage_mois' => $pointageMois,
            'objectif_mois' => $objectifMois,
            'taux_recette' => $tauxRecette,
            'echeances_retard' => $echeancesRetard,
            'penalites_mois' => $penalitesMois,
            'penalites_non_payees' => $penalitesNonPayees,
        ];
    }

    /* ═══════════════════════════════════════════════════════
     *  RECOUVREMENTS
     * ═══════════════════════════════════════════════════════ */

    public function storeVersement(Request $r, Recouvrement $recouvrement)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'montant' => 'required|integer|min:1',
            'date_versement' => 'required|date',
            'mode_paiement' => 'required|in:especes,mobile_money,cheque,virement,autre',
            'source_paiement' => 'required|in:chauffeur,entreprise',
            'observations' => 'nullable|string|max:500',
        ]);

        try {
            if ($data['montant'] > $recouvrement->solde) {
                return back()->withErrors(['Le montant dépasse le solde restant (' . number_format($recouvrement->solde, 0, ',', ' ') . ' F).']);
            }

            $data['recouvrement_id'] = $recouvrement->id;
            $data['recu_par'] = $r->user()->name;
            $data['source_paiement'] = $data['source_paiement'] ?? 'chauffeur';

            // Si entreprise paie → créer une dépense liée
            if ($data['source_paiement'] === 'entreprise') {
                $typeRecouvr = ($recouvrement->type_recouvrement ?? 'recette') === 'penalite' ? 'Pénalité' : 'Déficit recette';
                $dep = Depense::create([
                    'date_depense' => $data['date_versement'],
                    'type_depense' => 'recouvrement',
                    'vehicule_id' => $recouvrement->vehicule_id,
                    'montant' => $data['montant'],
                    'commentaire' => "Recouvrement pris en charge par l'entreprise : " . mb_strtolower($typeRecouvr) . " de {$recouvrement->chauffeur_nom} du " . $recouvrement->date_recette->format('d/m/Y'),
                    'cree_par' => $r->user()->name,
                ]);
                $data['depense_id'] = $dep->id;
            }

            $vers = VersementRecouvrement::create($data);
            $recouvrement->recalculerSolde();
            $sourceLabel = $data['source_paiement'] === 'entreprise' ? ' (pris en charge par l\'entreprise)' : '';
            AuditLog::log('creation', 'versement', "Versement #{$vers->id}", "Recouvrement #{$recouvrement->id}, Montant: {$data['montant']} F{$sourceLabel}");

            return redirect()->route('parc.index', ['tab' => 'recouv'])
                ->with('succes', 'Versement de ' . number_format($data['montant'], 0, ',', ' ') . ' F enregistré.' . $sourceLabel);
        } catch (\Exception $e) {
            return redirect()->route('parc.index', ['tab' => 'recouv'])
                ->withErrors(['Erreur : ' . $e->getMessage()]);
        }
    }

    public function annulerRecouvrement(Request $r, Recouvrement $recouvrement)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent annuler.');
        $r->validate(['motif_annulation' => 'required|string|min:5|max:500']);
        if ($recouvrement->montant_recouvre > 0) {
            return back()->withErrors(['Impossible d\'annuler : des versements ont déjà été effectués.']);
        }
        $recouvrement->update([
            'statut' => 'annule',
            'motif_annulation' => $r->motif_annulation,
            'annule_par' => auth()->user()->name,
            'annule_le' => now(),
        ]);
        AuditLog::log('annulation', 'recouvrement', "Recouvrement #{$recouvrement->id}", "Chauffeur: {$recouvrement->chauffeur}, Montant: {$recouvrement->montant_initial} F, Motif: {$r->motif_annulation}");
        return redirect()->route('parc.index', ['tab' => 'recouv'])->with('succes', 'Recouvrement annulé.');
    }

    public function destroyRecouvrement(Request $r, Recouvrement $recouvrement)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5|max:500']);

        $info = "Chauffeur: {$recouvrement->chauffeur_nom}, Déficit: {$recouvrement->deficit} F, Statut: {$recouvrement->statut}";
        AuditLog::log('suppression', 'recouvrement', "Recouvrement #{$recouvrement->id}", "{$info}, Motif: {$r->motif_suppression}");

        // Supprimer les versements liés
        $recouvrement->versements()->delete();
        $recouvrement->delete();

        return redirect()->route('parc.index', ['tab' => 'recouv'])->with('succes', 'Recouvrement supprimé.');
    }

    /* ═══════════════════════════ DÉPENSES ═══════════════════════════ */

    public function storeDepense(Request $r)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403, 'La direction ne peut pas saisir de dépenses.');
        $v = $r->validate([
            'date_depense'   => 'required|date',
            'type_depense'   => 'required|string|max:100',
            'fournisseur'    => 'nullable|string|max:150',
            'montant'        => 'required|integer|min:1',
            'moyen_paiement' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'caisse_source'  => 'nullable|string|max:30',
            'commentaire'    => 'nullable|string|max:500',
        ]);
        $v['cree_par'] = auth()->user()->name;
        $v['caisse_source'] = $v['caisse_source'] ?? 'generale';
        $v['vehicule_id'] = null; // charge de l'entreprise, non imputée à un véhicule
        $dep = Depense::create($v);

        // Mouvement caisse sortie
        if (Schema::hasTable('mouvements_caisse')) {
            MouvementCaisse::create([
                'caisse_code' => $dep->caisse_source ?? 'generale',
                'sens' => 'sortie', 'montant' => $dep->montant,
                'date_mouvement' => $dep->date_depense,
                'source_type' => 'depense', 'source_id' => $dep->id,
                'libelle' => 'Dépense — ' . $dep->typeLibelle(),
                'cree_par' => auth()->user()->name,
            ]);
        }

        AuditLog::log('creation', 'depense', "Dépense #{$dep->id}", "Type: {$dep->type_depense}, Montant: {$dep->montant} F");
        return redirect()->route('parc.index', ['tab' => 'depenses'])->with('succes', 'Dépense enregistrée.');
    }

    public function updateDepense(Request $r, Depense $depense)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403);
        $v = $r->validate([
            'date_depense'   => 'required|date',
            'type_depense'   => 'required|string|max:100',
            'fournisseur'    => 'nullable|string|max:150',
            'montant'        => 'required|integer|min:1',
            'moyen_paiement' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'caisse_source'  => 'nullable|string|max:30',
            'commentaire'    => 'nullable|string|max:500',
        ]);
        if ($depense->type_depense !== 'maintenance') $v['vehicule_id'] = null;
        $depense->update($v);
        AuditLog::log('modification', 'depense', "Dépense #{$depense->id}", "Type: {$depense->type_depense}, Montant: {$depense->montant} F");
        return redirect()->route('parc.index', ['tab' => 'depenses'])->with('succes', 'Dépense modifiée.');
    }

    public function destroyDepense(Request $r, Depense $depense)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'depense', "Dépense #{$depense->id}", "Type: {$depense->type_depense}, Montant: {$depense->montant} F, Motif: " . $r->motif_suppression);
        $depense->delete();
        return redirect()->route('parc.index', ['tab' => 'depenses'])->with('succes', 'Dépense supprimée.');
    }

    /* ═══════════════════════════ EMPRUNTS ═══════════════════════════ */

    public function storeEmprunt(Request $r)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403, 'La direction ne peut pas saisir d\'emprunts.');
        $data = $r->validate([
            'montant'      => 'required|integer|min:1',
            'date_emprunt' => 'required|date',
            'preteur'      => 'required|string|max:150',
            'motif'        => 'nullable|string|max:500',
            'source_type'  => 'nullable|in:panne,penalite,maintenance,autre',
            'vehicule_id'  => 'nullable|exists:vehicules,id',
        ]);
        $data['source_type'] = $data['source_type'] ?? 'autre';
        $data['cree_par'] = auth()->user()->name;

        $emp = Emprunt::create($data);
        AuditLog::log('creation', 'emprunt', "Emprunt #{$emp->id}", "Montant: {$emp->montant} F, Prêteur: {$emp->preteur}");
        return redirect()->route('parc.index', ['tab' => 'emprunts'])->with('succes', 'Emprunt enregistré.');
    }

    public function storeRemboursement(Request $r, Emprunt $emprunt)
    {
        $this->autoriser($r);
        $r->validate([
            'montant'             => 'required|integer|min:1|max:' . $emprunt->soldeRestant(),
            'date_remboursement'  => 'required|date',
            'mode_paiement'       => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'observations'        => 'nullable|string|max:500',
        ]);

        RemboursementEmprunt::create([
            'emprunt_id'         => $emprunt->id,
            'montant'            => $r->montant,
            'date_remboursement' => $r->date_remboursement,
            'mode_paiement'      => $r->mode_paiement,
            'observations'       => $r->observations,
            'cree_par'           => auth()->user()->name,
        ]);

        $emprunt->recalculerRemboursement();
        AuditLog::log('creation', 'remboursement_emprunt', "Emprunt #{$emprunt->id}", "Versement: {$r->montant} F, Solde: {$emprunt->soldeRestant()} F");
        return redirect()->route('parc.index', ['tab' => 'emprunts'])->with('succes', 'Remboursement enregistré. Solde : ' . number_format($emprunt->soldeRestant(), 0, ',', ' ') . ' F');
    }

    public function destroyEmprunt(Request $r, Emprunt $emprunt)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'emprunt', "Emprunt #{$emprunt->id}", "Prêteur: {$emprunt->preteur}, Montant: {$emprunt->montant} F, Motif: " . $r->motif_suppression);
        $emprunt->delete();
        return redirect()->route('parc.index', ['tab' => 'emprunts'])->with('succes', 'Emprunt supprimé.');
    }

    /* ═══════════════════════════════════════════════════════
     *  PRÊTS ACCORDÉS
     * ═══════════════════════════════════════════════════════ */

    public function storePret(Request $r)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403);
        $data = $r->validate([
            'montant'        => 'required|integer|min:1',
            'date_pret'      => 'required|date',
            'beneficiaire'   => 'required|string|max:150',
            'chauffeur_id'   => 'nullable|exists:chauffeurs,id',
            'motif'          => 'nullable|string|max:500',
            'caisse_source'  => 'nullable|string|max:30',
            'date_echeance'  => 'nullable|date',
            'modalites'      => 'nullable|string|max:500',
        ]);
        $data['caisse_source'] = $data['caisse_source'] ?? 'generale';
        $data['cree_par'] = auth()->user()->name;
        $data['reference'] = 'PRT-' . date('Ymd') . '-' . str_pad(PretAccorde::whereDate('date_pret', today())->count() + 1, 3, '0', STR_PAD_LEFT);

        $pret = PretAccorde::create($data);

        // Mouvement caisse sortie
        if (Schema::hasTable('mouvements_caisse')) {
            MouvementCaisse::create([
                'caisse_code' => $pret->caisse_source,
                'sens' => 'sortie', 'montant' => $pret->montant,
                'date_mouvement' => $pret->date_pret,
                'source_type' => 'pret_accorde', 'source_id' => $pret->id,
                'libelle' => "Prêt accordé — {$pret->beneficiaire}",
                'reference' => $pret->reference,
                'cree_par' => auth()->user()->name,
            ]);
        }

        AuditLog::log('creation', 'pret_accorde', "Prêt #{$pret->id}", "Montant: {$pret->montant} F, Bénéficiaire: {$pret->beneficiaire}");
        return redirect()->route('parc.index', ['tab' => 'prets'])->with('succes', 'Prêt accordé enregistré.');
    }

    public function storeRemboursementPret(Request $r, PretAccorde $pret)
    {
        $this->autoriser($r);
        $r->validate([
            'montant'            => 'required|integer|min:1|max:' . $pret->soldeRestant(),
            'date_remboursement' => 'required|date',
            'mode_paiement'      => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'caisse_destination' => 'nullable|string|max:30',
            'observations'       => 'nullable|string|max:500',
        ]);

        $rb = RemboursementPret::create([
            'pret_accorde_id'    => $pret->id,
            'montant'            => $r->montant,
            'date_remboursement' => $r->date_remboursement,
            'mode_paiement'      => $r->mode_paiement,
            'caisse_destination' => $r->caisse_destination ?? 'generale',
            'observations'       => $r->observations,
            'cree_par'           => auth()->user()->name,
        ]);

        $pret->recalculerRemboursement();

        // Mouvement caisse entrée
        if (Schema::hasTable('mouvements_caisse')) {
            MouvementCaisse::create([
                'caisse_code' => $rb->caisse_destination,
                'sens' => 'entree', 'montant' => $rb->montant,
                'date_mouvement' => $rb->date_remboursement,
                'source_type' => 'remboursement_pret', 'source_id' => $rb->id,
                'libelle' => "Remboursement prêt — {$pret->beneficiaire}",
                'reference' => $pret->reference,
                'cree_par' => auth()->user()->name,
            ]);
        }

        AuditLog::log('creation', 'remboursement_pret', "Prêt #{$pret->id}", "Versement: {$r->montant} F, Solde: {$pret->soldeRestant()} F");
        return redirect()->route('parc.index', ['tab' => 'prets'])->with('succes', 'Remboursement enregistré. Solde : ' . number_format($pret->soldeRestant(), 0, ',', ' ') . ' F');
    }

    public function destroyPret(Request $r, PretAccorde $pret)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'pret_accorde', "Prêt #{$pret->id}", "Bénéficiaire: {$pret->beneficiaire}, Montant: {$pret->montant} F, Motif: " . $r->motif_suppression);
        $pret->delete();
        return redirect()->route('parc.index', ['tab' => 'prets'])->with('succes', 'Prêt supprimé.');
    }

    /* ═══════════════════════════════════════════════════════
     *  AUTRES RESSOURCES
     * ═══════════════════════════════════════════════════════ */

    public function storeAutreRessource(Request $r)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403);
        $data = $r->validate([
            'date_ressource'     => 'required|date',
            'type_ressource'     => 'required|string|max:40',
            'montant'            => 'required|integer|min:1',
            'source'             => 'nullable|string|max:150',
            'description'        => 'nullable|string|max:500',
            'caisse_destination' => 'nullable|string|max:30',
            'vehicule_id'        => 'nullable|exists:vehicules,id',
        ]);
        $data['caisse_destination'] = $data['caisse_destination'] ?? 'generale';
        $data['cree_par'] = auth()->user()->name;

        $res = AutreRessource::create($data);

        // Mouvement caisse entrée
        if (Schema::hasTable('mouvements_caisse')) {
            MouvementCaisse::create([
                'caisse_code' => $res->caisse_destination,
                'sens' => 'entree', 'montant' => $res->montant,
                'date_mouvement' => $res->date_ressource,
                'source_type' => 'autre_ressource', 'source_id' => $res->id,
                'libelle' => 'Autre ressource — ' . $res->typeLibelle(),
                'cree_par' => auth()->user()->name,
            ]);
        }

        AuditLog::log('creation', 'autre_ressource', "Ressource #{$res->id}", "Type: {$res->type_ressource}, Montant: {$res->montant} F");
        return redirect()->route('parc.index', ['tab' => 'ressources'])->with('succes', 'Ressource enregistrée.');
    }

    public function updateAutreRessource(Request $r, AutreRessource $ressource)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403);
        $data = $r->validate([
            'date_ressource'     => 'required|date',
            'type_ressource'     => 'required|string|max:40',
            'montant'            => 'required|integer|min:1',
            'source'             => 'nullable|string|max:150',
            'description'        => 'nullable|string|max:500',
            'caisse_destination' => 'nullable|string|max:30',
            'vehicule_id'        => 'nullable|exists:vehicules,id',
        ]);
        if (!$r->filled('vehicule_id')) $data['vehicule_id'] = null;
        $ressource->update($data);
        AuditLog::log('modification', 'autre_ressource', "Ressource #{$ressource->id}", "Type: {$ressource->type_ressource}, Montant: {$ressource->montant} F");
        return redirect()->route('parc.index', ['tab' => 'ressources'])->with('succes', 'Ressource modifiée.');
    }

    public function destroyAutreRessource(Request $r, AutreRessource $ressource)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'autre_ressource', "Ressource #{$ressource->id}", "Montant: {$ressource->montant} F, Motif: " . $r->motif_suppression);
        $ressource->delete();
        return redirect()->route('parc.index', ['tab' => 'ressources'])->with('succes', 'Ressource supprimée.');
    }

    /* ═══════════════════════════════════════════════════════
     *  WORKFLOW VALIDATION MOTIF (statut_motif)
     * ═══════════════════════════════════════════════════════ */

    public function validerMotif(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        $recette->update([
            'statut_motif' => 'valide',
            'justification_valable' => true,
        ]);

        // Si un recouvrement actif existe pour cette recette et aucun versement, le supprimer
        if (Schema::hasTable('recouvrements')) {
            $rec = Recouvrement::where('recette_vehicule_id', $recette->id)->where('statut', 'actif')->first();
            if ($rec && $rec->montant_recouvre == 0) {
                $rec->delete();
                AuditLog::log('suppression', 'recouvrement', "Recouvrement #{$rec->id}", "Supprimé suite à validation du motif");
            }
        }

        AuditLog::log('validation', 'motif_recette', "Recette #{$recette->id}", "Motif validé par " . auth()->user()->name);
        return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $recette->date->format('Y-m-d')])->with('succes', 'Motif validé.');
    }

    public function invaliderMotif(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        $recette->update([
            'statut_motif' => 'invalide',
            'justification_valable' => false,
        ]);

        // Créer le recouvrement si pas déjà existant
        if (Schema::hasTable('recouvrements') && $recette->chauffeur) {
            $deficit = max(0, ($recette->objectif ?? 0) - ($recette->recette ?? 0));
            if ($deficit > 0) {
                $existe = Recouvrement::where('recette_vehicule_id', $recette->id)->where('statut', 'actif')->exists();
                if (!$existe) {
                    $chauffeurId = null;
                    if (Schema::hasTable('chauffeurs')) {
                        $ch = Chauffeur::whereRaw("CONCAT(prenom,' ',nom) = ?", [$recette->chauffeur])
                            ->orWhereRaw("CONCAT(nom,' ',prenom) = ?", [$recette->chauffeur])->first();
                        $chauffeurId = $ch?->id;
                    }
                    Recouvrement::create([
                        'recette_vehicule_id' => $recette->id,
                        'chauffeur_id' => $chauffeurId,
                        'chauffeur_nom' => $recette->chauffeur,
                        'vehicule_id' => $recette->vehicule_id,
                        'date_recette' => $recette->date,
                        'montant_verse' => $recette->recette ?? 0,
                        'objectif' => $recette->objectif ?? 0,
                        'deficit' => $deficit,
                        'montant_recouvre' => 0,
                        'solde' => $deficit,
                        'statut' => 'actif',
                        'justification_valable' => false,
                        'motif' => $recette->motif_absence,
                        'cree_par' => auth()->user()->name,
                    ]);
                    AuditLog::log('creation', 'recouvrement', $recette->chauffeur, "Déficit {$deficit} F — motif invalidé");
                }
            }
        }

        AuditLog::log('invalidation', 'motif_recette', "Recette #{$recette->id}", "Motif invalidé par " . auth()->user()->name);
        return redirect()->route('parc.index', ['tab' => 'recettes', 'date_recette' => $recette->date->format('Y-m-d')])->with('succes', 'Motif invalidé — recouvrement créé.');
    }
}
