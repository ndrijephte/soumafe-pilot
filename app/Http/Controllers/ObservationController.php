<?php

namespace App\Http\Controllers;

use App\Models\{Observation, NotificationApp, Projet, User, Tache, Objectif};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ObservationController extends Controller
{
    public function index(Request $r)
    {
        if (!Schema::hasTable('observations')) {
            return view('observations.index', [
                'observations' => collect(),
                'stats'        => ['total' => 0, 'nouveau' => 0, 'en_cours' => 0, 'resolu' => 0, 'bloque' => 0],
                'responsables' => collect(),
                'projets'      => collect(),
            ]);
        }

        $query = Observation::with(['responsable', 'declarant', 'projet'])->orderByDesc('date_reception');

        if ($r->filled('statut'))       $query->where('statut', $r->statut);
        if ($r->filled('priorite'))     $query->where('priorite', $r->priorite);
        if ($r->filled('type'))         $query->where('type_difficulte', $r->type);
        if ($r->filled('responsable'))  $query->where('responsable_id', $r->responsable);
        if ($r->filled('projet'))       $query->where('projet_id', $r->projet);

        $observations = $query->get();

        $all = Observation::all();
        $stats = [
            'total'    => $all->count(),
            'nouveau'  => $all->where('statut', 'nouveau')->count(),
            'en_cours' => $all->where('statut', 'en_cours')->count(),
            'resolu'   => $all->where('statut', 'resolu')->count(),
            'bloque'   => $all->where('statut', 'bloque')->count(),
        ];

        $responsables = User::where('actif', true)
            ->whereNotIn('role', ['invite'])
            ->orderBy('name')
            ->get();

        $projets = Projet::orderBy('libelle')->get();

        return view('observations.index', compact('observations', 'stats', 'responsables', 'projets'));
    }

    /** Phase 1 — Déclaration */
    public function store(Request $r)
    {
        $v = $r->validate([
            'projet_id'        => 'nullable|exists:projets,id',
            'description'      => 'required|string|max:2000',
            'type_difficulte'  => 'required|string|max:100',
            'priorite'         => 'required|string|max:30',
            'responsable_id'   => 'nullable|exists:users,id',
            'origine'          => 'required|string|max:100',
            'commentaires'     => 'nullable|string|max:2000',
        ]);

        $obs = Observation::create([
            'code'            => Observation::prochainCode(),
            'projet_id'       => $v['projet_id'] ?? null ?: null,
            'date_reception'  => now()->toDateString(),
            'description'     => $v['description'],
            'type_difficulte' => $v['type_difficulte'],
            'priorite'        => $v['priorite'],
            'responsable_id'  => $v['responsable_id'] ?? null ?: null,
            'origine'         => $v['origine'],
            'commentaires'    => trim($v['commentaires'] ?? '') ?: null,
            'statut'          => 'nouveau',
            'declare_par'     => auth()->id(),
        ]);

        // Notification au responsable désigné
        $this->notifierResponsable($obs, $r->responsable_id);

        $this->auditer('declarer_observation', $obs->code . ' — ' . $r->description);

        return redirect()->route('observations.index')
            ->with('succes', 'Observation déclarée avec succès.');
    }

    /** Phase 2 — Résolution / mise à jour statut */
    public function update(Request $r, Observation $observation)
    {
        $v = $r->validate([
            'statut'                  => 'required|in:nouveau,en_cours,resolu,bloque,rejete',
            'mode_execution'          => 'nullable|string|max:100',
            'date_livraison'          => 'nullable|date',
            'commentaire_resolution'  => 'nullable|string|max:2000',
            'responsable_id'          => 'nullable|exists:users,id',
        ]);

        $ancienResp = $observation->responsable_id;

        $observation->statut                 = $v['statut'];
        $observation->mode_execution         = $v['mode_execution'] ?? null;
        $observation->date_livraison         = $v['date_livraison'] ?? null;
        $observation->commentaire_resolution = trim($v['commentaire_resolution'] ?? '') ?: null;

        if ($r->filled('responsable_id')) {
            $observation->responsable_id = $r->responsable_id;
        }

        if (in_array($r->statut, ['resolu', 'rejete']) && !$observation->resolu_le) {
            $observation->resolu_par = auth()->id();
            $observation->resolu_le  = now();
        }

        $observation->save();

        // Notification si responsable changé
        if ($r->filled('responsable_id') && (int) $r->responsable_id !== (int) $ancienResp) {
            $this->notifierResponsable($observation, $r->responsable_id);
        }

        $this->auditer('traiter_observation', $observation->code . ' → ' . $observation->statutLibelle());

        return redirect()->route('observations.index')
            ->with('succes', 'Observation mise à jour.');
    }

    /** Suppression */
    public function destroy(Request $r, Observation $observation)
    {
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $code = $observation->code;
        $observation->delete();
        $this->auditer('supprimer_observation', $code . ' — Motif: ' . $r->motif_suppression);

        return redirect()->route('observations.index')
            ->with('succes', 'Observation supprimée.');
    }

    /** Stats collaborateur pour dialog résolution (AJAX) */
    public function statsCollaborateur(Request $r)
    {
        $userId = $r->input('user_id');
        if (!$userId) return response()->json([]);

        $user = User::find($userId);
        if (!$user) return response()->json([]);

        $periode = now()->format('Y-m');

        // Tâches
        $taches = Tache::where('user_id', $userId)->withTrashed();
        $tachesTotal     = (clone $taches)->count();
        $tachesTerminees = (clone $taches)->where('statut', 'termine')->count();
        $tachesEnRetard  = (clone $taches)->whereNull('date_fin_reelle')
            ->whereDate('echeance', '<', today())->count();

        // Objectifs du mois
        $objectifs = Objectif::where('user_id', $userId)->where('periode', $periode);
        $objTotal     = (clone $objectifs)->count();
        $objAtteints  = (clone $objectifs)->where('avancement', '>=', 100)->count();
        $objScore     = (clone $objectifs)->avg('avancement') ?? 0;

        // Observations assignées
        $obsTotal   = Observation::where('responsable_id', $userId)->count();
        $obsResolus = Observation::where('responsable_id', $userId)->where('statut', 'resolu')->count();

        return response()->json([
            'nom'              => $user->nomComplet(),
            'poste'            => $user->roleLibelle(),
            'taches_total'     => $tachesTotal,
            'taches_terminees' => $tachesTerminees,
            'taches_retard'    => $tachesEnRetard,
            'obj_total'        => $objTotal,
            'obj_atteints'     => $objAtteints,
            'obj_score'        => round($objScore),
            'obs_total'        => $obsTotal,
            'obs_resolus'      => $obsResolus,
        ]);
    }

    /** Fiche historique collaborateur */
    public function ficheCollaborateur(Request $r)
    {
        $u = $r->user();
        abort_unless($u->estPilote(), 403);

        $collaborateurs = User::where('actif', true)
            ->whereNotIn('role', ['invite', 'administrateur', 'direction'])
            ->orderBy('name')->get();

        $collab  = null;
        $data    = null;
        $debut   = $r->input('debut', now()->startOfMonth()->toDateString());
        $fin     = $r->input('fin', now()->toDateString());
        $periode = now()->format('Y-m');

        if ($r->filled('collaborateur_id')) {
            $collab = User::findOrFail($r->collaborateur_id);

            // Tâches sur la période
            $taches = Tache::where('user_id', $collab->id)
                ->where(function ($q) use ($debut, $fin) {
                    $q->whereBetween('date_debut', [$debut, $fin])
                      ->orWhereBetween('echeance', [$debut, $fin]);
                })
                ->with('projet')
                ->withTrashed()
                ->orderByDesc('date_debut')
                ->get();

            // Objectifs (avec KR pour score pondéré + verdict)
            $objectifs = Objectif::where('user_id', $collab->id)
                ->where(function ($q) use ($debut, $fin) {
                    $q->whereBetween('date_debut', [$debut, $fin])
                      ->orWhereBetween('date_echeance', [$debut, $fin])
                      ->orWhere('periode', now()->format('Y-m'));
                })
                ->with(['projet', 'resultats'])
                ->orderByDesc('created_at')
                ->get();

            // Observations assignées
            $observations = Observation::where('responsable_id', $collab->id)
                ->whereBetween('date_reception', [$debut, $fin])
                ->with('projet')
                ->orderByDesc('date_reception')
                ->get();

            // Pointages
            $pointages = collect();
            if (Schema::hasTable('pointages')) {
                $pointages = \App\Models\Pointage::where('user_id', $collab->id)
                    ->whereBetween('date', [$debut, $fin])
                    ->orderByDesc('date')
                    ->get();
            }

            // Planning réunions (affectations sur la période)
            $reunions = collect();
            if (Schema::hasTable('affectations_reunion')) {
                $reunions = \App\Models\AffectationReunion::with(['role', 'typeReunion'])
                    ->where('user_id', $collab->id)
                    ->whereBetween('date_reunion', [$debut, $fin])
                    ->orderByDesc('date_reunion')
                    ->get();
            }

            // Productivité (tâches terminées / jours ouvrés)
            $termPeriode = $taches->where('statut', 'termine')
                ->filter(fn($t) => $t->date_fin_reelle && $t->date_fin_reelle->between($debut, $fin))
                ->count();
            $joursSpeciaux = Schema::hasTable('jours_ouvrables_speciaux')
                ? \App\Models\JourOuvrableSpecial::dansLaPeriode(\Carbon\Carbon::parse($debut), \Carbon\Carbon::parse($fin))
                : collect();
            $joursOuvres = 0;
            $cp = \Carbon\Carbon::parse($debut)->copy();
            $ep = \Carbon\Carbon::parse($fin)->copy()->min(today());
            while ($cp->lte($ep)) {
                if (!$cp->isWeekend() || $joursSpeciaux->contains($cp->toDateString())) $joursOuvres++;
                $cp->addDay();
            }
            $productivite = $joursOuvres > 0 ? round($termPeriode / $joursOuvres, 1) : 0;

            // Score OKR pondéré + KR indicateurs
            $scoreOKR = $objectifs->isNotEmpty() ? Objectif::scoreGroupe($objectifs) : 0;
            $krTotal = $objectifs->sum(fn($o) => $o->resultats->count());
            $krValides = $objectifs->sum(fn($o) => $o->resultats->whereNotNull('valide')->count());
            $krAtteints = $objectifs->sum(fn($o) => $o->resultats->where('valide', true)->count());

            // Taux de complétion
            $tauxCompletion = $taches->count() > 0
                ? round($taches->where('statut', 'termine')->count() / $taches->count() * 100)
                : 0;

            // Stats résumé
            $data = [
                'taches'       => $taches,
                'objectifs'    => $objectifs,
                'observations' => $observations,
                'pointages'    => $pointages,
                'reunions'     => $reunions,
                'stats'        => [
                    'taches_total'     => $taches->count(),
                    'taches_terminees' => $taches->where('statut', 'termine')->count(),
                    'taches_retard'    => $taches->filter(fn($t) => !$t->date_fin_reelle && $t->echeance && $t->echeance->isPast())->count(),
                    'taux_completion'  => $tauxCompletion,
                    'productivite'     => $productivite,
                    'obj_total'        => $objectifs->count(),
                    'obj_atteints'     => $objectifs->where('evaluation', 'atteint')->count(),
                    'obj_score'        => $scoreOKR,
                    'kr_total'         => $krTotal,
                    'kr_valides'       => $krValides,
                    'kr_atteints'      => $krAtteints,
                    'obs_total'        => $observations->count(),
                    'obs_resolus'      => $observations->where('statut', 'resolu')->count(),
                    'jours_presents'   => $pointages->whereIn('statut', ['present', 'retard', 'sorti_tot'])->count(),
                    'jours_absents'    => $pointages->where('statut', 'absent')->count(),
                    'retards'          => $pointages->where('statut', 'retard')->count(),
                    'reunions_total'   => $reunions->count(),
                    'reunions_present' => $reunions->whereIn('statut', ['prevu', 'present'])->count(),
                ],
            ];
        }

        return view('observations.fiche-collaborateur', compact('collaborateurs', 'collab', 'data', 'debut', 'fin'));
    }

    private function notifierResponsable(Observation $obs, $responsableId): void
    {
        if (!$responsableId || (int) $responsableId === auth()->id()) return;
        if (!Schema::hasTable('notifications_app')) return;

        $projetLib = $obs->projet ? ' (' . $obs->projet->libelle . ')' : '';
        NotificationApp::create([
            'user_id' => $responsableId,
            'type'    => 'observation',
            'contenu' => 'Observation ' . $obs->code . $projetLib . ' vous a été assignée : ' . mb_substr($obs->description, 0, 80),
            'lien'    => route('observations.index'),
            'lu'      => false,
            'canal'   => 'in_app',
        ]);
    }

    private function auditer(string $action, string $cible): void
    {
        if (Schema::hasTable('audit_logs') && class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log($action, 'observations', $cible);
        }
    }
}
