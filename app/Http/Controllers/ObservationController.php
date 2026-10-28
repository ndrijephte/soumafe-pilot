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

        $query = Observation::with(['responsable', 'declarant', 'projet', 'suiveur'])->orderByDesc('date_reception');

        // Collaborateur non-pilote : uniquement ses observations déléguées
        $u = auth()->user();
        if ($u && !$u->estPilote()) {
            $query->where(fn($q) => $q->where('suivi_par', $u->id)->orWhere('declare_par', $u->id));
        }

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

        $peutDeclarer = Observation::peutDeclarer($u);

        return view('observations.index', compact('observations', 'stats', 'responsables', 'projets', 'peutDeclarer'));
    }

    /** Phase 1 - Déclaration */
    public function store(Request $r)
    {
        abort_unless(Observation::peutDeclarer($r->user()), 403, "Seuls les pilotes et les collaborateurs désignés au suivi peuvent déclarer.");
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
            // Déclaré par un collaborateur désigné : il en assure le suivi
            'suivi_par'       => $r->user()->estPilote() ? null : auth()->id(),
        ]);
        $obs->journaliser('declaration', trim($obs->type_difficulte . ' - priorité ' . $obs->prioriteLibelle() . ' - ' . $obs->origine)
            . ($obs->responsable_id ? ' - responsable : ' . ($obs->responsable?->nomComplet() ?? '') : ''));

        // Notification au responsable désigné
        $this->notifierResponsable($obs, $r->responsable_id);

        $this->auditer('declarer_observation', $obs->code, $obs->description);

        return redirect()->route('observations.index')
            ->with('succes', 'Observation déclarée avec succès.');
    }

    /** Phase 2 - Résolution / mise à jour statut */
    public function update(Request $r, Observation $observation)
    {
        $u = $r->user();
        // Pilotes OU collaborateur délégué au suivi peuvent modifier
        abort_unless(
            $u->estPilote() || (int) $observation->suivi_par === (int) $u->id,
            403,
            'Vous n\'avez pas les droits pour modifier cette observation.'
        );

        $v = $r->validate([
            'statut'                  => 'required|in:nouveau,en_cours,resolu,bloque,rejete',
            'mode_execution'          => 'nullable|string|max:100',
            'date_livraison'          => 'nullable|date',
            'commentaire_resolution'  => 'nullable|string|max:2000',
            'responsable_id'          => 'nullable|exists:users,id',
            'description'             => 'nullable|string|max:2000',
            'type_difficulte'         => 'nullable|string|max:100',
            'priorite'                => 'nullable|string|max:30',
            'origine'                 => 'nullable|string|max:100',
            'commentaires'            => 'nullable|string|max:2000',
        ]);

        $ancienResp = $observation->responsable_id;
        $ancienStatut = $observation->statut;
        $ancienCommentaire = $observation->commentaire_resolution;

        $observation->statut                 = $v['statut'];
        $observation->mode_execution         = $v['mode_execution'] ?? null;
        $observation->date_livraison         = $v['date_livraison'] ?? null;
        $observation->commentaire_resolution = trim($v['commentaire_resolution'] ?? '') ?: null;

        // Champs modifiables par le collaborateur délégué
        if (isset($v['description']) && $v['description'])     $observation->description = $v['description'];
        if (isset($v['type_difficulte']) && $v['type_difficulte']) $observation->type_difficulte = $v['type_difficulte'];
        if (isset($v['priorite']) && $v['priorite'])           $observation->priorite = $v['priorite'];
        if (isset($v['origine']) && $v['origine'])             $observation->origine = $v['origine'];
        if (array_key_exists('commentaires', $v))              $observation->commentaires = trim($v['commentaires'] ?? '') ?: null;

        if ($r->filled('responsable_id') && $u->estPilote()) {
            $observation->responsable_id = $r->responsable_id;
        }

        if (in_array($r->statut, ['resolu', 'rejete']) && !$observation->resolu_le) {
            $observation->resolu_par = auth()->id();
            $observation->resolu_le  = now();
        }

        $observation->save();

        if ($ancienStatut !== $observation->statut) {
            $observation->journaliser(in_array($observation->statut, ['resolu', 'rejete']) ? 'resolution' : 'statut',
                Observation::make(['statut' => $ancienStatut])->statutLibelle() . ' → ' . $observation->statutLibelle()
                . ($observation->mode_execution ? ' - mode : ' . $observation->mode_execution : '')
                . ($observation->date_livraison ? ' - livraison : ' . $observation->date_livraison->format('d/m/Y') : ''));
        }
        if ((int) $ancienResp !== (int) $observation->responsable_id && $observation->responsable_id) {
            $observation->journaliser('responsable', $observation->fresh('responsable')->responsable?->nomComplet());
        }
        if ($observation->commentaire_resolution && $observation->commentaire_resolution !== $ancienCommentaire) {
            $observation->journaliser('commentaire', $observation->commentaire_resolution);
        }

        // Notification si responsable changé
        if ($r->filled('responsable_id') && (int) $r->responsable_id !== (int) $ancienResp) {
            $this->notifierResponsable($observation, $r->responsable_id);
        }

        $this->auditer('traiter_observation', $observation->code . ' → ' . $observation->statutLibelle());

        return redirect()->route('observations.index')
            ->with('succes', 'Observation mise à jour.');
    }

    /** Déléguer le suivi d'une observation à un collaborateur */
    public function deleguerSuivi(Request $r, Observation $observation)
    {
        abort_unless($r->user()->estAdmin(), 403);

        $r->validate(['suivi_par' => 'required|exists:users,id']);

        $observation->suivi_par = $r->suivi_par;
        $observation->save();
        $observation->journaliser('delegation', User::find($r->suivi_par)?->nomComplet());

        // Notifier le collaborateur
        $collab = User::find($r->suivi_par);
        if ($collab) {
            NotificationApp::create([
                'user_id' => $collab->id,
                'type'    => 'observation',
                'contenu' => 'Suivi observation ' . $observation->code . ' - ' . \Illuminate\Support\Str::limit($observation->description, 80),
                'lien'    => route('observations.index'),
                'lu'      => false,
                'canal'   => 'in_app',
            ]);
        }

        $this->auditer('deleguer_suivi_observation', $observation->code . ' → ' . ($collab?->nomComplet() ?? '?'));

        return redirect()->route('observations.index')
            ->with('succes', 'Suivi délégué à ' . ($collab?->nomComplet() ?? '—'));
    }

    /** Résumé des actions d'une observation (JSON pour la fenêtre « Résumé ») */
    public function resume(Request $r, Observation $observation)
    {
        $u = $r->user();
        abort_unless($u->estPilote() || in_array((int) $u->id, [(int) $observation->suivi_par, (int) $observation->declare_par], true), 403);
        $observation->load(['projet', 'responsable', 'suiveur', 'declarant', 'resoluteur']);
        $historiques = Schema::hasTable('observation_historiques') ? $observation->historiques()->with('user')->get() : collect();

        $etapes = $historiques->map(fn($h) => [
            'date' => $h->created_at->format('d/m/Y H:i'), 'action' => $h->actionLibelle(),
            'par' => $h->user?->nomComplet() ?? '—', 'details' => $h->details,
        ]);
        // Observations antérieures à l'historique : reconstitution depuis le journal d'audit
        if ($etapes->isEmpty() && Schema::hasTable('audit_logs')) {
            $libelles = ['declarer_observation' => 'Déclaration', 'traiter_observation' => 'Traitement', 'deleguer_suivi_observation' => 'Suivi délégué'];
            $etapes = \App\Models\AuditLog::where('module', 'observations')->where('cible', 'like', $observation->code . '%')->orderBy('created_at')->get()
                ->map(fn($l) => ['date' => $l->created_at->format('d/m/Y H:i'), 'action' => $libelles[$l->action] ?? $l->action,
                    'par' => $l->user_name ?? '—', 'details' => trim(preg_replace('/^' . preg_quote($observation->code, '/') . '\s*(—|→)?\s*/u', '', rtrim($l->cible, '…'))) ?: $l->details]);
            if ($observation->declarant && !$etapes->contains('action', 'Déclaration')) {
                $etapes->prepend(['date' => $observation->created_at?->format('d/m/Y H:i') ?? $observation->date_reception->format('d/m/Y'),
                    'action' => 'Déclaration', 'par' => $observation->declarant->nomComplet(), 'details' => null]);
            }
        }

        $fin = $observation->resolu_le ?? now();
        return response()->json([
            'code' => $observation->code, 'statut' => $observation->statutLibelle(), 'priorite' => $observation->prioriteLibelle(),
            'description' => $observation->description, 'type' => $observation->type_difficulte, 'origine' => $observation->origine,
            'projet' => $observation->projet?->libelle, 'commentaires' => $observation->commentaires,
            'declarant' => $observation->declarant?->nomComplet(), 'responsable' => $observation->responsable?->nomComplet(),
            'suiveur' => $observation->suiveur?->nomComplet(), 'resolu_par' => $observation->resoluteur?->nomComplet(),
            'date_reception' => $observation->date_reception->format('d/m/Y'),
            'date_livraison' => $observation->date_livraison?->format('d/m/Y'),
            'resolu_le' => $observation->resolu_le?->format('d/m/Y H:i'),
            'duree' => (($j = (int) floor($observation->date_reception->diffInDays($fin))) === 0 ? "Moins d'un jour" : "{$j} jour(s)") . ($observation->resolu_le ? '' : ' (en cours)'),
            'mode_execution' => $observation->mode_execution, 'commentaire_resolution' => $observation->commentaire_resolution,
            'etapes' => $etapes->values(),
        ]);
    }

    /** Suppression */
    public function destroy(Request $r, Observation $observation)
    {
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $code = $observation->code;
        $observation->delete();
        $this->auditer('supprimer_observation', $code, 'Motif : ' . $r->motif_suppression);

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

        // Observations assignées + suivies
        $obsTotal   = Observation::where('responsable_id', $userId)->orWhere('suivi_par', $userId)->count();
        $obsResolus = Observation::where(function($q) use ($userId) {
            $q->where('responsable_id', $userId)->orWhere('suivi_par', $userId);
        })->where('statut', 'resolu')->count();

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
        abort_unless($u->estPilote() || $u->peutGererParc(), 403);

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

            // Observations assignées (responsable)
            $observations = Observation::where('responsable_id', $collab->id)
                ->whereBetween('date_reception', [$debut, $fin])
                ->with('projet')
                ->orderByDesc('date_reception')
                ->get();

            // Observations suivies (suivi_par)
            $observationsSuivies = Observation::where('suivi_par', $collab->id)
                ->whereBetween('date_reception', [$debut, $fin])
                ->with('projet')
                ->orderByDesc('date_reception')
                ->get();

            // Audit global (toutes actions sur tous modules)
            $auditGlobal = collect();
            if (Schema::hasTable('audit_logs')) {
                $auditGlobal = \App\Models\AuditLog::where('user_id', $collab->id)
                    ->whereBetween('created_at', [$debut, $fin . ' 23:59:59'])
                    ->orderByDesc('created_at')
                    ->limit(30)
                    ->get();
            }

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

            // Activités parc automobile (si gestionnaire parc)
            $parcData = null;
            if ($collab->peutGererParc()) {
                $nomComplet = $collab->nomComplet();
                $recettesParc = \App\Models\RecetteVehicule::where('controle_par', $nomComplet)
                    ->whereBetween('date', [$debut, $fin])
                    ->get();
                $depensesParc = \App\Models\Depense::where('cree_par', $nomComplet)
                    ->whereBetween('date_depense', [$debut, $fin])
                    ->get();
                $auditParc = collect();
                if (Schema::hasTable('audit_logs')) {
                    $auditParc = \App\Models\AuditLog::where('user_id', $collab->id)
                        ->where('module', 'parc')
                        ->whereBetween('created_at', [$debut, $fin . ' 23:59:59'])
                        ->orderByDesc('created_at')
                        ->limit(20)
                        ->get();
                }
                $parcData = [
                    'recettes'           => $recettesParc,
                    'recettes_total'     => $recettesParc->sum('recette'),
                    'recettes_count'     => $recettesParc->count(),
                    'depenses'           => $depensesParc,
                    'depenses_total'     => $depensesParc->sum('montant'),
                    'depenses_count'     => $depensesParc->count(),
                    'audit_actions'      => $auditParc,
                    'audit_count'        => $auditParc->count(),
                ];
            }

            // Observations combinées (responsable + suivies, sans doublons)
            $obsTotal = $observations->count() + $observationsSuivies->reject(fn($o) => $observations->contains('id', $o->id))->count();
            $obsResolus = $observations->where('statut', 'resolu')->count()
                + $observationsSuivies->reject(fn($o) => $observations->contains('id', $o->id))->where('statut', 'resolu')->count();

            // Score global dynamique (pondéré)
            $scoreComposantes = [];
            // 1. Tâches (40%)
            $scoreTaches = $taches->count() > 0
                ? round($taches->where('statut', 'termine')->count() / $taches->count() * 100) : 0;
            $scoreComposantes['taches'] = ['poids' => 40, 'score' => $scoreTaches, 'label' => 'Tâches'];
            // 2. OKR (25%)
            $scoreComposantes['okr'] = ['poids' => 25, 'score' => round($scoreOKR), 'label' => 'Objectifs (OKR)'];
            // 3. Observations (15%)
            $scoreObs = $obsTotal > 0 ? round($obsResolus / $obsTotal * 100) : 0;
            $scoreComposantes['obs'] = ['poids' => 15, 'score' => $scoreObs, 'label' => 'Observations'];
            // 4. Assiduité (15%)
            $joursAttendus = $pointages->count();
            $joursPresents = $pointages->whereIn('statut', ['present', 'retard', 'sorti_tot'])->count();
            $scoreAssiduite = $joursAttendus > 0 ? round($joursPresents / $joursAttendus * 100) : 0;
            $scoreComposantes['assiduite'] = ['poids' => 15, 'score' => $scoreAssiduite, 'label' => 'Assiduité'];
            // 5. Réunions (5%)
            $scoreReunions = $reunions->count() > 0
                ? round($reunions->whereIn('statut', ['prevu', 'present'])->count() / $reunions->count() * 100) : 0;
            $scoreComposantes['reunions'] = ['poids' => 5, 'score' => $scoreReunions, 'label' => 'Réunions'];

            $scoreGlobal = 0;
            foreach ($scoreComposantes as $c) {
                $scoreGlobal += $c['score'] * $c['poids'] / 100;
            }
            $scoreGlobal = round($scoreGlobal);

            // Stats résumé
            $data = [
                'taches'             => $taches,
                'objectifs'          => $objectifs,
                'observations'       => $observations,
                'observationsSuivies' => $observationsSuivies,
                'pointages'          => $pointages,
                'reunions'           => $reunions,
                'auditGlobal'        => $auditGlobal,
                'parcData'           => $parcData,
                'scoreComposantes'   => $scoreComposantes,
                'scoreGlobal'        => $scoreGlobal,
                'stats'              => [
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
                    'obs_total'        => $obsTotal,
                    'obs_resolus'      => $obsResolus,
                    'obs_suivies'      => $observationsSuivies->count(),
                    'obs_suivies_resolues' => $observationsSuivies->where('statut', 'resolu')->count(),
                    'jours_presents'   => $joursPresents,
                    'jours_absents'    => $pointages->where('statut', 'absent')->count(),
                    'retards'          => $pointages->where('statut', 'retard')->count(),
                    'reunions_total'   => $reunions->count(),
                    'reunions_present' => $reunions->whereIn('statut', ['prevu', 'present'])->count(),
                    'audit_actions'    => $auditGlobal->count(),
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

    private function auditer(string $action, string $cible, ?string $details = null): void
    {
        if (Schema::hasTable('audit_logs') && class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log($action, 'observations', $cible, $details);
        }
    }
}
