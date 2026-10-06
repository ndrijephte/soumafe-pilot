<?php
namespace App\Http\Controllers;

use App\Models\{Chauffeur, Depense, MaintenancePreventive, Objectif, Panne, Penalite, Recouvrement, RecetteVehicule, TypeParc, User, Vehicule, VersementRecouvrement};
use App\Services\ExerciceParc;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};

class ReportingParcController extends Controller
{
    private const PANNES_OUVERTES = ['declaree', 'en_diagnostic', 'en_intervention'];

    public function index(Request $r)
    {
        abort_unless($r->user()->peutGererParc(), 403);

        // ═══════ PÉRIODE (exercices du 05 au 04) ═══════
        $periode = match ($r->get('periode', 'exercice')) {
            'trois_exercices', 'trimestre' => 'trois_exercices',
            'personnalise' => 'personnalise',
            default => 'exercice',
        };
        $exercices = ExerciceParc::liste(18);
        $exercice = $r->get('exercice');
        if ($r->get('periode') === 'mois_precedent') $exercice = ExerciceParc::decaler(ExerciceParc::cle(), -1);
        if (!isset($exercices[$exercice])) $exercice = ExerciceParc::cle();

        [$debut, $fin, $libellePeriode, $debutPrec, $finPrec, $libellePrec] = $this->bornes($periode, $exercice, $r->get('date_debut'), $r->get('date_fin'));

        // ═══════ PÉRIMÈTRE ═══════
        $categorie = in_array($r->get('categorie'), ['taxi', 'vtc'], true) ? $r->get('categorie') : 'tous';
        $filtreVehicule = $r->get('vehicule_id');
        $filtreChauffeur = $r->get('chauffeur_id');

        $vehicules = Vehicule::query()
            ->when($categorie !== 'tous', fn($q) => $q->where('categorie', $categorie))
            ->when($filtreVehicule, fn($q) => $q->where('id', $filtreVehicule))
            ->orderBy('immatriculation')->get();
        $vehiculeIds = $vehicules->pluck('id');
        $perimetreComplet = $categorie === 'tous' && !$filtreVehicule;

        $tousVehicules = Vehicule::orderBy('immatriculation')->get();
        $tousChauffeurs = Schema::hasTable('chauffeurs') ? Chauffeur::orderBy('nom')->get() : collect();
        $nomChauffeur = $filtreChauffeur ? $tousChauffeurs->firstWhere('id', $filtreChauffeur)?->nomComplet() : null;

        // ═══════ INDICATEURS CLÉS + COMPARAISON ═══════
        $kpi = $this->indicateurs($vehiculeIds, $perimetreComplet, $debut, $fin, $nomChauffeur);
        $kpiPrec = $this->indicateurs($vehiculeIds, $perimetreComplet, $debutPrec, $finPrec, $nomChauffeur);

        // ═══════ RENTABILITÉ PAR VÉHICULE ═══════
        $parVehicule = $this->rentabiliteVehicules($vehicules, $debut, $fin, $nomChauffeur);

        // ═══════ PERFORMANCE CHAUFFEURS ═══════
        $chauffeurs = $this->performanceChauffeurs($vehiculeIds, $debut, $fin, $nomChauffeur);

        // ═══════ ÉVOLUTION : RECETTES, OBJECTIF, DÉPENSES BUREAU ET VÉHICULES ═══════
        $evolution = $this->evolution($vehiculeIds, $perimetreComplet, $debut, $fin, $nomChauffeur);

        // ═══════ CHARGES : RÉPARTITION ═══════
        $typesDepenseLabels = TypeParc::depenses()->pluck('libelle', 'code')->toArray();
        $depensesParType = Schema::hasTable('depenses')
            ? $this->depenses($vehiculeIds, $perimetreComplet, $debut, $fin)->where('type_depense', '!=', 'maintenance')
                ->selectRaw('type_depense, SUM(montant) as total')->groupBy('type_depense')->orderByDesc('total')->get()
                ->map(fn($d) => ['type' => $typesDepenseLabels[$d->type_depense] ?? ucfirst(str_replace('_', ' ', $d->type_depense)), 'total' => (int) $d->total])
            : collect();

        $pannesUrgence = Panne::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_declaration', [$debut, $fin])
            ->selectRaw('urgence, COUNT(*) as nb, SUM(cout_reel) as cout')->groupBy('urgence')->get()
            ->keyBy('urgence');

        $penalitesType = collect();
        if (Schema::hasTable('penalites')) {
            $libellesPen = TypeParc::where('categorie', 'penalite')->pluck('libelle', 'code');
            $penalitesType = Penalite::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_infraction', [$debut, $fin])
                ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
                ->selectRaw('type, COUNT(*) as nb, SUM(montant) as montant')->groupBy('type')->orderByDesc('montant')->get()
                ->map(fn($p) => ['type' => $libellesPen[$p->type] ?? ucfirst(str_replace('_', ' ', $p->type)), 'nb' => (int) $p->nb, 'montant' => (int) $p->montant]);
        }

        // ═══════ À TRAITER (état actuel, pas seulement la période) ═══════
        $aTraiter = $this->aTraiter($vehiculeIds, $parVehicule, $chauffeurs, $nomChauffeur);
        $inconnus = $nomChauffeur ? [] : $this->nomsInconnus($vehiculeIds, $debut, $fin);
        if ($inconnus) {
            array_unshift($aTraiter, ['niveau' => 'ko', 'onglet' => 'recettes',
                'titre' => array_sum(array_column($inconnus, 'nb')) . ' saisie(s) avec un nom de chauffeur inconnu',
                'detail' => collect($inconnus)->map(fn($i) => "« {$i['nom']} » ({$i['nb']} {$i['source']})")->join(', ')
                    . '. Ces noms ne figurent pas dans le formulaire Chauffeurs : corriger les saisies (les montants restent comptés dans les totaux, pas dans la performance des chauffeurs).']);
        }

        // ═══════ DÉTAIL JOUR PAR JOUR (filtre véhicule ou chauffeur) ═══════
        $detailJour = collect();
        $filtreNom = $nomChauffeur ?? ($filtreVehicule ? $vehicules->first()?->immatriculation : null);
        if ($filtreNom) {
            $detailJour = RecetteVehicule::with('vehicule')
                ->whereIn('vehicule_id', $vehiculeIds)
                ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
                ->whereBetween('date', [$debut, $fin])->orderBy('date')->get();
        }

        // ═══════ OKR (exercice courant) ═══════
        [$okrGestionnaire, $okrChauffeurs, $periodeOkr] = $this->okr($r);

        $gestionnaires = User::where('actif', true)->where('delegation_parc', true)->get()->map(fn($u) => $u->nomComplet());

        return view('reporting.parc', compact(
            'periode', 'exercice', 'exercices', 'debut', 'fin', 'libellePeriode', 'libellePrec',
            'categorie', 'filtreVehicule', 'filtreChauffeur', 'tousVehicules', 'tousChauffeurs',
            'kpi', 'kpiPrec', 'parVehicule', 'chauffeurs', 'evolution',
            'depensesParType', 'pannesUrgence', 'penalitesType', 'aTraiter',
            'detailJour', 'filtreNom', 'okrGestionnaire', 'okrChauffeurs', 'periodeOkr', 'gestionnaires'
        ));
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string, 3: Carbon, 4: Carbon, 5: string} */
    private function bornes(string $periode, string $exercice, ?string $d, ?string $f): array
    {
        if ($periode === 'personnalise') {
            $debut = Carbon::parse($d ?: ExerciceParc::courant()[0])->startOfDay();
            $fin = Carbon::parse($f ?: today())->endOfDay();
            if ($fin->lt($debut)) [$debut, $fin] = [$fin->copy()->startOfDay(), $debut->copy()->endOfDay()];
            $nbJours = $debut->diffInDays($fin) + 1;
            $finPrec = $debut->copy()->subDay()->endOfDay();
            $debutPrec = $finPrec->copy()->subDays($nbJours - 1)->startOfDay();
            return [$debut, $fin, 'du ' . $debut->format('d/m/Y') . ' au ' . $fin->format('d/m/Y'),
                $debutPrec, $finPrec, 'les ' . $nbJours . ' jours précédents'];
        }

        if ($periode === 'trois_exercices') {
            $premier = ExerciceParc::decaler($exercice, -2);
            [$debut] = ExerciceParc::bornes($premier);
            [, $fin] = ExerciceParc::bornes($exercice);
            [$debutPrec] = ExerciceParc::bornes(ExerciceParc::decaler($premier, -3));
            [, $finPrec] = ExerciceParc::bornes(ExerciceParc::decaler($premier, -1));
            return [$debut, $fin, 'exercices ' . ExerciceParc::nom($premier) . ' à ' . ExerciceParc::nom($exercice) . ' (' . $debut->format('d/m/Y') . ' au ' . $fin->format('d/m/Y') . ')',
                $debutPrec, $finPrec, 'les 3 exercices précédents'];
        }

        [$debut, $fin] = ExerciceParc::bornes($exercice);
        $prec = ExerciceParc::decaler($exercice, -1);
        [$debutPrec, $finPrec] = ExerciceParc::bornes($prec);
        return [$debut, $fin, 'exercice ' . ExerciceParc::libelle($exercice), $debutPrec, $finPrec, 'exercice ' . ExerciceParc::nom($prec)];
    }

    /** Dépenses du périmètre : toutes si aucun filtre véhicule/catégorie, sinon celles des véhicules filtrés. */
    private function depenses(Collection $vehiculeIds, bool $perimetreComplet, Carbon $debut, Carbon $fin)
    {
        return Depense::whereBetween('date_depense', [$debut, $fin])
            ->when(!$perimetreComplet, fn($q) => $q->whereIn('vehicule_id', $vehiculeIds));
    }

    /** Recouvrements réellement payés par les chauffeurs (versements « chauffeur »). */
    private function versementsChauffeurs(Collection $vehiculeIds, Carbon $debut, Carbon $fin, ?string $nomChauffeur)
    {
        return VersementRecouvrement::join('recouvrements', 'versements_recouvrement.recouvrement_id', '=', 'recouvrements.id')
            ->whereIn('recouvrements.vehicule_id', $vehiculeIds)
            ->when($nomChauffeur, fn($q) => $q->where('recouvrements.chauffeur_nom', $nomChauffeur))
            ->where(fn($q) => $q->where('versements_recouvrement.source_paiement', 'chauffeur')->orWhereNull('versements_recouvrement.source_paiement'))
            ->whereBetween('versements_recouvrement.date_versement', [$debut, $fin]);
    }

    private function avecRecouvrements(): bool
    {
        return Schema::hasTable('versements_recouvrement') && Schema::hasTable('recouvrements');
    }

    private function indicateurs(Collection $vehiculeIds, bool $perimetreComplet, Carbon $debut, Carbon $fin, ?string $nomChauffeur): array
    {
        $rec = RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date', [$debut, $fin])
            ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
            ->selectRaw('COALESCE(SUM(recette),0) as recette, COALESCE(SUM(objectif),0) as objectif, COUNT(*) as jours, COALESCE(SUM(CASE WHEN recette >= objectif THEN 1 ELSE 0 END),0) as jours_atteints')
            ->first();
        $recouvrement = $this->avecRecouvrements()
            ? (int) $this->versementsChauffeurs($vehiculeIds, $debut, $fin, $nomChauffeur)->sum('versements_recouvrement.montant') : 0;

        $pannes = Panne::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_declaration', [$debut, $fin])
            ->selectRaw('COUNT(*) as nb, COALESCE(SUM(cout_reel),0) as cout')->first();
        $penalites = Schema::hasTable('penalites')
            ? Penalite::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_infraction', [$debut, $fin])
                ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
                ->selectRaw('COUNT(*) as nb, COALESCE(SUM(montant),0) as montant')->first()
            : (object) ['nb' => 0, 'montant' => 0];

        $maintenance = 0;
        $autresDepenses = 0;
        if (Schema::hasTable('depenses')) {
            $maintenance = (int) $this->depenses($vehiculeIds, $perimetreComplet, $debut, $fin)->where('type_depense', 'maintenance')->sum('montant');
            $autresDepenses = (int) $this->depenses($vehiculeIds, $perimetreComplet, $debut, $fin)->where('type_depense', '!=', 'maintenance')->sum('montant');
        }
        // Vue chauffeur : seules ses recettes et pénalités lui sont imputables
        if ($nomChauffeur) { $pannes->cout = 0; $pannes->nb = 0; $maintenance = 0; $autresDepenses = 0; }

        $recettes = (int) $rec->recette + $recouvrement;
        $charges = (int) $pannes->cout + $maintenance + (int) $penalites->montant + $autresDepenses;

        return [
            'recettes' => $recettes,
            'recette_brute' => (int) $rec->recette,
            'recouvrement' => $recouvrement,
            'objectif' => (int) $rec->objectif,
            'taux' => $rec->objectif > 0 ? round($recettes / $rec->objectif * 100, 2) : 0,
            'jours' => (int) $rec->jours,
            'jours_atteints' => (int) $rec->jours_atteints,
            'pannes' => (int) $pannes->cout,
            'nb_pannes' => (int) $pannes->nb,
            'maintenance' => $maintenance,
            'penalites' => (int) $penalites->montant,
            'nb_penalites' => (int) $penalites->nb,
            'autres_depenses' => $autresDepenses,
            'charges' => $charges,
            'resultat' => $recettes - $charges,
            'marge' => $recettes > 0 ? round(($recettes - $charges) / $recettes * 100, 2) : null,
        ];
    }

    private function rentabiliteVehicules(Collection $vehicules, Carbon $debut, Carbon $fin, ?string $nomChauffeur): Collection
    {
        $ids = $vehicules->pluck('id');
        $grp = fn($q, string $col) => $q->whereIn($col, $ids);

        $rec = $grp(RecetteVehicule::query(), 'vehicule_id')->whereBetween('date', [$debut, $fin])
            ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
            ->selectRaw('vehicule_id, SUM(recette) as recette, SUM(objectif) as objectif, COUNT(*) as jours')
            ->groupBy('vehicule_id')->get()->keyBy('vehicule_id');
        $recouv = $this->avecRecouvrements()
            ? $this->versementsChauffeurs($ids, $debut, $fin, $nomChauffeur)
                ->selectRaw('recouvrements.vehicule_id as vid, SUM(versements_recouvrement.montant) as total')
                ->groupBy('recouvrements.vehicule_id')->pluck('total', 'vid')
            : collect();
        $pannes = $nomChauffeur ? collect() : $grp(Panne::query(), 'vehicule_id')->whereBetween('date_declaration', [$debut, $fin])
            ->selectRaw('vehicule_id, SUM(cout_reel) as cout, COUNT(*) as nb')->groupBy('vehicule_id')->get()->keyBy('vehicule_id');
        $pen = Schema::hasTable('penalites')
            ? $grp(Penalite::query(), 'vehicule_id')->whereBetween('date_infraction', [$debut, $fin])
                ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
                ->selectRaw('vehicule_id, SUM(montant) as total')->groupBy('vehicule_id')->pluck('total', 'vehicule_id')
            : collect();
        $dep = Schema::hasTable('depenses') && !$nomChauffeur
            ? $grp(Depense::query(), 'vehicule_id')->whereBetween('date_depense', [$debut, $fin])
                ->selectRaw("vehicule_id, SUM(CASE WHEN type_depense = 'maintenance' THEN montant ELSE 0 END) as maint, SUM(CASE WHEN type_depense <> 'maintenance' THEN montant ELSE 0 END) as autres")
                ->groupBy('vehicule_id')->get()->keyBy('vehicule_id')
            : collect();

        return $vehicules->map(function ($v) use ($rec, $recouv, $pannes, $pen, $dep) {
            $r = $rec[$v->id] ?? null;
            $recettes = (int) ($r->recette ?? 0) + (int) ($recouv[$v->id] ?? 0);
            $objectif = (int) ($r->objectif ?? 0);
            $pannesCout = (int) ($pannes[$v->id]->cout ?? 0);
            $maint = (int) ($dep[$v->id]->maint ?? 0);
            $autres = (int) ($dep[$v->id]->autres ?? 0);
            $penal = (int) ($pen[$v->id] ?? 0);
            $charges = $pannesCout + $maint + $penal + $autres;
            $resultat = $recettes - $charges;
            return [
                'id' => $v->id, 'immat' => $v->immatriculation, 'categorie' => $v->categorieLabel(),
                'statut' => $v->statut, 'statut_label' => $v->statutLabel(),
                'jours' => (int) ($r->jours ?? 0), 'recettes' => $recettes, 'objectif' => $objectif,
                'taux' => $objectif > 0 ? round($recettes / $objectif * 100, 2) : null,
                'pannes' => $pannesCout, 'nb_pannes' => (int) ($pannes[$v->id]->nb ?? 0),
                'maintenance' => $maint, 'penalites' => $penal, 'autres' => $autres,
                'charges' => $charges, 'resultat' => $resultat,
                'verdict' => $resultat > 0 ? 'rentable' : ($resultat < 0 ? 'deficit' : 'equilibre'),
            ];
        })
        // Véhicules sans aucune activité ni service sur la période : hors classement
        ->filter(fn($x) => $x['statut'] === 'en_service' || $x['recettes'] > 0 || $x['charges'] > 0)
        ->sortBy('resultat')->values();
    }

    /** Uniquement les chauffeurs du formulaire Chauffeurs (prénom + nom) ; tout autre nom est une anomalie signalée à part. */
    private function performanceChauffeurs(Collection $vehiculeIds, Carbon $debut, Carbon $fin, ?string $nomChauffeur): Collection
    {
        if (!Schema::hasTable('chauffeurs')) return collect();
        $liste = Chauffeur::orderBy('nom')->get()
            ->when($nomChauffeur, fn($c) => $c->filter(fn($ch) => $ch->nomComplet() === $nomChauffeur));
        $noms = $liste->map->nomComplet();

        $rec = RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date', [$debut, $fin])
            ->whereIn('chauffeur', $noms)
            ->selectRaw('chauffeur, SUM(recette) as recette, SUM(objectif) as objectif, COUNT(*) as jours,
                SUM(CASE WHEN recette >= objectif THEN 1 ELSE 0 END) as jours_atteints,
                SUM(CASE WHEN motif_absence IS NOT NULL AND motif_absence <> \'\' THEN 1 ELSE 0 END) as absences,
                COUNT(DISTINCT vehicule_id) as nb_vehicules')
            ->groupBy('chauffeur')->get()->keyBy('chauffeur');
        $recouv = $this->avecRecouvrements()
            ? $this->versementsChauffeurs($vehiculeIds, $debut, $fin, $nomChauffeur)
                ->selectRaw('recouvrements.chauffeur_nom as nom, SUM(versements_recouvrement.montant) as total')
                ->groupBy('recouvrements.chauffeur_nom')->pluck('total', 'nom')
            : collect();
        $pen = Schema::hasTable('penalites')
            ? Penalite::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_infraction', [$debut, $fin])
                ->selectRaw('chauffeur, SUM(montant) as total, COUNT(*) as nb')->groupBy('chauffeur')->get()->keyBy('chauffeur')
            : collect();
        $soldes = Schema::hasTable('recouvrements')
            ? Recouvrement::where('statut', 'actif')->selectRaw('chauffeur_nom, SUM(solde) as solde')->groupBy('chauffeur_nom')->pluck('solde', 'chauffeur_nom')
            : collect();

        return $liste->map(function ($ch) use ($rec, $recouv, $pen, $soldes) {
            $nom = $ch->nomComplet();
            $c = $rec[$nom] ?? null;
            $objectif = (int) ($c->objectif ?? 0);
            $recettes = (int) ($c->recette ?? 0) + (int) ($recouv[$nom] ?? 0);
            $jours = (int) ($c->jours ?? 0);
            return [
                'nom' => $nom, 'statut' => $ch->statut,
                'jours' => $jours, 'jours_atteints' => (int) ($c->jours_atteints ?? 0), 'absences' => (int) ($c->absences ?? 0),
                'nb_vehicules' => (int) ($c->nb_vehicules ?? 0),
                'recettes' => $recettes, 'objectif' => $objectif,
                'ecart' => $recettes - $objectif,
                'taux' => $objectif > 0 ? round($recettes / $objectif * 100, 2) : null,
                'moyenne' => $jours > 0 ? (int) round($recettes / $jours) : 0,
                'penalites' => (int) ($pen[$nom]->total ?? 0), 'nb_penalites' => (int) ($pen[$nom]->nb ?? 0),
                'dette' => (int) ($soldes[$nom] ?? 0),
            ];
        })
        // Chauffeur inactif sans aucune activité sur la période : rien à évaluer
        ->filter(fn($c) => $c['statut'] === 'actif' || $c['jours'] > 0 || $c['penalites'] > 0 || $c['dette'] > 0)
        // Du taux le plus faible au plus élevé ; les chauffeurs sans saisie en fin de liste
        ->sortBy(fn($c) => $c['taux'] ?? 100000)->values();
    }

    /** Saisies portant un nom de chauffeur absent du formulaire Chauffeurs (erreur de données). */
    private function nomsInconnus(Collection $vehiculeIds, Carbon $debut, Carbon $fin): array
    {
        if (!Schema::hasTable('chauffeurs')) return [];
        $valides = Chauffeur::nomsValides();
        $res = [];
        $sources = ['recettes' => [RecetteVehicule::query(), 'chauffeur', 'date']];
        if (Schema::hasTable('penalites')) $sources['pénalités'] = [Penalite::query(), 'chauffeur', 'date_infraction'];
        foreach ($sources as $lib => [$q, $col, $date]) {
            $lignes = $q->whereIn('vehicule_id', $vehiculeIds)->whereBetween($date, [$debut, $fin])
                ->whereNotNull($col)->where($col, '<>', '')->whereNotIn($col, $valides)
                ->selectRaw("{$col} as nom, COUNT(*) as nb")->groupBy($col)->get();
            foreach ($lignes as $l) $res[] = ['source' => $lib, 'nom' => $l->nom, 'nb' => (int) $l->nb];
        }
        return $res;
    }

    /**
     * Série jour par jour (ou semaine par semaine au-delà de 100 jours) jusqu'à aujourd'hui :
     * recettes, objectif, dépenses bureau (hors maintenance) et dépenses véhicules (pannes + maintenance + pénalités).
     */
    private function evolution(Collection $vehiculeIds, bool $perimetreComplet, Carbon $debut, Carbon $fin, ?string $nomChauffeur): array
    {
        $finEffective = $fin->copy()->min(today()->endOfDay());
        if ($finEffective->lt($debut)) return ['points' => [], 'par_semaine' => false];

        $parJour = fn($q, string $col, string $somme) => $q->selectRaw("DATE({$col}) as j, SUM({$somme}) as t")
            ->groupBy(DB::raw("DATE({$col})"))->pluck('t', 'j')->map(fn($v) => (int) $v);

        $recettes = RecetteVehicule::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date', [$debut, $finEffective])
            ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
            ->selectRaw('DATE(date) as j, SUM(recette) as r, SUM(objectif) as o')->groupBy(DB::raw('DATE(date)'))->get()->keyBy('j');
        $penalites = Schema::hasTable('penalites')
            ? $parJour(Penalite::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_infraction', [$debut, $finEffective])
                ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur)), 'date_infraction', 'montant')
            : collect();
        $pannes = collect(); $maint = collect(); $bureau = collect();
        if (!$nomChauffeur) {
            $pannes = $parJour(Panne::whereIn('vehicule_id', $vehiculeIds)->whereBetween('date_declaration', [$debut, $finEffective]), 'date_declaration', 'cout_reel');
            if (Schema::hasTable('depenses')) {
                $maint = $parJour($this->depenses($vehiculeIds, $perimetreComplet, $debut, $finEffective)->where('type_depense', 'maintenance'), 'date_depense', 'montant');
                $bureau = $parJour($this->depenses($vehiculeIds, $perimetreComplet, $debut, $finEffective)->where('type_depense', '!=', 'maintenance'), 'date_depense', 'montant');
            }
        }

        $parSemaine = $debut->diffInDays($finEffective) > 100;
        $points = [];
        for ($d = $debut->copy()->startOfDay(); $d->lte($finEffective); $d->addDay()) {
            $j = $d->toDateString();
            $cle = $parSemaine ? $d->copy()->startOfWeek()->toDateString() : $j;
            $points[$cle] ??= ['date' => $cle, 'recette' => 0, 'objectif' => 0, 'bureau' => 0, 'vehicule' => 0, 'saisi' => false];
            $p = &$points[$cle];
            if (isset($recettes[$j])) { $p['recette'] += (int) $recettes[$j]->r; $p['objectif'] += (int) $recettes[$j]->o; $p['saisi'] = true; }
            $p['bureau'] += $bureau[$j] ?? 0;
            $p['vehicule'] += ($pannes[$j] ?? 0) + ($maint[$j] ?? 0) + ($penalites[$j] ?? 0);
            unset($p);
        }
        return ['points' => array_values($points), 'par_semaine' => $parSemaine];
    }

    /** Liste priorisée de ce qui demande une décision ou une action. */
    private function aTraiter(Collection $vehiculeIds, Collection $parVehicule, Collection $chauffeurs, ?string $nomChauffeur): array
    {
        $points = [];
        $f = fn($n) => number_format($n, 0, ',', ' ') . ' F';

        $deficit = $parVehicule->where('verdict', 'deficit');
        if ($deficit->isNotEmpty()) {
            $points[] = ['niveau' => 'ko', 'onglet' => 'vehicules',
                'titre' => $deficit->count() . ' véhicule(s) en déficit sur la période',
                'detail' => $deficit->take(4)->map(fn($v) => $v['immat'] . ' (' . $f($v['resultat']) . ')')->join(', ')
                    . '. À examiner : coûts de panne, objectif, affectation du chauffeur.'];
        }

        $sousObjectif = $chauffeurs->filter(fn($c) => $c['taux'] !== null && $c['taux'] < 80);
        if ($sousObjectif->isNotEmpty()) {
            $points[] = ['niveau' => 'warn', 'onglet' => 'chauffeurs',
                'titre' => $sousObjectif->count() . ' chauffeur(s) sous 80 % de l\'objectif',
                'detail' => $sousObjectif->take(4)->map(fn($c) => $c['nom'] . ' (' . number_format($c['taux'], 2, ',', ' ') . ' %)')->join(', ') . '.'];
        }

        $pannesOuvertes = Panne::whereIn('vehicule_id', $vehiculeIds)->whereIn('statut', self::PANNES_OUVERTES)->get(['urgence', 'date_declaration']);
        if (!$nomChauffeur && $pannesOuvertes->isNotEmpty()) {
            $graves = $pannesOuvertes->whereIn('urgence', ['haute', 'critique'])->count();
            $anciennes = $pannesOuvertes->filter(fn($p) => $p->date_declaration && $p->date_declaration->lt(today()->subDays(7)))->count();
            $points[] = ['niveau' => $graves > 0 ? 'ko' : 'warn', 'onglet' => 'pannes',
                'titre' => $pannesOuvertes->count() . ' panne(s) non clôturée(s)',
                'detail' => trim(($graves ? "{$graves} haute(s)/critique(s). " : '') . ($anciennes ? "{$anciennes} ouverte(s) depuis plus de 7 jours." : ''))];
        }

        if (!$nomChauffeur) {
            $maint = MaintenancePreventive::whereIn('vehicule_id', $vehiculeIds)->where(fn($q) => $q->whereNull('statut')->orWhere('statut', '!=', 'realisee'))
                ->whereNotNull('prochaine_echeance')->whereDate('prochaine_echeance', '<=', today()->addDays(15))->get(['prochaine_echeance']);
            $retard = $maint->filter(fn($m) => $m->prochaine_echeance->lt(today()))->count();
            $proche = $maint->count() - $retard;
            if ($maint->isNotEmpty()) {
                $points[] = ['niveau' => $retard ? 'ko' : 'info', 'onglet' => 'maint',
                    'titre' => ($retard ? "{$retard} maintenance(s) en retard" : '') . ($retard && $proche ? ', ' : '') . ($proche ? "{$proche} dans les 15 jours" : ''),
                    'detail' => 'Planifier les interventions pour éviter des pannes plus coûteuses.'];
            }
        }

        if (Schema::hasTable('penalites')) {
            $impayees = Penalite::whereIn('vehicule_id', $vehiculeIds)->where('statut', '!=', 'payee')
                ->when($nomChauffeur, fn($q) => $q->where('chauffeur', $nomChauffeur))
                ->selectRaw('COUNT(*) as nb, COALESCE(SUM(montant - COALESCE(montant_paye,0)),0) as solde')->first();
            if ($impayees->nb > 0) {
                $points[] = ['niveau' => 'warn', 'onglet' => 'penal',
                    'titre' => $impayees->nb . ' pénalité(s) non soldée(s) : ' . $f($impayees->solde),
                    'detail' => 'Relancer le paiement ou décider de la prise en charge.'];
            }
        }

        if (Schema::hasTable('recouvrements')) {
            $recouv = Recouvrement::whereIn('vehicule_id', $vehiculeIds)->where('statut', 'actif')
                ->when($nomChauffeur, fn($q) => $q->where('chauffeur_nom', $nomChauffeur))
                ->selectRaw('COUNT(*) as nb, COALESCE(SUM(solde),0) as solde')->first();
            if ($recouv->nb > 0) {
                $points[] = ['niveau' => 'warn', 'onglet' => 'recouv',
                    'titre' => $f($recouv->solde) . ' à recouvrer auprès des chauffeurs',
                    'detail' => $recouv->nb . ' créance(s) active(s).'];
            }
        }

        $immobilises = Vehicule::whereIn('id', $vehiculeIds)->where('statut', 'immobilise')->pluck('immatriculation');
        if (!$nomChauffeur && $immobilises->isNotEmpty()) {
            $points[] = ['niveau' => 'info', 'onglet' => 'vehicules',
                'titre' => $immobilises->count() . ' véhicule(s) immobilisé(s)',
                'detail' => $immobilises->take(5)->join(', ') . ' : ne rapportent rien tant qu\'ils sont à l\'arrêt.'];
        }

        $ordre = ['ko' => 0, 'warn' => 1, 'info' => 2];
        usort($points, fn($a, $b) => $ordre[$a['niveau']] <=> $ordre[$b['niveau']]);
        return $points;
    }

    private function okr(Request $r): array
    {
        $periodeOkr = ExerciceParc::cle();
        $okrGestionnaire = null;
        $okrChauffeurs = collect();
        if (!Schema::hasTable('objectifs') || !Schema::hasColumn('objectifs', 'type')) return [$okrGestionnaire, $okrChauffeurs, $periodeOkr];

        $mesObj = Objectif::with('resultats')->where('user_id', $r->user()->id)->where('periode', $periodeOkr)->get();
        if ($mesObj->isNotEmpty()) {
            $okrGestionnaire = [
                'score' => Objectif::scoreGroupe($mesObj),
                'total' => $mesObj->count(),
                'atteints' => $mesObj->where('evaluation', 'atteint')->count(),
            ];
        }
        if (Schema::hasTable('chauffeurs') && Schema::hasColumn('chauffeurs', 'user_id')) {
            $liens = Chauffeur::where('statut', 'actif')->whereNotNull('user_id')->get();
            $objs = Objectif::with('resultats')->whereIn('user_id', $liens->pluck('user_id'))->where('periode', $periodeOkr)->get()->groupBy('user_id');
            foreach ($liens as $ch) {
                $o = $objs[$ch->user_id] ?? collect();
                if ($o->isEmpty()) continue;
                $okrChauffeurs->push(['nom' => $ch->nomComplet(), 'score' => Objectif::scoreGroupe($o), 'total' => $o->count(), 'atteints' => $o->where('evaluation', 'atteint')->count()]);
            }
            $okrChauffeurs = $okrChauffeurs->sortByDesc('score')->values();
        }
        return [$okrGestionnaire, $okrChauffeurs, $periodeOkr];
    }
}
