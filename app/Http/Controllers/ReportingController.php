<?php
namespace App\Http\Controllers;

use App\Models\{Evenement, JourFerie, JourSaisie, Projet, Sprint, Tache, User};
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $periode = $r->get('periode', 'semaine');
        [$debut, $fin] = $this->bornesPeriode($periode, $r->get('date_debut'), $r->get('date_fin'));

        // ─── KPI globaux sur la période ───
        $tachesPeriode = Tache::whereBetween('date_fin_reelle', [$debut, $fin])->where('statut', 'termine');
        $terminees = (clone $tachesPeriode)->count();
        $creees = Tache::whereBetween('created_at', [$debut, $fin])->count();
        $enRetardClotures = (clone $tachesPeriode)->where('qualification', 'en_retard')->count();
        $anticipees = (clone $tachesPeriode)->where('qualification', 'anticipee')->count();
        $aTemps = (clone $tachesPeriode)->where('qualification', 'a_temps')->count();

        // Taux global
        $totalActives = Tache::whereIn('statut', ['a_faire', 'en_cours', 'bloque', 'termine'])->count();
        $totalTerminees = Tache::where('statut', 'termine')->count();
        $tauxGlobal = $totalActives > 0 ? round(($totalTerminees / $totalActives) * 100) : 0;

        // Blocages actifs
        $bloquees = Tache::where('statut', 'bloque')->count();
        $enRetardOuvert = Tache::whereNull('date_fin_reelle')->whereNotNull('echeance')
            ->whereDate('echeance', '<', today())->count();

        // ─── Par collaborateur ───
        $collaborateurs = User::where('actif', true)
            ->whereNotIn('role', ['direction', 'invite'])
            ->orderBy('name')->get()
            ->map(function ($u) use ($debut, $fin) {
                $total = Tache::where('user_id', $u->id)->count();
                $term = Tache::where('user_id', $u->id)->where('statut', 'termine')->count();
                $termPeriode = Tache::where('user_id', $u->id)->where('statut', 'termine')
                    ->whereBetween('date_fin_reelle', [$debut, $fin])->count();
                $retard = Tache::where('user_id', $u->id)->whereNull('date_fin_reelle')
                    ->whereNotNull('echeance')->whereDate('echeance', '<', today())->count();
                $bloq = Tache::where('user_id', $u->id)->where('statut', 'bloque')->count();

                $u->stats = [
                    'total' => $total,
                    'terminees' => $term,
                    'terminees_periode' => $termPeriode,
                    'en_retard' => $retard,
                    'bloquees' => $bloq,
                    'taux' => $total > 0 ? round(($term / $total) * 100) : 0,
                ];
                $u->stats['assiduite'] = $this->assiduitePeriode($u->id, $debut, $fin);
                return $u;
            });

        // ─── Par projet ───
        $projets = Projet::where('statut', 'actif')->withCount([
            'taches',
            'taches as terminees_count' => fn ($q) => $q->where('statut', 'termine'),
            'taches as en_retard_count' => fn ($q) => $q->whereNull('date_fin_reelle')
                ->whereNotNull('echeance')->whereDate('echeance', '<', today()),
            'taches as bloquees_count' => fn ($q) => $q->where('statut', 'bloque'),
        ])->having('taches_count', '>', 0)->orderBy('libelle')->get();

        // ─── Sprints en cours ───
        $sprints = Sprint::where('statut', 'en_cours')->with('projet')->get()->map(function ($s) {
            $total = Tache::where('sprint_id', $s->id)->count();
            $term = Tache::where('sprint_id', $s->id)->where('statut', 'termine')->count();
            $s->stats_total = $total;
            $s->stats_terminees = $term;
            $s->progression = $total > 0 ? round(($term / $total) * 100) : 0;
            $s->jours_restants = $s->date_fin ? max(0, now()->diffInDays($s->date_fin, false)) : null;
            return $s;
        });

        // ─── Événements de la période ───
        $evenements = Evenement::with('user')
            ->whereBetween('date', [$debut, $fin])
            ->orderByDesc('date')->get();

        return view('reporting.index', compact(
            'periode', 'debut', 'fin',
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
            'personnalise' => [Carbon::parse($d ?? today()->subDays(30)), Carbon::parse($f ?? today())],
            default => [today()->startOfWeek(), today()->endOfWeek()],
        };
    }

    private function assiduitePeriode(int $userId, Carbon $debut, Carbon $fin): int
    {
        $ouvres = 0; $saisis = 0;
        $c = $debut->copy();
        $endDate = $fin->copy()->min(today());
        while ($c->lte($endDate)) {
            if (!$c->isWeekend() && !JourFerie::whereDate('date', $c)->exists()) {
                $ouvres++;
                if (JourSaisie::where('user_id', $userId)->whereDate('date', $c)
                    ->whereIn('statut', ['saisi', 'justifie'])->exists()) {
                    $saisis++;
                }
            }
            $c->addDay();
        }
        return $ouvres > 0 ? round(($saisis / $ouvres) * 100) : 100;
    }
}
