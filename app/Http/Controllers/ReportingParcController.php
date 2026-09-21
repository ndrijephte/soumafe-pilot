<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, Chauffeur, Depense, Emprunt, MaintenancePreventive, Objectif, Panne, Penalite, RecetteVehicule, TypeParc, User, Vehicule};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

class ReportingParcController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->peutGererParc(), 403);

        $periode = $r->get('periode', 'mois');
        [$debut, $fin] = $this->bornesPeriode($periode, $r->get('date_debut'), $r->get('date_fin'));
        $categorie = $r->get('categorie', 'tous');
        $filtreVehicule = $r->get('vehicule_id');
        $filtreChauffeur = $r->get('chauffeur_id');

        // Filtre véhicules par catégorie
        $vehiculesQ = Vehicule::query();
        if ($categorie !== 'tous') $vehiculesQ->where('categorie', $categorie);
        $vehicules = $vehiculesQ->orderBy('immatriculation')->get();
        $vehiculeIds = $vehicules->pluck('id');

        // Si véhicule spécifique sélectionné
        if ($filtreVehicule) {
            $vehiculeIds = $vehiculeIds->intersect([$filtreVehicule]);
        }

        // Tous chauffeurs et tous véhicules pour les dropdowns
        $tousVehicules = Vehicule::orderBy('immatriculation')->get();
        $tousChauffeurs = Schema::hasTable('chauffeurs')
            ? Chauffeur::where('statut', 'actif')->orderBy('nom')->get()
            : collect();

        // ═══════ KPI GLOBAUX ═══════
        $recetteTotal = (int) RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date', [$debut, $fin])->sum('recette');
        $objectifTotal = (int) RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date', [$debut, $fin])->sum('objectif');
        $tauxRecette = $objectifTotal > 0 ? round(($recetteTotal / $objectifTotal) * 100) : 0;
        $pointageTotal = (int) RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date', [$debut, $fin])->sum('pointage');

        $coutPannes = (int) Panne::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date_declaration', [$debut, $fin])->sum('cout_reel');
        $nbPannes = Panne::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date_declaration', [$debut, $fin])->count();
        $delaiMoyen = Panne::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date_declaration', [$debut, $fin])
            ->whereNotNull('delai_resolution')->avg('delai_resolution');
        $delaiMoyen = $delaiMoyen !== null ? round($delaiMoyen, 1) : 0;

        $montantPenalites = 0;
        $nbPenalites = 0;
        if (Schema::hasTable('penalites')) {
            $montantPenalites = (int) Penalite::whereIn('vehicule_id', $vehiculeIds)
                ->whereBetween('date_infraction', [$debut, $fin])->sum('montant');
            $nbPenalites = Penalite::whereIn('vehicule_id', $vehiculeIds)
                ->whereBetween('date_infraction', [$debut, $fin])->count();
        }

        $maintenancesRetard = MaintenancePreventive::whereIn('vehicule_id', $vehiculeIds)
            ->whereNotNull('prochaine_echeance')
            ->whereDate('prochaine_echeance', '<', today())->count();
        $maintenancesProches = MaintenancePreventive::whereIn('vehicule_id', $vehiculeIds)
            ->whereNotNull('prochaine_echeance')
            ->whereDate('prochaine_echeance', '>=', today())
            ->whereDate('prochaine_echeance', '<=', today()->addDays(15))->count();

        // ═══════ RECETTES PAR JOUR (graphique barres) ═══════
        $recettesJour = RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date', [$debut, $fin])
            ->select(DB::raw("DATE(date) as jour"), DB::raw("SUM(recette) as total"), DB::raw("SUM(objectif) as obj"))
            ->groupBy('jour')->orderBy('jour')->get()
            ->map(fn($r) => ['jour' => $r->jour, 'total' => (int) $r->total, 'obj' => (int) $r->obj]);

        // ═══════ RECETTES PAR VÉHICULE ═══════
        $recettesVehicule = RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date', [$debut, $fin])
            ->select('vehicule_id', DB::raw("SUM(recette) as total"), DB::raw("SUM(objectif) as obj"), DB::raw("COUNT(*) as nb_jours"))
            ->groupBy('vehicule_id')->get()
            ->map(function ($r) use ($vehicules) {
                $v = $vehicules->firstWhere('id', $r->vehicule_id);
                return [
                    'immat' => $v ? $v->immatriculation : '?',
                    'categorie' => $v ? $v->categorieLabel() : '?',
                    'recette' => (int) $r->total,
                    'objectif' => (int) $r->obj,
                    'ecart' => (int) $r->total - (int) $r->obj,
                    'taux' => $r->obj > 0 ? round(($r->total / $r->obj) * 100) : 0,
                    'nb_jours' => (int) $r->nb_jours,
                    'moyenne' => $r->nb_jours > 0 ? round($r->total / $r->nb_jours) : 0,
                ];
            })->sortByDesc('recette')->values();

        // ═══════ PANNES PAR VÉHICULE ═══════
        $pannesVehicule = Panne::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date_declaration', [$debut, $fin])
            ->select('vehicule_id', DB::raw("COUNT(*) as nb"), DB::raw("SUM(cout_reel) as cout"),
                DB::raw("AVG(delai_resolution) as delai"))
            ->groupBy('vehicule_id')->get()
            ->map(function ($r) use ($vehicules) {
                $v = $vehicules->firstWhere('id', $r->vehicule_id);
                return [
                    'immat' => $v ? $v->immatriculation : '?',
                    'nb' => (int) $r->nb,
                    'cout' => (int) $r->cout,
                    'delai' => $r->delai !== null ? round($r->delai, 1) : 0,
                ];
            })->sortByDesc('cout')->values();

        // ═══════ PANNES PAR URGENCE ═══════
        $pannesUrgence = Panne::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date_declaration', [$debut, $fin])
            ->select('urgence', DB::raw("COUNT(*) as nb"))
            ->groupBy('urgence')->get()
            ->pluck('nb', 'urgence');

        // ═══════ PÉNALITÉS PAR TYPE ═══════
        $penalitesType = collect();
        if (Schema::hasTable('penalites')) {
            $penalitesType = Penalite::whereIn('vehicule_id', $vehiculeIds)
                ->whereBetween('date_infraction', [$debut, $fin])
                ->select('type', DB::raw("COUNT(*) as nb"), DB::raw("SUM(montant) as montant"))
                ->groupBy('type')->get()
                ->map(function ($r) {
                    $tp = TypeParc::where('categorie', 'penalite')->where('code', $r->type)->first();
                    return [
                        'type' => $tp ? $tp->libelle : ucfirst(str_replace('_', ' ', $r->type)),
                        'nb' => (int) $r->nb,
                        'montant' => (int) $r->montant,
                    ];
                })->sortByDesc('montant')->values();
        }

        // ═══════ PÉNALITÉS PAR STATUT ═══════
        $penalitesStatut = collect();
        if (Schema::hasTable('penalites')) {
            $penalitesStatut = Penalite::whereIn('vehicule_id', $vehiculeIds)
                ->whereBetween('date_infraction', [$debut, $fin])
                ->select('statut', DB::raw("COUNT(*) as nb"), DB::raw("SUM(montant) as montant"))
                ->groupBy('statut')->get()
                ->pluck('montant', 'statut');
        }

        // ═══════ MAINTENANCE PAR TYPE ═══════
        $maintenanceType = MaintenancePreventive::whereIn('vehicule_id', $vehiculeIds)
            ->select('type_echeance', DB::raw("COUNT(*) as nb"),
                DB::raw("SUM(CASE WHEN prochaine_echeance < CURDATE() THEN 1 ELSE 0 END) as retard"),
                DB::raw("SUM(CASE WHEN prochaine_echeance >= CURDATE() AND prochaine_echeance <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 1 ELSE 0 END) as proche"))
            ->groupBy('type_echeance')->get()
            ->map(function ($r) {
                $tp = TypeParc::where('categorie', 'maintenance')->where('code', $r->type_echeance)->first();
                return [
                    'type' => $tp ? $tp->libelle : ucfirst(str_replace('_', ' ', $r->type_echeance)),
                    'nb' => (int) $r->nb,
                    'retard' => (int) $r->retard,
                    'proche' => (int) $r->proche,
                ];
            });

        // ═══════ PERFORMANCE CHAUFFEURS (individuelle par chauffeur) ═══════
        $performanceChauffeurs = collect();
        if (Schema::hasTable('chauffeurs') && Schema::hasTable('chauffeur_vehicule')) {
            $performanceChauffeurs = Chauffeur::with(['vehicules' => fn($q) => $q->wherePivot('actif', true)])
                ->where('statut', 'actif')->get()
                ->map(function ($c) use ($debut, $fin, $vehiculeIds) {
                    $nomComplet = $c->nomComplet();
                    $cvIds = $c->vehicules->pluck('id')->intersect($vehiculeIds);

                    // Recettes individuelles : filtrées par nom du chauffeur + même scope véhicules que les KPIs globaux
                    $recQ = RecetteVehicule::where('chauffeur', $nomComplet)
                        ->whereIn('vehicule_id', $vehiculeIds)
                        ->whereBetween('date', [$debut, $fin]);
                    $recTotal = (int) (clone $recQ)->sum('recette');
                    $objTotal = (int) (clone $recQ)->sum('objectif');
                    $nbJours = (clone $recQ)->count();
                    $totalPointage = (int) (clone $recQ)->sum('pointage');

                    if ($nbJours === 0) return null;

                    // Pannes et pénalités sur les véhicules de la catégorie filtrée
                    $coutP = (int) Panne::whereIn('vehicule_id', $vehiculeIds)
                        ->whereBetween('date_declaration', [$debut, $fin])
                        ->where(function($q) use ($nomComplet, $cvIds) {
                            if ($cvIds->isNotEmpty()) $q->whereIn('vehicule_id', $cvIds);
                        })->sum('cout_reel');
                    $nbP = Panne::whereIn('vehicule_id', $vehiculeIds)
                        ->whereBetween('date_declaration', [$debut, $fin])
                        ->where(function($q) use ($cvIds) {
                            if ($cvIds->isNotEmpty()) $q->whereIn('vehicule_id', $cvIds);
                        })->count();
                    $montPen = 0; $nbPen = 0;
                    if (Schema::hasTable('penalites')) {
                        $montPen = (int) Penalite::whereIn('vehicule_id', $vehiculeIds)
                            ->where('chauffeur', $nomComplet)
                            ->whereBetween('date_infraction', [$debut, $fin])->sum('montant');
                        $nbPen = Penalite::whereIn('vehicule_id', $vehiculeIds)
                            ->where('chauffeur', $nomComplet)
                            ->whereBetween('date_infraction', [$debut, $fin])->count();
                    }
                    $resultat = $recTotal - $coutP - $montPen;
                    // Tous véhicules utilisés dans la période (pas seulement assignés)
                    $vehUtilises = RecetteVehicule::where('chauffeur', $nomComplet)
                        ->whereIn('vehicule_id', $vehiculeIds)
                        ->whereBetween('date', [$debut, $fin])
                        ->distinct()->pluck('vehicule_id');
                    $vehiculesLabels = Vehicule::whereIn('id', $vehUtilises)->pluck('immatriculation')->join(', ');
                    return [
                        'nom' => $nomComplet,
                        'statut' => $c->statut,
                        'nb_vehicules' => $vehUtilises->count(),
                        'vehicules' => $vehiculesLabels,
                        'recette' => $recTotal,
                        'objectif' => $objTotal,
                        'nb_jours' => $nbJours,
                        'taux' => $objTotal > 0 ? round(($recTotal / $objTotal) * 100) : 0,
                        'cout_pannes' => $coutP,
                        'nb_pannes' => $nbP,
                        'montant_penalites' => $montPen,
                        'nb_penalites' => $nbPen,
                        'resultat' => $resultat,
                        'pointage' => $totalPointage,
                    ];
                })->filter()->sortByDesc('resultat')->values();
        }

        // ═══════ GESTIONNAIRES PARC — ACTUEL + HISTORIQUE ═══════
        $gestionnairesActuels = User::where('actif', true)->where('delegation_parc', true)->get()
            ->map(fn($u) => $u->nomComplet());
        if ($gestionnairesActuels->isEmpty()) {
            $gestionnairesActuels = User::where('actif', true)
                ->whereIn('role', ['administrateur', 'charge_operations'])->get()
                ->map(fn($u) => $u->nomComplet());
        }
        $historiqueGestionnaires = collect();
        if (Schema::hasTable('audit_logs')) {
            $historiqueGestionnaires = AuditLog::where('module', 'utilisateur')
                ->where('action', 'delegation_parc')
                ->orderByDesc('created_at')->take(20)->get();
        }

        // ═══════ PERFORMANCE OKR (gestionnaire + chauffeurs) ═══════
        $okrGestionnaire = null;
        $okrChauffeurs = collect();
        $periodeOkr = now()->format('Y-m');
        if (Schema::hasTable('objectifs') && Schema::hasColumn('objectifs', 'type')) {
            $eagerOkr = ['resultats'];
            // Score OKR du gestionnaire connecté
            $mesObj = Objectif::with($eagerOkr)->where('user_id', $r->user()->id)
                ->where('periode', $periodeOkr)->get();
            if ($mesObj->isNotEmpty()) {
                $okrGestionnaire = [
                    'score' => Objectif::scoreGroupe($mesObj),
                    'total' => $mesObj->count(),
                    'atteints' => $mesObj->where('evaluation', 'atteint')->count(),
                    'partiels' => $mesObj->where('evaluation', 'partiel')->count(),
                    'en_cours' => $mesObj->whereIn('evaluation', ['en_cours', null])->count(),
                ];
            }
            // Scores OKR des chauffeurs (via users liés aux chauffeurs)
            if (Schema::hasTable('chauffeurs')) {
                $chauffeursActifs = Chauffeur::where('statut', 'actif')->get();
                foreach ($chauffeursActifs as $ch) {
                    if (!$ch->user_id) continue;
                    $objCh = Objectif::with($eagerOkr)->where('user_id', $ch->user_id)
                        ->where('periode', $periodeOkr)->get();
                    if ($objCh->isEmpty()) continue;
                    $okrChauffeurs->push([
                        'nom' => $ch->nomComplet(),
                        'score' => Objectif::scoreGroupe($objCh),
                        'total' => $objCh->count(),
                        'atteints' => $objCh->where('evaluation', 'atteint')->count(),
                    ]);
                }
                $okrChauffeurs = $okrChauffeurs->sortByDesc('score')->values();
            }
        }

        // ═══════ CLASSEMENT VÉHICULES (performance globale) ═══════
        $classement = $vehicules->where('statut', 'en_service')->map(function ($v) use ($debut, $fin) {
            $rec = RecetteVehicule::where('vehicule_id', $v->id)
                ->whereBetween('date', [$debut, $fin]);
            $recTotal = (int) (clone $rec)->sum('recette');
            $objTotal = (int) (clone $rec)->sum('objectif');
            $coutP = (int) Panne::where('vehicule_id', $v->id)
                ->whereBetween('date_declaration', [$debut, $fin])->sum('cout_reel');
            $montPen = 0;
            if (Schema::hasTable('penalites')) {
                $montPen = (int) Penalite::where('vehicule_id', $v->id)
                    ->whereBetween('date_infraction', [$debut, $fin])->sum('montant');
            }
            $nbPannes = Panne::where('vehicule_id', $v->id)
                ->whereBetween('date_declaration', [$debut, $fin])->count();
            $maint = MaintenancePreventive::where('vehicule_id', $v->id)
                ->whereNotNull('prochaine_echeance')
                ->whereDate('prochaine_echeance', '<', today())->count();

            $resultat = $recTotal - $coutP - $montPen;

            return [
                'immat' => $v->immatriculation,
                'categorie' => $v->categorieLabel(),
                'recette' => $recTotal,
                'objectif' => $objTotal,
                'taux' => $objTotal > 0 ? round(($recTotal / $objTotal) * 100) : 0,
                'cout_pannes' => $coutP,
                'penalites' => $montPen,
                'nb_pannes' => $nbPannes,
                'maint_retard' => $maint,
                'resultat' => $resultat,
            ];
        })->sortByDesc('resultat')->values();

        // ═══════ DÉTAIL JOUR PAR JOUR (si filtre véhicule ou chauffeur) ═══════
        $detailJour = collect();
        $filtreNom = null;
        if ($filtreChauffeur && Schema::hasTable('chauffeurs')) {
            $ch = Chauffeur::find($filtreChauffeur);
            if ($ch) {
                $filtreNom = $ch->nomComplet();
                $detailJour = RecetteVehicule::where('chauffeur', $filtreNom)
                    ->whereBetween('date', [$debut, $fin])
                    ->orderBy('date')->get();
            }
        } elseif ($filtreVehicule) {
            $vf = Vehicule::find($filtreVehicule);
            $filtreNom = $vf ? $vf->immatriculation : null;
            $detailJour = RecetteVehicule::where('vehicule_id', $filtreVehicule)
                ->whereBetween('date', [$debut, $fin])
                ->orderBy('date')->get();
        }

        // ═══════ DÉPENSES + RENTABILITÉ ═══════
        $depensesTotal = 0;
        $depensesJour = collect();
        $depensesType = collect();
        $coutMaintenance = 0;
        $maintJ = collect();
        if (Schema::hasTable('depenses')) {
            // Autres dépenses (hors maintenance = dépense véhicule)
            $depsQuery = Depense::whereBetween('date_depense', [$debut, $fin])
                ->where('type_depense', '!=', 'maintenance');
            $depensesTotal = (clone $depsQuery)->sum('montant');
            // Par jour (pour courbe bureau)
            $depensesJour = (clone $depsQuery)->selectRaw('date_depense as date, SUM(montant) as total')
                ->groupBy('date_depense')->orderBy('date_depense')->get()
                ->mapWithKeys(fn($d) => [$d->date => $d->total]);
            // Par type (hors maintenance)
            $depensesType = (clone $depsQuery)->selectRaw('type_depense, SUM(montant) as total')
                ->groupBy('type_depense')->orderByDesc('total')->get();
            // Maintenance = dépense véhicule
            $maintQuery = Depense::whereBetween('date_depense', [$debut, $fin])
                ->where('type_depense', 'maintenance');
            $coutMaintenance = $maintQuery->sum('montant');
            $maintJ = (clone $maintQuery)->selectRaw('date_depense as date, SUM(montant) as total')
                ->groupBy('date_depense')->orderBy('date_depense')->get()
                ->mapWithKeys(fn($d) => [$d->date => (int) $d->total]);
        }
        $typesDepenseLabels = TypeParc::depenses()->pluck('libelle', 'code')->toArray();

        // Coûts véhicules par jour (pannes + pénalités + maintenance)
        $depVehJour = collect();
        $pannesJ = Panne::whereIn('vehicule_id', $vehiculeIds)
            ->whereBetween('date_declaration', [$debut, $fin])
            ->selectRaw('DATE(date_declaration) as date, SUM(cout_reel) as total')
            ->groupBy(DB::raw('DATE(date_declaration)'))->get()
            ->mapWithKeys(fn($d) => [$d->date => (int) $d->total]);
        $penalJ = collect();
        if (Schema::hasTable('penalites')) {
            $penalJ = Penalite::whereIn('vehicule_id', $vehiculeIds)
                ->whereBetween('date_infraction', [$debut, $fin])
                ->selectRaw('DATE(date_infraction) as date, SUM(montant) as total')
                ->groupBy(DB::raw('DATE(date_infraction)'))->get()
                ->mapWithKeys(fn($d) => [$d->date => (int) $d->total]);
        }
        $allVehDates = $pannesJ->keys()->merge($penalJ->keys())->merge($maintJ->keys())->unique();
        $depVehJour = $allVehDates->mapWithKeys(fn($d) => [$d => ($pannesJ[$d] ?? 0) + ($penalJ[$d] ?? 0) + ($maintJ[$d] ?? 0)]);

        // Rentabilité = Recettes - Dépenses bureau - Pénalités - Coût pannes - Maintenance
        $rentabilite = $recetteTotal - $depensesTotal - $montantPenalites - $coutPannes - $coutMaintenance;

        // ═══════ EMPRUNTS vs FONDS PROPRES ═══════
        $empruntsData = ['total_emprunte' => 0, 'total_rembourse' => 0, 'solde_du' => 0, 'nb_emprunts' => 0, 'nb_en_cours' => 0];
        $fondsData = ['pannes_fp' => 0, 'pannes_emp' => 0, 'penalites_fp' => 0, 'penalites_emp' => 0, 'maint_fp' => 0, 'maint_emp' => 0];
        if (Schema::hasTable('emprunts')) {
            $empPeriode = Emprunt::whereBetween('date_emprunt', [$debut, $fin]);
            $empruntsData['total_emprunte'] = (int) (clone $empPeriode)->sum('montant');
            $empruntsData['total_rembourse'] = (int) (clone $empPeriode)->sum('montant_rembourse');
            $empruntsData['solde_du'] = $empruntsData['total_emprunte'] - $empruntsData['total_rembourse'];
            $empruntsData['nb_emprunts'] = (clone $empPeriode)->count();
            $empruntsData['nb_en_cours'] = (clone $empPeriode)->where('statut', 'en_cours')->count();

            // Pannes : fonds propres vs emprunt
            $pannesPeriode = Panne::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_declaration', [$debut, $fin]);
            $fondsData['pannes_emp'] = (int) (clone $pannesPeriode)->whereNotNull('emprunt_id')->sum('cout_reel');
            $fondsData['pannes_fp'] = $coutPannes - $fondsData['pannes_emp'];

            // Pénalités : fonds propres vs emprunt
            if (Schema::hasTable('paiements_penalites')) {
                $paiPeriode = DB::table('paiements_penalites')
                    ->join('penalites', 'paiements_penalites.penalite_id', '=', 'penalites.id')
                    ->whereIn('penalites.vehicule_id', $vehiculeIds)
                    ->whereBetween('paiements_penalites.date_paiement', [$debut, $fin]);
                $fondsData['penalites_emp'] = (int) (clone $paiPeriode)->whereNotNull('paiements_penalites.emprunt_id')->sum('paiements_penalites.montant');
                $totalPaiePeriode = (int) (clone $paiPeriode)->sum('paiements_penalites.montant');
                $fondsData['penalites_fp'] = $totalPaiePeriode - $fondsData['penalites_emp'];
            }

            // Maintenance : fonds propres vs emprunt
            $maintPeriode = Depense::where('type_depense', 'maintenance')->whereBetween('date_depense', [$debut, $fin]);
            $fondsData['maint_emp'] = (int) (clone $maintPeriode)->whereNotNull('emprunt_id')->sum('montant');
            $fondsData['maint_fp'] = $coutMaintenance - $fondsData['maint_emp'];
        }
        $totalFP = $fondsData['pannes_fp'] + $fondsData['penalites_fp'] + $fondsData['maint_fp'];
        $totalEmp = $fondsData['pannes_emp'] + $fondsData['penalites_emp'] + $fondsData['maint_emp'];

        // Solde en caisse réel = rentabilité - dette non remboursée (emprunts en cours)
        // Les emprunts non remboursés sont une dette, pas un gain
        $detteGlobale = Schema::hasTable('emprunts')
            ? (int) Emprunt::where('statut', 'en_cours')->sum(\DB::raw('montant - montant_rembourse'))
            : 0;
        $soldeCaisse = $rentabilite - $detteGlobale;

        // ═══════ RENTABILITÉ DYNAMIQUE PAR VÉHICULE ═══════
        $rentaVehicules = $vehicules->where('statut', 'en_service')->map(function ($v) use ($debut, $fin) {
            $rec = (int) RecetteVehicule::where('vehicule_id', $v->id)->whereBetween('date', [$debut, $fin])->sum('recette');
            $obj = (int) RecetteVehicule::where('vehicule_id', $v->id)->whereBetween('date', [$debut, $fin])->sum('objectif');
            $nbJ = RecetteVehicule::where('vehicule_id', $v->id)->whereBetween('date', [$debut, $fin])->count();
            $pan = (int) Panne::where('vehicule_id', $v->id)->whereBetween('date_declaration', [$debut, $fin])->sum('cout_reel');
            $pen = Schema::hasTable('penalites') ? (int) Penalite::where('vehicule_id', $v->id)->whereBetween('date_infraction', [$debut, $fin])->sum('montant') : 0;
            $mai = (int) Depense::where('type_depense', 'maintenance')->where('vehicule_id', $v->id)->whereBetween('date_depense', [$debut, $fin])->sum('montant');
            $dep = (int) Depense::where('type_depense', '!=', 'maintenance')->where('vehicule_id', $v->id)->whereBetween('date_depense', [$debut, $fin])->sum('montant');
            $emp = Schema::hasTable('emprunts') ? (int) Emprunt::where('vehicule_id', $v->id)->whereBetween('date_emprunt', [$debut, $fin])->sum('montant') : 0;
            $empR = Schema::hasTable('emprunts') ? (int) Emprunt::where('vehicule_id', $v->id)->whereBetween('date_emprunt', [$debut, $fin])->sum('montant_rembourse') : 0;
            $totalDep = $pan + $pen + $mai + $dep;
            $profit = $rec - $totalDep;
            $roi = $totalDep > 0 ? round(($rec / $totalDep) * 100) : ($rec > 0 ? 999 : 0);
            return [
                'id' => $v->id, 'immat' => $v->immatriculation, 'categorie' => $v->categorieLabel(),
                'recette' => $rec, 'objectif' => $obj, 'nb_jours' => $nbJ,
                'pannes' => $pan, 'penalites' => $pen, 'maintenance' => $mai, 'depenses' => $dep,
                'total_depenses' => $totalDep, 'profit' => $profit, 'roi' => $roi,
                'emprunt' => $emp, 'emprunt_rembourse' => $empR, 'dette_nette' => $emp - $empR,
                'verdict' => $profit > 0 ? 'rentable' : ($profit == 0 ? 'equilibre' : 'deficit'),
            ];
        })->sortByDesc('profit')->values();
        // KPIs décisionnels
        $nbRentables = $rentaVehicules->where('verdict', 'rentable')->count();
        $nbDeficit = $rentaVehicules->where('verdict', 'deficit')->count();
        $profitTotal = $rentaVehicules->sum('profit');
        $depTotalVeh = $rentaVehicules->sum('total_depenses');
        $detteNetteTotale = $rentaVehicules->sum('dette_nette');

        return view('reporting.parc', compact(
            'periode', 'debut', 'fin', 'categorie',
            'recetteTotal', 'objectifTotal', 'tauxRecette', 'pointageTotal',
            'coutPannes', 'nbPannes', 'delaiMoyen',
            'montantPenalites', 'nbPenalites',
            'maintenancesRetard', 'maintenancesProches',
            'recettesJour', 'recettesVehicule',
            'pannesVehicule', 'pannesUrgence',
            'penalitesType', 'penalitesStatut',
            'maintenanceType', 'classement',
            'performanceChauffeurs', 'gestionnairesActuels', 'historiqueGestionnaires',
            'tousVehicules', 'tousChauffeurs',
            'filtreVehicule', 'filtreChauffeur',
            'detailJour', 'filtreNom',
            'depensesTotal', 'depensesJour', 'depVehJour', 'depensesType', 'typesDepenseLabels', 'rentabilite',
            'okrGestionnaire', 'okrChauffeurs', 'periodeOkr',
            'empruntsData', 'fondsData', 'totalFP', 'totalEmp', 'soldeCaisse', 'detteGlobale',
            'rentaVehicules', 'nbRentables', 'nbDeficit', 'profitTotal', 'depTotalVeh', 'detteNetteTotale'
        ));
    }

    private function bornesPeriode(string $p, ?string $d, ?string $f): array
    {
        return match ($p) {
            'mois_precedent' => [today()->subMonth()->startOfMonth(), today()->subMonth()->endOfMonth()],
            'trimestre' => [today()->firstOfQuarter(), today()->lastOfQuarter()],
            'personnalise' => [
                Carbon::parse($d ?? today()->subDays(30)->format('Y-m-d')),
                Carbon::parse($f ?? today()->format('Y-m-d')),
            ],
            default => [today()->startOfMonth(), today()->endOfMonth()],
        };
    }
}
