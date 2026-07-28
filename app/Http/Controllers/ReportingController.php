<?php
namespace App\Http\Controllers;

use App\Models\{ConfirmationTache, Evenement, JourFerie, JourSaisie, Objectif, Projet, Sprint, Tache, User};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReportingController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $periode = $r->get('periode', 'semaine');
        [$debut, $fin] = $this->bornesPeriode($periode, $r->get('date_debut'), $r->get('date_fin'));

        // ─── KPI globaux sur la période ───
        $terminees = 0; $creees = 0; $enRetardClotures = 0; $anticipees = 0; $aTemps = 0;
        $tauxGlobal = 0; $bloquees = 0; $enRetardOuvert = 0;

        if (Schema::hasTable('taches')) {
            $tachesPeriode = Tache::whereBetween('date_fin_reelle', [$debut, $fin])->where('statut', 'termine');
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
            $enRetardOuvert = Tache::whereNull('date_fin_reelle')->whereNotNull('echeance')
                ->whereDate('echeance', '<', today())->count();
        }

        // ─── Par collaborateur ───
        $collaborateurs = User::where('actif', true)
            ->whereNotIn('role', ['direction', 'invite'])
            ->with('pole')
            ->orderBy('name')->get()
            ->map(function ($u) use ($debut, $fin) {
                $total = Tache::where('user_id', $u->id)->count();
                $term = Tache::where('user_id', $u->id)->where('statut', 'termine')->count();
                $termPeriode = Tache::where('user_id', $u->id)->where('statut', 'termine')
                    ->whereBetween('date_fin_reelle', [$debut, $fin])->count();
                $retard = Tache::where('user_id', $u->id)->whereNull('date_fin_reelle')
                    ->whereNotNull('echeance')->whereDate('echeance', '<', today())->count();
                $bloq = Tache::where('user_id', $u->id)->where('statut', 'bloque')->count();

                // Score objectifs du mois en cours
                $scoreObj = null;
                if (Schema::hasTable('objectifs')) {
                    $moisCourant = now()->format('Y-m');
                    $objs = Objectif::where('user_id', $u->id)->where('periode', $moisCourant)->where('type', 'individuel')->get();
                    $sommePond = $objs->sum('ponderation');
                    $scoreObj = $sommePond > 0 ? round($objs->sum(fn($o) => $o->avancement * $o->ponderation / 100)) : null;
                }

                $assiduite = $this->assiduitePeriode($u->id, $debut, $fin);

                $u->setAttribute('stats', [
                    'total' => $total,
                    'terminees' => $term,
                    'terminees_periode' => $termPeriode,
                    'en_retard' => $retard,
                    'bloquees' => $bloq,
                    'taux' => $total > 0 ? round(($term / $total) * 100) : 0,
                    'score_objectifs' => $scoreObj,
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
                'taches as en_retard_count' => fn ($q) => $q->whereNull('date_fin_reelle')
                    ->whereNotNull('echeance')->whereDate('echeance', '<', today()),
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

        // ─── Événements de la période ───
        $evenements = collect();
        if (Schema::hasTable('evenements')) {
            $evenements = Evenement::with('user')
                ->whereBetween('date', [$debut, $fin])
                ->orderByDesc('date')->get();
        }

        return view('reporting.index', compact(
            'periode', 'debut', 'fin',
            'terminees', 'creees', 'enRetardClotures', 'anticipees', 'aTemps',
            'tauxGlobal', 'bloquees', 'enRetardOuvert',
            'collaborateurs', 'projets', 'sprints', 'evenements'
        ));
    }

    /** Vue imprimable (PDF via navigateur) avec colonnes filtrables. */
    public function imprimer(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $periode = $r->get('periode', 'semaine');
        [$debut, $fin] = $this->bornesPeriode($periode, $r->get('date_debut'), $r->get('date_fin'));

        // Sections sélectionnées (par défaut toutes)
        $sections = $r->get('sections', ['kpi', 'collaborateurs', 'projets', 'sprints', 'evenements']);
        if (is_string($sections)) $sections = explode(',', $sections);

        // Colonnes collaborateurs sélectionnées
        $colsCollab = $r->get('cols_collab', ['nom', 'pole', 'total', 'periode', 'retard', 'bloquees', 'taux', 'objectifs', 'assiduite']);
        if (is_string($colsCollab)) $colsCollab = explode(',', $colsCollab);

        // Mêmes données que index()
        $terminees = 0; $creees = 0; $enRetardClotures = 0; $anticipees = 0; $aTemps = 0;
        $tauxGlobal = 0; $bloquees = 0; $enRetardOuvert = 0;

        if (Schema::hasTable('taches')) {
            $tachesPeriode = Tache::whereBetween('date_fin_reelle', [$debut, $fin])->where('statut', 'termine');
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
            $enRetardOuvert = Tache::whereNull('date_fin_reelle')->whereNotNull('echeance')
                ->whereDate('echeance', '<', today())->count();
        }

        $collaborateurs = User::where('actif', true)
            ->whereNotIn('role', ['direction', 'invite'])
            ->with('pole')->orderBy('name')->get()
            ->map(function ($u) use ($debut, $fin) {
                $total = Tache::where('user_id', $u->id)->count();
                $term = Tache::where('user_id', $u->id)->where('statut', 'termine')->count();
                $termPeriode = Tache::where('user_id', $u->id)->where('statut', 'termine')
                    ->whereBetween('date_fin_reelle', [$debut, $fin])->count();
                $retard = Tache::where('user_id', $u->id)->whereNull('date_fin_reelle')
                    ->whereNotNull('echeance')->whereDate('echeance', '<', today())->count();
                $bloq = Tache::where('user_id', $u->id)->where('statut', 'bloque')->count();
                $scoreObj = null;
                if (Schema::hasTable('objectifs')) {
                    $moisCourant = now()->format('Y-m');
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
                'taches as en_retard_count' => fn ($q) => $q->whereNull('date_fin_reelle')
                    ->whereNotNull('echeance')->whereDate('echeance', '<', today()),
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

        $evenements = collect();
        if (Schema::hasTable('evenements')) {
            $evenements = Evenement::with('user')->whereBetween('date', [$debut, $fin])->orderByDesc('date')->get();
        }

        return view('reporting.imprimer', compact(
            'periode', 'debut', 'fin', 'sections', 'colsCollab',
            'terminees', 'creees', 'enRetardClotures', 'anticipees', 'aTemps',
            'tauxGlobal', 'bloquees', 'enRetardOuvert',
            'collaborateurs', 'projets', 'sprints', 'evenements'
        ));
    }

    private function bornesPeriode(string $p, ?string $d, ?string $f): array
    {
        return match ($p) {
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

        $ouvres = 0; $saisis = 0;
        $c = $debut->copy();
        $endDate = $fin->copy()->min(today());
        while ($c->lte($endDate)) {
            if (!$c->isWeekend() && !JourFerie::whereDate('date', $c)->exists()) {
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
