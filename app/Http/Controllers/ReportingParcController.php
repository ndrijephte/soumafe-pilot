<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, Chauffeur, MaintenancePreventive, Panne, Penalite, RecetteVehicule, TypeParc, User, Vehicule};
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

                    // Recettes individuelles : filtrées par nom du chauffeur
                    $recQ = RecetteVehicule::where('chauffeur', $nomComplet)
                        ->whereBetween('date', [$debut, $fin]);
                    if ($cvIds->isNotEmpty()) {
                        $recQ->whereIn('vehicule_id', $cvIds);
                    }
                    $recTotal = (int) (clone $recQ)->sum('recette');
                    $objTotal = (int) (clone $recQ)->sum('objectif');
                    $nbJours = (clone $recQ)->count();

                    if ($nbJours === 0) return null;

                    // Pannes et pénalités restent liées aux véhicules affectés
                    $coutP = 0; $nbP = 0; $montPen = 0; $nbPen = 0;
                    if ($cvIds->isNotEmpty()) {
                        $coutP = (int) Panne::whereIn('vehicule_id', $cvIds)
                            ->whereBetween('date_declaration', [$debut, $fin])->sum('cout_reel');
                        $nbP = Panne::whereIn('vehicule_id', $cvIds)
                            ->whereBetween('date_declaration', [$debut, $fin])->count();
                        if (Schema::hasTable('penalites')) {
                            $montPen = (int) Penalite::whereIn('vehicule_id', $cvIds)
                                ->where('chauffeur', $nomComplet)
                                ->whereBetween('date_infraction', [$debut, $fin])->sum('montant');
                            $nbPen = Penalite::whereIn('vehicule_id', $cvIds)
                                ->where('chauffeur', $nomComplet)
                                ->whereBetween('date_infraction', [$debut, $fin])->count();
                        }
                    }
                    $resultat = $recTotal - $coutP - $montPen;
                    return [
                        'nom' => $nomComplet,
                        'statut' => $c->statut,
                        'nb_vehicules' => $cvIds->count(),
                        'vehicules' => $c->vehicules->whereIn('id', $cvIds)->pluck('immatriculation')->join(', '),
                        'recette' => $recTotal,
                        'objectif' => $objTotal,
                        'nb_jours' => $nbJours,
                        'taux' => $objTotal > 0 ? round(($recTotal / $objTotal) * 100) : 0,
                        'cout_pannes' => $coutP,
                        'nb_pannes' => $nbP,
                        'montant_penalites' => $montPen,
                        'nb_penalites' => $nbPen,
                        'resultat' => $resultat,
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

        return view('reporting.parc', compact(
            'periode', 'debut', 'fin', 'categorie',
            'recetteTotal', 'objectifTotal', 'tauxRecette',
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
            'detailJour', 'filtreNom'
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
