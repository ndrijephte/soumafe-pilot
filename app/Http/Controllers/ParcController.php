<?php
namespace App\Http\Controllers;

use App\Models\{Chauffeur, JourFerie, MaintenancePreventive, Panne, ParametreParc, Penalite, RecetteVehicule, TypeParc, User, Vehicule};
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
        $pannes = Panne::with('vehicule')->orderByDesc('date_declaration')->get();
        $recettes = RecetteVehicule::with('vehicule')->orderByDesc('date')->get();
        $maintenances = MaintenancePreventive::with('vehicule')->orderBy('prochaine_echeance')->get();
        $penalites = Penalite::with('vehicule')->orderByDesc('date_infraction')->get();

        $kpi = $this->calculerKpi($vehicules);
        $typesPenalite = TypeParc::penalites();
        $typesMaintenance = TypeParc::maintenances();

        return view('parc.index', compact('vehicules', 'chauffeurs', 'collaborateurs', 'pannes', 'recettes', 'maintenances', 'penalites', 'kpi', 'typesPenalite', 'typesMaintenance'));
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
            'date_mise_en_service' => 'nullable|date',
            'chauffeur_affecte' => 'nullable|string|max:100',
            'kilometrage' => 'nullable|integer|min:0',
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
            'date_mise_en_service' => 'nullable|date',
            'chauffeur_affecte' => 'nullable|string|max:100',
            'kilometrage' => 'nullable|integer|min:0',
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

        return redirect()->route('parc.index', ['tab' => 'vehicules'])->with('succes', 'Véhicule mis à jour.');
    }

    public function destroyVehicule(Request $r, Vehicule $vehicule)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estPilote(), 403);
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

        Panne::create($data);
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
        return redirect()->route('parc.index', ['tab' => 'pannes'])->with('succes', 'Panne mise à jour.');
    }

    public function destroyPanne(Request $r, Panne $panne)
    {
        $this->autoriser($r);
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
            'chauffeur' => 'nullable|string|max:100',
            'recette' => 'required|integer|min:0',
            'mode_remise' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'controle_par' => 'nullable|string|max:100',
            'observations' => 'nullable|string|max:500',
        ]);

        $v = Vehicule::find($data['vehicule_id']);
        $data['type_jour'] = $this->detecterTypeJour($data['date']);

        // Objectif dynamique : paramètre si dispo, sinon objectif véhicule
        $categorie = $v->categorie ?? 'taxi';
        $objectifParam = ParametreParc::objectifRecette($categorie, $data['type_jour']);
        $data['objectif'] = $objectifParam > 0 ? $objectifParam : ($v->objectif_recette ?? 0);
        $data['ecart'] = $data['recette'] - $data['objectif'];

        RecetteVehicule::create($data);
        return redirect()->route('parc.index', ['tab' => 'recettes'])->with('succes', 'Recette enregistrée.');
    }

    public function updateRecette(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'date' => 'required|date',
            'vehicule_id' => 'required|exists:vehicules,id',
            'chauffeur' => 'nullable|string|max:100',
            'recette' => 'required|integer|min:0',
            'mode_remise' => 'nullable|in:especes,mobile_money,cheque,virement,autre',
            'controle_par' => 'nullable|string|max:100',
            'observations' => 'nullable|string|max:500',
        ]);

        $v = Vehicule::find($data['vehicule_id']);
        $data['type_jour'] = $this->detecterTypeJour($data['date']);
        $categorie = $v->categorie ?? 'taxi';
        $objectifParam = ParametreParc::objectifRecette($categorie, $data['type_jour']);
        $data['objectif'] = $objectifParam > 0 ? $objectifParam : ($v->objectif_recette ?? 0);
        $data['ecart'] = $data['recette'] - $data['objectif'];

        $recette->update($data);
        return redirect()->route('parc.index', ['tab' => 'recettes'])->with('succes', 'Recette mise à jour.');
    }

    public function destroyRecette(Request $r, RecetteVehicule $recette)
    {
        $this->autoriser($r);
        $recette->delete();
        return redirect()->route('parc.index', ['tab' => 'recettes'])->with('succes', 'Recette supprimée.');
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

        MaintenancePreventive::create($r->only([
            'vehicule_id', 'type_echeance', 'derniere_realisation', 'prochaine_echeance', 'observations',
        ]));

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

        return redirect()->route('parc.index', ['tab' => 'maint'])->with('succes', 'Échéance mise à jour.');
    }

    public function destroyMaintenance(Request $r, MaintenancePreventive $maintenance)
    {
        $this->autoriser($r);
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
            'chauffeur' => 'nullable|string|max:100',
            'lieu' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'montant' => 'required|integer|min:0',
            'statut' => 'required|in:non_payee,payee,contestee',
            'date_paiement' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ]);

        Penalite::create($r->only([
            'vehicule_id', 'date_infraction', 'type', 'chauffeur', 'lieu',
            'description', 'montant', 'statut', 'date_paiement', 'observations',
        ]));

        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Pénalité enregistrée.');
    }

    public function updatePenalite(Request $r, Penalite $penalite)
    {
        $this->autoriser($r);
        $r->validate([
            'vehicule_id' => 'required|exists:vehicules,id',
            'date_infraction' => 'required|date',
            'type' => 'required|string|max:50',
            'chauffeur' => 'nullable|string|max:100',
            'lieu' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:500',
            'montant' => 'required|integer|min:0',
            'statut' => 'required|in:non_payee,payee,contestee',
            'date_paiement' => 'nullable|date',
            'observations' => 'nullable|string|max:500',
        ]);

        $penalite->update($r->only([
            'vehicule_id', 'date_infraction', 'type', 'chauffeur', 'lieu',
            'description', 'montant', 'statut', 'date_paiement', 'observations',
        ]));

        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Pénalité mise à jour.');
    }

    public function destroyPenalite(Request $r, Penalite $penalite)
    {
        $this->autoriser($r);
        $penalite->delete();
        return redirect()->route('parc.index', ['tab' => 'penal'])->with('succes', 'Pénalité supprimée.');
    }

    /* ===== CHAUFFEURS CRUD ===== */
    public function storeChauffeur(Request $r)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'nom' => 'required|string|max:60',
            'prenom' => 'required|string|max:60',
            'numero_permis' => 'nullable|string|max:30|unique:chauffeurs,numero_permis',
            'telephone' => 'nullable|string|max:20',
            'statut' => 'required|in:actif,inactif',
            'observations' => 'nullable|string',
        ]);
        Chauffeur::create($data);
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur enregistré.');
    }

    public function updateChauffeur(Request $r, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'nom' => 'required|string|max:60',
            'prenom' => 'required|string|max:60',
            'numero_permis' => 'nullable|string|max:30|unique:chauffeurs,numero_permis,' . $chauffeur->id,
            'telephone' => 'nullable|string|max:20',
            'statut' => 'required|in:actif,inactif',
            'observations' => 'nullable|string',
        ]);
        $chauffeur->update($data);
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur mis à jour.');
    }

    public function destroyChauffeur(Request $r, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        abort_unless($r->user()->estPilote(), 403);
        $chauffeur->delete();
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur supprimé.');
    }

    public function affecterChauffeur(Request $r)
    {
        $this->autoriser($r);
        $data = $r->validate([
            'chauffeur_id' => 'required|exists:chauffeurs,id',
            'vehicule_id' => 'required|exists:vehicules,id',
            'role' => 'required|in:principal,alternant',
        ]);
        $v = Vehicule::findOrFail($data['vehicule_id']);
        $v->chauffeurs()->syncWithoutDetaching([$data['chauffeur_id'] => ['role' => $data['role'], 'actif' => true]]);
        return redirect()->route('parc.index', ['tab' => 'chauffeurs'])->with('succes', 'Chauffeur affecté.');
    }

    public function detacherChauffeur(Request $r, Vehicule $vehicule, Chauffeur $chauffeur)
    {
        $this->autoriser($r);
        $vehicule->chauffeurs()->detach($chauffeur->id);
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
}
