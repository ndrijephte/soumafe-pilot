<?php
namespace App\Http\Controllers;

use App\Models\{Absence, ConfirmationTache, Evenement, JourFerie, JourSaisie, Objectif, Pointage, Projet, Sprint, Tache, User};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportingController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $periode = $r->get('periode', 'exercice');
        [$debut, $fin] = $this->bornesPeriode($periode, $r->get('date_debut'), $r->get('date_fin'));
        $exercice = $periode === 'exercice';

        // ─── KPI globaux sur la période ───
        $terminees = 0; $creees = 0; $enRetardClotures = 0; $anticipees = 0; $aTemps = 0;
        $tauxGlobal = 0; $bloquees = 0; $enRetardOuvert = 0;

        if (Schema::hasTable('taches')) {
            $tachesPeriode = $this->tachesTermineesPeriode($debut, $fin, $exercice);
            $terminees = (clone $tachesPeriode)->count();
            $creees = Tache::whereBetween('created_at', [$debut, $fin])->count();

            if (Schema::hasColumn('taches', 'qualification')) {
                $enRetardClotures = (clone $tachesPeriode)->where('qualification', 'en_retard')->count();
                $anticipees = (clone $tachesPeriode)->where('qualification', 'anticipee')->count();
                $aTemps = (clone $tachesPeriode)->where('qualification', 'a_temps')->count();
            }

            $totalActives = Tache::whereIn('statut', ['a_faire', 'en_cours', 'bloque', 'termine'])->count();
            $totalTerminees = Tache::where('statut', 'termine')->count();
            $tauxGlobal = $totalActives > 0 ? round(($totalTerminees / $totalActives) * 100) : 0;

            $bloquees = Tache::where('statut', 'bloque')->count();
            $enRetardOuvert = Tache::enRetard()->count();
        }

        // ─── Par collaborateur ───
        $collaborateurs = User::where('actif', true)
            ->whereNotIn('role', ['direction', 'invite'])
            ->with('pole')
            ->orderBy('name')->get()
            ->map(function ($u) use ($debut, $fin, $exercice) {
                $total = Tache::where('user_id', $u->id)->count();
                $term = Tache::where('user_id', $u->id)->where('statut', 'termine')->count();
                $termPeriode = $this->tachesTermineesPeriode($debut, $fin, $exercice)->where('user_id', $u->id)->count();
                $retard = Tache::where('user_id', $u->id)->enRetard()->count();
                $bloq = Tache::where('user_id', $u->id)->where('statut', 'bloque')->count();

                // Score OKR unifié (individuel + projet) du mois en cours
                $scoreObj = null;
                $nbObjAtteints = 0;
                $nbObjTotal = 0;
                if (Schema::hasTable('objectifs') && Schema::hasColumn('objectifs', 'type')) {
                    $moisCourant = Objectif::periodeCourante();
                    $tousObj = Objectif::with('resultats')
                        ->where('user_id', $u->id)
                        ->where('periode', $moisCourant)->get();
                    if ($tousObj->isNotEmpty()) {
                        $scoreObj = Objectif::scoreGroupe($tousObj);
                        $nbObjTotal = $tousObj->count();
                        $nbObjAtteints = $tousObj->where('evaluation', 'atteint')->count();
                    }
                }

                $assiduite = $this->assiduitePeriode($u->id, $debut, $fin);

                // Productivité = tâches terminées sur la période / jours ouvrés
                $joursOuvresPeriode = 0;
                $cp = $debut->copy();
                $ep = $fin->copy()->min(today());
                while ($cp->lte($ep)) {
                    if (\App\Services\CalendrierTravail::estJourTravaille($cp)) $joursOuvresPeriode++;
                    $cp->addDay();
                }
                $productivite = $joursOuvresPeriode > 0 ? round($termPeriode / $joursOuvresPeriode, 1) : 0;

                $u->setAttribute('stats', [
                    'total' => $total,
                    'terminees' => $term,
                    'terminees_periode' => $termPeriode,
                    'en_retard' => $retard,
                    'bloquees' => $bloq,
                    'taux' => $total > 0 ? round(($term / $total) * 100) : 0,
                    'score_objectifs' => $scoreObj,
                    'nb_obj_atteints' => $nbObjAtteints,
                    'nb_obj_total' => $nbObjTotal,
                    'productivite' => $productivite,
                    'assiduite' => $assiduite,
                ]);
                return $u;
            });

        // ─── Par projet ───
        $projets = Projet::where('statut', 'actif')
            ->whereHas('taches')
            ->withCount([
                'taches',
                'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
                'taches as en_retard_count' => fn ($q) => $q->enRetard(),
                'taches as bloquees_count' => fn ($q) => $q->where('statut', 'bloque'),
            ])->orderBy('libelle')->get();

        // ─── Sprints en cours ───
        $sprints = Sprint::where('statut', 'en_cours')->with('projet')->get()->map(function ($s) {
            $total = Tache::where('sprint_id', $s->id)->count();
            $term = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->stats_total = $total;
            $s->stats_terminees = $term;
            $s->progression = $total > 0 ? round(($term / $total) * 100) : 0;
            $s->jours_restants = $s->date_fin ? (int) max(0, now()->diffInDays($s->date_fin, false)) : null;
            return $s;
        });

        // ─── Absences sur la période ───
        $absences = collect();
        if (Schema::hasTable('absences')) {
            $absences = Absence::with(['user', 'declarant'])
                ->where(function ($q) use ($debut, $fin) {
                    $q->whereBetween('date_debut', [$debut, $fin])
                      ->orWhereBetween('date_fin', [$debut, $fin])
                      ->orWhere(function ($q2) use ($debut, $fin) {
                          $q2->whereDate('date_debut', '<=', $debut)
                              ->whereDate('date_fin', '>=', $fin);
                      });
                })
                ->orderByDesc('date_debut')->get();
        }

        // ─── Événements de la période ───
        $evenements = collect();
        if (Schema::hasTable('evenements')) {
            $evenements = Evenement::with('user')
                ->whereBetween('date', [$debut, $fin])
                ->orderByDesc('date')->get();
        }

        // ─── Assiduité pointage ───
        $pointageStats = collect();
        if (Schema::hasTable('pointages')) {
            $collabIds = $collaborateurs->pluck('id');
            $pointages = Pointage::whereBetween('date', [$debut, $fin])
                ->whereIn('user_id', $collabIds)->get();

            $joursSpeciaux = Schema::hasTable('jours_ouvrables_speciaux')
                ? \App\Models\JourOuvrableSpecial::dansLaPeriode($debut, $fin) : collect();

            $pointageStats = $collaborateurs->map(function ($u) use ($pointages, $debut, $fin, $joursSpeciaux) {
                $up = $pointages->where('user_id', $u->id);
                $presents = $up->where('statut', 'present')->count();
                $retards = $up->where('statut', 'retard')->count();
                $sortisTot = $up->where('statut', 'sorti_tot')->count();
                $absents = $up->where('statut', 'absent')->count();
                $totalPointes = $up->count();

                // Jours ouvrés dans la période (+ jours spéciaux)
                $ouvres = 0;
                $c = $debut->copy();
                $endDate = $fin->copy()->min(today());
                while ($c->lte($endDate)) {
                    if (\App\Services\CalendrierTravail::estJourTravaille($c) || $joursSpeciaux->contains($c->toDateString())) $ouvres++;
                    $c->addDay();
                }
                $tauxPresence = $ouvres > 0 ? round((($presents + $retards + $sortisTot) / $ouvres) * 100) : 0;
                $tauxPonctualite = ($presents + $retards + $sortisTot) > 0
                    ? round(($presents / ($presents + $retards + $sortisTot)) * 100) : 0;

                return [
                    'user' => $u,
                    'presents' => $presents,
                    'retards' => $retards,
                    'sortis_tot' => $sortisTot,
                    'absents' => $absents,
                    'ouvres' => $ouvres,
                    'taux_presence' => $tauxPresence,
                    'taux_ponctualite' => $tauxPonctualite,
                ];
            });
        }

        return view('reporting.index', compact(
            'periode', 'debut', 'fin',
            'terminees', 'creees', 'enRetardClotures', 'anticipees', 'aTemps',
            'tauxGlobal', 'bloquees', 'enRetardOuvert',
            'collaborateurs', 'projets', 'sprints', 'absences', 'evenements',
            'pointageStats'
        ));
    }

    /** Vue imprimable (PDF via navigateur) avec colonnes filtrables. */
    public function imprimer(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $periode = $r->get('periode', 'exercice');
        [$debut, $fin] = $this->bornesPeriode($periode, $r->get('date_debut'), $r->get('date_fin'));
        $exercice = $periode === 'exercice';

        // Sections sélectionnées (par défaut toutes)
        $sections = $r->get('sections', ['kpi', 'collaborateurs', 'projets', 'sprints', 'absences', 'evenements']);
        if (is_string($sections)) $sections = explode(',', $sections);

        // Colonnes collaborateurs sélectionnées
        $colsCollab = $r->get('cols_collab', ['nom', 'pole', 'total', 'periode', 'retard', 'bloquees', 'taux', 'objectifs', 'assiduite']);
        if (is_string($colsCollab)) $colsCollab = explode(',', $colsCollab);

        // Mêmes données que index()
        $terminees = 0; $creees = 0; $enRetardClotures = 0; $anticipees = 0; $aTemps = 0;
        $tauxGlobal = 0; $bloquees = 0; $enRetardOuvert = 0;

        if (Schema::hasTable('taches')) {
            $tachesPeriode = $this->tachesTermineesPeriode($debut, $fin, $exercice);
            $terminees = (clone $tachesPeriode)->count();
            $creees = Tache::whereBetween('created_at', [$debut, $fin])->count();
            if (Schema::hasColumn('taches', 'qualification')) {
                $enRetardClotures = (clone $tachesPeriode)->where('qualification', 'en_retard')->count();
                $anticipees = (clone $tachesPeriode)->where('qualification', 'anticipee')->count();
                $aTemps = (clone $tachesPeriode)->where('qualification', 'a_temps')->count();
            }
            $totalActives = Tache::whereIn('statut', ['a_faire', 'en_cours', 'bloque', 'termine'])->count();
            $totalTerminees = Tache::where('statut', 'termine')->count();
            $tauxGlobal = $totalActives > 0 ? round(($totalTerminees / $totalActives) * 100) : 0;
            $bloquees = Tache::where('statut', 'bloque')->count();
            $enRetardOuvert = Tache::enRetard()->count();
        }

        $collaborateurs = User::where('actif', true)
            ->whereNotIn('role', ['direction', 'invite'])
            ->with('pole')->orderBy('name')->get()
            ->map(function ($u) use ($debut, $fin, $exercice) {
                $total = Tache::where('user_id', $u->id)->count();
                $term = Tache::where('user_id', $u->id)->where('statut', 'termine')->count();
                $termPeriode = $this->tachesTermineesPeriode($debut, $fin, $exercice)->where('user_id', $u->id)->count();
                $retard = Tache::where('user_id', $u->id)->enRetard()->count();
                $bloq = Tache::where('user_id', $u->id)->where('statut', 'bloque')->count();
                $scoreObj = null;
                if (Schema::hasTable('objectifs')) {
                    $moisCourant = Objectif::periodeCourante();
                    $objs = Objectif::where('user_id', $u->id)->where('periode', $moisCourant)->where('type', 'individuel')->get();
                    $sommePond = $objs->sum('ponderation');
                    $scoreObj = $sommePond > 0 ? round($objs->sum(fn($o) => $o->avancement * $o->ponderation / 100)) : null;
                }
                $assiduite = $this->assiduitePeriode($u->id, $debut, $fin);
                $u->setAttribute('stats', [
                    'total' => $total, 'terminees' => $term, 'terminees_periode' => $termPeriode,
                    'en_retard' => $retard, 'bloquees' => $bloq,
                    'taux' => $total > 0 ? round(($term / $total) * 100) : 0,
                    'score_objectifs' => $scoreObj, 'assiduite' => $assiduite,
                ]);
                return $u;
            });

        $projets = Projet::where('statut', 'actif')->whereHas('taches')
            ->withCount([
                'taches',
                'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
                'taches as en_retard_count' => fn ($q) => $q->enRetard(),
                'taches as bloquees_count' => fn ($q) => $q->where('statut', 'bloque'),
            ])->orderBy('libelle')->get();

        $sprints = Sprint::where('statut', 'en_cours')->with('projet')->get()->map(function ($s) {
            $total = Tache::where('sprint_id', $s->id)->count();
            $term = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->stats_total = $total;
            $s->stats_terminees = $term;
            $s->progression = $total > 0 ? round(($term / $total) * 100) : 0;
            return $s;
        });



        $absences = collect();
        if (Schema::hasTable('absences')) {
            $absences = Absence::with(['user', 'declarant'])
                ->where(function ($q) use ($debut, $fin) {
                    $q->whereBetween('date_debut', [$debut, $fin])
                      ->orWhereBetween('date_fin', [$debut, $fin])
                      ->orWhere(function ($q2) use ($debut, $fin) {
                          $q2->whereDate('date_debut', '<=', $debut)
                              ->whereDate('date_fin', '>=', $fin);
                      });
                })
                ->orderByDesc('date_debut')->get();
        }

        $evenements = collect();
        if (Schema::hasTable('evenements')) {
            $evenements = Evenement::with('user')->whereBetween('date', [$debut, $fin])->orderByDesc('date')->get();
        }

        return view('reporting.imprimer', compact(
            'periode', 'debut', 'fin', 'sections', 'colsCollab',
            'terminees', 'creees', 'enRetardClotures', 'anticipees', 'aTemps',
            'tauxGlobal', 'bloquees', 'enRetardOuvert',
            'collaborateurs', 'projets', 'sprints', 'absences', 'evenements'
        ));
    }

    /**
     * Tâches terminées imputées à la période. Pour une période d'exercice, une tâche de sprint compte
     * dans la période du sprint (arbitrée s'il est à cheval) ; les autres tâches selon leur date de fin.
     */
    private function tachesTermineesPeriode(Carbon $debut, Carbon $fin, bool $exercice)
    {
        $q = Tache::where('statut', 'termine');
        if (!$exercice) return $q->whereBetween('date_fin_reelle', [$debut, $fin]);
        $sprints = Sprint::whereDate('date_debut', '<=', $fin)->whereDate('date_fin', '>=', $debut)->get()
            ->filter(fn($s) => $s->periodeExercice()?->isSameDay($debut))->pluck('id');
        return $q->where(fn($w) => $w->where(fn($h) => $h->whereNull('sprint_id')->whereBetween('date_fin_reelle', [$debut, $fin]))
            ->orWhereIn('sprint_id', $sprints));
    }

    private function bornesPeriode(string $p, ?string $d, ?string $f): array
    {
        return match ($p) {
            'exercice' => \App\Services\PeriodeExercice::bornes(today()),
            'mois' => [today()->startOfMonth(), today()->endOfMonth()],
            'trimestre' => [today()->firstOfQuarter(), today()->lastOfQuarter()],
            'personnalise' => [
                Carbon::parse($d ?? today()->subDays(30)->format('Y-m-d')),
                Carbon::parse($f ?? today()->format('Y-m-d')),
            ],
            default => [today()->startOfWeek(), today()->endOfWeek()],
        };
    }

    private function assiduitePeriode(int $userId, Carbon $debut, Carbon $fin): int
    {
        if (!Schema::hasTable('jours_saisie')) return 0;

        $samOuv = Schema::hasTable('jours_ouvrables_speciaux')
            ? \App\Models\JourOuvrableSpecial::dansLaPeriode($debut, $fin) : collect();

        $ouvres = 0; $saisis = 0;
        $c = $debut->copy();
        $endDate = $fin->copy()->min(today());
        while ($c->lte($endDate)) {
            if ((\App\Services\CalendrierTravail::estJourTravaille($c) || $samOuv->contains($c->toDateString())) && !JourFerie::whereDate('date', $c)->exists()) {
                $ouvres++;
                $ok = JourSaisie::where('user_id', $userId)->whereDate('date', $c)
                    ->whereIn('statut', ['saisi', 'justifie'])->exists();

                if (!$ok && Schema::hasTable('confirmation_taches')) {
                    $ok = ConfirmationTache::where('user_id', $userId)->whereDate('date', $c)->exists();
                }

                if (!$ok && Schema::hasTable('absences')) {
                    $ok = \App\Models\Absence::pourDate($userId, $c)->exists();
                }

                if ($ok) $saisis++;
            }
            $c->addDay();
        }
        return $ouvres > 0 ? round(($saisis / $ouvres) * 100) : 100;
    }
}
