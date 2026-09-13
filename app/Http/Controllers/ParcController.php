<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, Chauffeur, Depense, Emprunt, JourFerie, MaintenancePreventive, Panne, PaiementPenalite, ParametreParc, Penalite, Recouvrement, RecetteVehicule, RemboursementEmprunt, TypeParc, User, Vehicule, VersementRecouvrement};
use App\Services\AlerteParcService;
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

        // Toutes les données chargées (sections empilées)
        $pannes = Panne::with(['vehicule', 'emprunt'])->orderByDesc('date_declaration')->get();
        $recettes = RecetteVehicule::with('vehicule')->orderByDesc('date')->limit(200)->get();
        $maintenances = MaintenancePreventive::with('vehicule')->orderBy('prochaine_echeance')->get();
        $penalites = Penalite::with(['vehicule', 'paiements'])->orderByDesc('date_infraction')->get();

        $kpi = $this->calculerKpi($vehicules);
        $typesPenalite = TypeParc::penalites();
        $typesMaintenance = TypeParc::maintenances();
        $motifsAbsence = TypeParc::motifsAbsence();

        // --- Grille journalière recettes ---
        $dateJour = $r->get('date_recette', date('Y-m-d', strtotime('-1 day')));
        $grilleJour = $this->construireGrilleJour($vehicules, $dateJour, $collaborateurs);

        // --- Alertes jours manquants (7 derniers jours) ---
        $alertesManquants = $this->detecterJoursManquants($vehicules);

        // --- Recouvrements ---
        $recouvrements = Schema::hasTable('recouvrements')
            ? Recouvrement::with(['vehicule', 'versements'])->orderByDesc('date_recette')->get()
            : collect();

        // --- Dépenses (hors maintenance = dépense véhicule, pas "autre dépense") ---
        $depenses = Schema::hasTable('depenses')
            ? Depense::with('vehicule')->where('type_depense', '!=', 'maintenance')->orderByDesc('date_depense')->get()
            : collect();
        $typesDepense = TypeParc::depenses();

        // --- Emprunts ---
        $emprunts = Schema::hasTable('emprunts')
            ? Emprunt::with(['vehicule', 'remboursements'])->orderByDesc('date_emprunt')->get()
            : collect();

        return view('parc.index', compact(
            'vehicules', 'chauffeurs', 'collaborateurs', 'pannes', 'recettes',
            'maintenances', 'penalites', 'kpi', 'typesPenalite', 'typesMaintenance',
            'motifsAbsence', 'dateJour', 'grilleJour', 'alertesManquants', 'recouvrements',
            'depenses', 'typesDepense', 'emprunts'
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
                'chauffeur' => $v->chauffeur_affecte ?? '',
                'chauffeur_actif' => true,
                'vehicule' => $v,
                'objectif' => $estRepos ? 0 : $objectif,
                'est_repos' => $estRepos,
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
            'marque' => 'required|string|max:50',
            'modele' => 'required|string|max:50',
            'date_mise_en_service' => 'required|date',
            'chauffeur_affecte' => 'nullable|string|max:100',
            'kilometrage' => 'required|integer|min:0',
            'statut' => 'required|in:en_service,immobilise,reforme',
            'objectif_recette' => 'nullable|integer|min:0',
            'observations' => 'nullable|string|max:500',
        ]);

        $vehicule = Vehicule::create(array_merge(
            $r->only(['immatriculation', 'categorie', 'marque', 'modele', 'date_mise_en_service',
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
            'marque' => 'required|string|max:50',
            'modele' => 'required|string|max:50',
            'date_mise_en_service' => 'required|date',
            'chauffeur_affecte' => 'nullable|string|max:100',
            'kilometrage' => 'required|integer|min:0',
            'statut' => 'required|in:en_service,immobilise,reforme',
            'objectif_recette' => 'nullable|integer|min:0',
            'observations' => 'nullable|string|max:500',
        ]);

        $vehicule->update(array_merge(
            $r->only(['immatriculation', 'categorie', 'marque', 'modele', 'date_mise_en_service',
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
            'date_declaration' => 'required|date',
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

    /* ===== RECETTES CRUD ===== */
    public function storeRecette(Request $r)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'date' => 'required|date',
            'vehicule_id' => 'required|exists:vehicules,id',
            'chauffeur' => 'required|string|max:100',
            'recette' => 'required|integer|min:0',
            'mode_remise' => 'required|in:especes,mobile_money,cheque,virement,autre',
            'controle_par' => 'required|string|max:100',
            'observations' => 'nullable|string|max:500',
            'motif_absence' => 'nullable|string|max:80',
        ]);

        // Recette = 0 → motif obligatoire
        if (($data['recette'] ?? 0) == 0 && empty($data['motif_absence'])) {
            return back()->withInput()->withErrors(['motif_absence' => 'Le motif est obligatoire lorsque la recette est à 0.']);
        }
        // Recette > 0 → vider le motif
        if (($data['recette'] ?? 0) > 0) {
            $data['motif_absence'] = null;
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
                return back()->withInput()->withErrors(['vehicule_id' => 'Ce véhicule est déjà utilisé par ' . $conflit->chauffeur . ' le ' . $conflit->date->format('d/m/Y')]);
            }

            $categorie = $v->categorie ?? 'taxi';
            $objectifParam = ParametreParc::objectifRecette($categorie, $data['type_jour']);
            $data['objectif'] = $objectifParam > 0 ? $objectifParam : ($v->objectif_recette ?? 0);
            $data['ecart'] = $data['recette'] - $data['objectif'];

            RecetteVehicule::create($data);
            return redirect()->route('parc.index', ['tab' => 'recettes'])->with('succes', 'Recette enregistrée.');
        } catch (\Exception $e) {
            return redirect()->route('parc.index', ['tab' => 'recettes'])
                ->withErrors(['Erreur : ' . $e->getMessage()]);
        }
    }

    public function updateRecette(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'date' => 'required|date',
            'vehicule_id' => 'required|exists:vehicules,id',
            'chauffeur' => 'required|string|max:100',
            'recette' => 'required|integer|min:0',
            'mode_remise' => 'required|in:especes,mobile_money,cheque,virement,autre',
            'controle_par' => 'required|string|max:100',
            'observations' => 'nullable|string|max:500',
            'motif_absence' => 'nullable|string|max:80',
        ]);

        if (($data['recette'] ?? 0) == 0 && empty($data['motif_absence'])) {
            return back()->withInput()->withErrors(['motif_absence' => 'Le motif est obligatoire lorsque la recette est à 0.']);
        }
        if (($data['recette'] ?? 0) > 0) {
            $data['motif_absence'] = null;
        }

        try {
            $v = Vehicule::find($data['vehicule_id']);
            $data['type_jour'] = $this->detecterTypeJour($data['date']);

            // Bloquer si un autre chauffeur a déjà une recette pour ce véhicule ce jour (hors lui-même)
            $conflit = RecetteVehicule::where('vehicule_id', $data['vehicule_id'])
                ->whereDate('date', $data['date'])
                ->where('chauffeur', '!=', $data['chauffeur'] ?? '')
                ->where('id', '!=', $recette->id)
                ->first();
            if ($conflit) {
                return back()->withInput()->withErrors(['vehicule_id' => 'Ce véhicule est déjà utilisé par ' . $conflit->chauffeur . ' le ' . $conflit->date->format('d/m/Y')]);
            }

            $categorie = $v->categorie ?? 'taxi';
            $objectifParam = ParametreParc::objectifRecette($categorie, $data['type_jour']);
            $data['objectif'] = $objectifParam > 0 ? $objectifParam : ($v->objectif_recette ?? 0);
            $data['ecart'] = $data['recette'] - $data['objectif'];

            $recette->update($data);
            return redirect()->route('parc.index', ['tab' => 'recettes'])->with('succes', 'Recette mise à jour.');
        } catch (\Exception $e) {
            return redirect()->route('parc.index', ['tab' => 'recettes'])
                ->withErrors(['Erreur : ' . $e->getMessage()]);
        }
    }

    public function destroyRecette(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'recette', "Recette #{$recette->id}", "Motif: " . $r->motif_suppression);
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
        $erreurs = [];
        $nbCrees = 0;
        $nbMaj = 0;

        try {
            foreach ($lignes as $i => $ligne) {
                $vehiculeId = $ligne['vehicule_id'] ?? null;
                if (!$vehiculeId) continue;

                $v = Vehicule::find($vehiculeId);
                if (!$v) continue;

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
            'derniere_realisation' => 'required|date',
            'prochaine_echeance' => 'required|date',
            'observations' => 'nullable|string|max:500',
        ]);

        $m = MaintenancePreventive::create($r->only([
            'vehicule_id', 'type_echeance', 'derniere_realisation', 'prochaine_echeance', 'observations',
        ]));
        AuditLog::log('creation', 'maintenance', "Maintenance #{$m->id}", "Type: {$m->type_echeance}, Véhicule ID: {$m->vehicule_id}");

        return redirect()->route('parc.index', ['tab' => 'maint'])->with('succes', 'Échéance ajoutée.');
    }

    public function updateMaintenance(Request $r, MaintenancePreventive $maintenance)
    {
        $this->autoriser($r);
        $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'type_echeance' => 'required|string|max:50',
            'derniere_realisation' => 'required|date',
            'prochaine_echeance' => 'required|date',
            'observations' => 'nullable|string|max:500',
        ]);

        $maintenance->update($r->only([
            'vehicule_id', 'type_echeance', 'derniere_realisation', 'prochaine_echeance', 'observations',
        ]));
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

        // Auto-créer la prochaine échéance si date fournie
        $msg = 'Maintenance réalisée.';
        if ($r->prochaine_echeance_suivante) {
            $next = MaintenancePreventive::create([
                'vehicule_id' => $maintenance->vehicule_id,
                'type_echeance' => $maintenance->type_echeance,
                'derniere_realisation' => $r->date_realisation,
                'prochaine_echeance' => $r->prochaine_echeance_suivante,
                'observations' => null,
            ]);
            AuditLog::log('creation', 'maintenance', "Maintenance #{$next->id}", "Prochaine échéance auto — Type: {$next->type_echeance}, Véhicule: " . ($maintenance->vehicule->immatriculation ?? ''));
            $msg = 'Maintenance réalisée. Prochaine échéance planifiée au ' . date('d/m/Y', strtotime($r->prochaine_echeance_suivante)) . '.';
        }

        return redirect()->route('parc.index', ['tab' => 'maint'])->with('succes', $msg);
    }

    public function destroyMaintenance(Request $r, MaintenancePreventive $maintenance)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estAdmin(), 403, 'Seuls l\'administrateur et le chargé des opérations peuvent supprimer.');
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression', 'maintenance', "Maintenance #{$maintenance->id}", "Type: {$maintenance->type_echeance}, Motif: " . $r->motif_suppression);
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
            'chauffeur' => 'required|string|max:100',
            'lieu' => 'required|string|max:120',
            'description' => 'required|string|max:500',
            'montant' => 'required|integer|min:0',
            'statut' => 'required|in:non_payee,payee,en_cours,contestee',
            'date_paiement' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ]);

        $data = $r->only([
            'vehicule_id', 'date_infraction', 'type', 'chauffeur', 'lieu',
            'description', 'montant', 'statut', 'date_paiement', 'observations',
        ]);
        $data['montant_paye'] = 0;

        $p = Penalite::create($data);

        // Créer un recouvrement lié si non payée
        if ($p->statut !== 'payee' && $p->montant > 0 && Schema::hasTable('recouvrements') && Schema::hasColumn('recouvrements', 'penalite_id')) {
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
            'chauffeur' => 'required|string|max:100',
            'lieu' => 'required|string|max:120',
            'description' => 'required|string|max:500',
            'montant' => 'required|integer|min:0',
            'statut' => 'required|in:non_payee,payee,en_cours,contestee',
            'date_paiement' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ]);

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
            'mode_paiement' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'observations' => 'nullable|string|max:500',
        ]);

        $ppData = [
            'penalite_id' => $penalite->id,
            'montant' => $r->montant,
            'date_paiement' => $r->date_paiement,
            'mode_paiement' => $r->mode_paiement,
            'recu_par' => auth()->user()->name,
            'observations' => $r->observations,
        ];

        // Emprunt si source_financement = emprunt
        if ($r->source_financement === 'emprunt') {
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

        // Mettre à jour le recouvrement lié
        if (Schema::hasTable('recouvrements') && Schema::hasColumn('recouvrements', 'penalite_id')) {
            $recouv = Recouvrement::where('penalite_id', $penalite->id)->where('statut', 'actif')->first();
            if ($recouv) {
                $recouv->montant_recouvre = $penalite->montant_paye;
                $recouv->solde = $penalite->soldeRestant();
                if ($recouv->solde <= 0) {
                    $recouv->statut = 'solde';
                }
                $recouv->save();
            }
        }

        AuditLog::log('creation', 'paiement_penalite', "Pénalité #{$penalite->id}", "Versement: {$r->montant} F, Solde: {$penalite->soldeRestant()} F");
        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Paiement enregistré. Solde : ' . number_format($penalite->soldeRestant(), 0, ',', ' ') . ' F');
    }

    /* ===== CHAUFFEURS CRUD ===== */
    public function storeChauffeur(Request $r)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'nom' => 'required|string|max:60',
            'prenom' => 'required|string|max:60',
            'numero_permis' => 'required|string|max:30|unique:chauffeurs,numero_permis',
            'telephone' => 'required|string|max:20',
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
            'numero_permis' => 'required|string|max:30|unique:chauffeurs,numero_permis,' . $chauffeur->id,
            'telephone' => 'required|string|max:20',
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

        $moisDebut = today()->startOfMonth();
        $moisFin = today()->endOfMonth();
        $recetteMois = $vehicule->recettes->whereBetween('date', [$moisDebut, $moisFin])->sum('recette');
        $pannesOuvertes = $vehicule->pannes->whereIn('statut', ['declaree', 'en_diagnostic', 'en_intervention'])->count();
        $echeancesRetard = $vehicule->maintenances->filter(fn($m) => $m->prochaine_echeance && $m->prochaine_echeance < today())->count();
        $penalitesImpayees = $vehicule->penalites->where('statut', 'non_payee')->count();

        return response()->json([
            'id' => $vehicule->id,
            'immatriculation' => $vehicule->immatriculation,
            'categorie' => $vehicule->categorie,
            'categorie_label' => $vehicule->categorieLabel(),
            'marque' => $vehicule->marque,
            'modele' => $vehicule->modele,
            'statut' => $vehicule->statut,
            'statut_label' => $vehicule->statutLabel(),
            'kilometrage' => $vehicule->kilometrage,
            'date_mise_en_service' => $vehicule->date_mise_en_service?->format('d/m/Y'),
            'chauffeurs' => $vehicule->chauffeurs->map(fn($c) => [
                'nom' => $c->nomComplet(),
                'role' => $c->pivot->role === 'principal' ? 'Principal' : 'Alternant',
                'permis' => $c->numero_permis,
                'telephone' => $c->telephone,
            ]),
            'pannes_ouvertes' => $pannesOuvertes,
            'pannes_total' => $vehicule->pannes->count(),
            'recette_mois' => $recetteMois,
            'echeances_retard' => $echeancesRetard,
            'penalites_impayees' => $penalitesImpayees,
            'observations' => $vehicule->observations,
        ]);
    }

    /* ===== FICHE RÉSUMÉ CHAUFFEUR (JSON) ===== */
    public function ficheChauffeur(Request $r, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        $moisDebut = today()->startOfMonth();
        $moisFin   = today()->endOfMonth();

        // Véhicules affectés
        $vehiculesAffectes = $chauffeur->vehicules()
            ->wherePivot('actif', true)->get()
            ->map(fn($v) => [
                'immatriculation' => $v->immatriculation,
                'categorie' => $v->categorieLabel(),
                'role' => $v->pivot->role === 'principal' ? 'Principal' : 'Alternant',
            ]);

        // Recettes ce mois
        $nomComplet = $chauffeur->nomComplet();
        $recetteMois = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])->sum('recette');
        $objectifMois = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])->sum('objectif');
        $nbJoursSaisis = RecetteVehicule::where('chauffeur', $nomComplet)
            ->whereBetween('date', [$moisDebut, $moisFin])->count();

        // Pénalités
        $penalitesTotal = Schema::hasTable('penalites')
            ? Penalite::where('chauffeur', $nomComplet)->count() : 0;
        $penalitesImpayees = Schema::hasTable('penalites')
            ? Penalite::where('chauffeur', $nomComplet)->where('statut', 'non_payee')->count() : 0;
        $penalitesMontant = Schema::hasTable('penalites')
            ? (int) Penalite::where('chauffeur', $nomComplet)->where('statut', 'non_payee')->sum('montant') : 0;

        // Recouvrements
        $recouvrActifs = Recouvrement::where('chauffeur_nom', $nomComplet)->where('statut', 'actif')->get();
        $soldeRecouvr  = (int) $recouvrActifs->sum('solde');

        return response()->json([
            'nom' => $nomComplet,
            'statut' => $chauffeur->statut,
            'telephone' => $chauffeur->telephone,
            'permis' => $chauffeur->numero_permis,
            'vehicules' => $vehiculesAffectes,
            'recette_mois' => (int) $recetteMois,
            'objectif_mois' => (int) $objectifMois,
            'taux_recette' => $objectifMois > 0 ? round(($recetteMois / $objectifMois) * 100) : 0,
            'jours_saisis' => $nbJoursSaisis,
            'penalites_total' => $penalitesTotal,
            'penalites_impayees' => $penalitesImpayees,
            'penalites_montant' => $penalitesMontant,
            'recouvrements_actifs' => $recouvrActifs->count(),
            'solde_recouvrement' => $soldeRecouvr,
        ]);
    }

    /* ===== KPI ===== */
    private function calculerKpi($vehicules): array
    {
        $actifs = $vehicules->where('statut', 'en_service')->count();
        $taxis = $vehicules->where('categorie', 'taxi')->count();
        $vtcs = $vehicules->where('categorie', 'vtc')->count();
        $moisDebut = today()->startOfMonth();
        $moisFin = today()->endOfMonth();

        $pannesMois = Panne::whereBetween('date_declaration', [$moisDebut, $moisFin])->count();
        $pannesOuvertes = Panne::whereIn('statut', ['declaree', 'en_diagnostic', 'en_intervention'])->count();
        $coutMois = (int) Panne::whereBetween('date_declaration', [$moisDebut, $moisFin])->sum('cout_reel');
        $delaiMoyen = Panne::whereBetween('date_declaration', [$moisDebut, $moisFin])
            ->whereNotNull('delai_resolution')->avg('delai_resolution');

        $recetteMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('recette');
        $objectifMois = (int) RecetteVehicule::whereBetween('date', [$moisDebut, $moisFin])->sum('objectif');
        $tauxRecette = $objectifMois > 0 ? round(($recetteMois / $objectifMois) * 100) : 0;

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
            'mode_paiement' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'source_paiement' => 'nullable|in:chauffeur,entreprise',
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
                    'montant' => $data['montant'],
                    'commentaire' => "Recouvrement #{$recouvrement->id} — {$typeRecouvr} — {$recouvrement->chauffeur_nom}",
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

    /* ═══════════════════════════ DÉPENSES ═══════════════════════════ */

    public function storeDepense(Request $r)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403, 'La direction ne peut pas saisir de dépenses.');
        $v = $r->validate([
            'date_depense' => 'required|date',
            'type_depense' => 'required|string|max:100',
            'vehicule_id'  => 'nullable|exists:vehicules,id',
            'montant'      => 'required|integer|min:1',
            'commentaire'  => 'nullable|string|max:500',
        ]);
        $v['cree_par'] = auth()->user()->name;
        $dep = Depense::create($v);
        AuditLog::log('creation', 'depense', "Dépense #{$dep->id}", "Type: {$dep->type_depense}, Montant: {$dep->montant} F");
        return redirect()->route('parc.index', ['tab' => 'depenses'])->with('succes', 'Dépense enregistrée.');
    }

    public function updateDepense(Request $r, Depense $depense)
    {
        $this->autoriser($r);
        abort_if($r->user()->aRole('direction'), 403);
        $v = $r->validate([
            'date_depense' => 'required|date',
            'type_depense' => 'required|string|max:100',
            'vehicule_id'  => 'nullable|exists:vehicules,id',
            'montant'      => 'required|integer|min:1',
            'commentaire'  => 'nullable|string|max:500',
        ]);
        if (!$r->filled('vehicule_id')) $v['vehicule_id'] = null;
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
            'motif'        => 'required|string|max:500',
        ]);
        $data['source_type'] = 'autre';
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
}
