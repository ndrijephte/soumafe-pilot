<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, NotificationApp, Projet, ProjetCollaborateur, User, UserStory};
use App\Services\{ArchiveScrum, ImportBacklog, ImportConsolide, TexteRiche};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/** Espace Scrum d'un projet : équipe, Product Backlog (user stories) et sprints. */
class ProjetScrumController extends Controller
{
    public function show(Request $r, Projet $projet)
    {
        $u = $r->user();
        abort_unless($projet->estVisiblePour($u), 403, "Vous n'êtes pas membre de ce projet.");
        $projet->load(['responsable', 'collaborateurs.user', 'sprints' => fn($q) => $q->orderByDesc('numero')->withCount([
            'taches', 'dailyScrums', 'taches as taches_terminees_count' => fn($t) => $t->where('statut', 'termine'),
        ])]);

        $toutes = UserStory::trierParNumero(
            $projet->userStories()->with(['sprints:id,numero,statut'])->withCount(['taches', 'taches as taches_terminees_count' => fn($t) => $t->where('statut', 'termine')])->get()
        )->values();
        // Les stories archivées restent dans l'historique des sprints, hors du Product Backlog
        [$archivees, $stories] = $toutes->partition(fn($us) => $us->estArchivee());
        $stories = $stories->values();
        $membresIds = $projet->collaborateurs->where('actif', true)->pluck('user_id');
        $disponibles = User::where('actif', true)->whereNotIn('id', $membresIds)->orderBy('name')->get();

        return view('projets.espace', [
            'projet' => $projet,
            'stories' => $stories,
            'archivees' => $archivees->sortByDesc('archivee_le')->values(),
            'modules' => $stories->pluck('module')->filter()->unique()->values(),
            'epics' => $stories->filter(fn($s) => $s->epic)->groupBy(fn($s) => $s->module ?: '')->map(fn($g) => $g->pluck('epic')->unique()->values()),
            'arbre' => \App\Services\AvancementScrum::arborescence($stories),
            'bilanProjet' => \App\Services\AvancementScrum::bilan($stories),
            'tableau' => \App\Services\AvancementScrum::tableauBord($stories, $projet->sprints),
            // Sprints où cet utilisateur peut puiser : le prochain à préparer d'abord
            'sprintsCibles' => $projet->sprints->each->setRelation('projet', $projet)->filter(fn($s) => \App\Services\CeremoniesScrum::peutPuiser($u, $s))
                ->sortBy(fn($s) => [$s->statut === 'planifie' ? 0 : 1, $s->numero])->values(),
            'membres' => $projet->collaborateurs->sortBy([['actif', 'desc'], fn($c) => $c->user?->name])->values(),
            'disponibles' => $disponibles,
            'canEdit' => $u->peutGererProjet($projet->id),
            'peutAlimenter' => $projet->peutAlimenterBacklog($u),
            'prochainNumero' => UserStory::prochainNumero($projet),
        ]);
    }

    /* ─── Équipe ─── */

    public function ajouterCollaborateur(Request $r, Projet $projet)
    {
        abort_unless($r->user()->peutGererProjet($projet->id), 403);
        $v = $r->validate([
            'user_id' => 'required|exists:users,id',
            'role_projet' => ['required', Rule::in(array_keys(ProjetCollaborateur::ROLES))],
        ]);
        $c = ProjetCollaborateur::firstOrNew(['projet_id' => $projet->id, 'user_id' => $v['user_id']]);
        $retour = $c->exists && !$c->actif;
        $c->fill(['role_projet' => $v['role_projet'], 'actif' => true, 'date_ajout' => today(), 'date_retrait' => null])->save();

        $nom = $c->user->nomComplet();
        $this->notifier($c->user_id, "Vous avez été ajouté(e) à l'équipe du projet {$projet->libelle} ({$c->roleLibelle()}).", $projet);
        AuditLog::log('ajout_collaborateur', 'projet', $projet->code, "{$nom} - {$c->roleLibelle()}");
        return back()->with('succes', ($retour ? "{$nom} réintègre" : "{$nom} rejoint") . " l'équipe du projet.");
    }

    public function modifierCollaborateur(Request $r, Projet $projet, ProjetCollaborateur $collaborateur)
    {
        abort_unless($r->user()->peutGererProjet($projet->id) && $collaborateur->projet_id === $projet->id, 403);
        $v = $r->validate(['role_projet' => ['required', Rule::in(array_keys(ProjetCollaborateur::ROLES))]]);
        $collaborateur->update($v);
        AuditLog::log('role_collaborateur', 'projet', $projet->code, $collaborateur->user->nomComplet() . ' - ' . $collaborateur->roleLibelle());
        return back()->with('succes', 'Rôle mis à jour.');
    }

    public function retirerCollaborateur(Request $r, Projet $projet, ProjetCollaborateur $collaborateur)
    {
        abort_unless($r->user()->peutGererProjet($projet->id) && $collaborateur->projet_id === $projet->id, 403);
        // Retrait sans suppression : l'historique (dailies, tâches passées) reste rattaché
        $collaborateur->update(['actif' => false, 'date_retrait' => today()]);
        AuditLog::log('retrait_collaborateur', 'projet', $projet->code, $collaborateur->user->nomComplet());
        return back()->with('succes', $collaborateur->user->nomComplet() . " a été retiré(e) de l'équipe.");
    }

    /* ─── Product Backlog ─── */

    private function reglesStory(Projet $projet, ?UserStory $us = null): array
    {
        return [
            'numero' => ['required', 'string', 'max:20', Rule::unique('user_stories')->where('projet_id', $projet->id)->ignore($us?->id)],
            'module' => 'nullable|string|max:150',
            'epic' => 'nullable|string|max:150',
            'profil' => 'nullable|string|max:150',
            'intitule' => 'required|string|max:2000',
            'priorite_moscow' => ['nullable', Rule::in(UserStory::MOSCOW_BACKLOG)],
            'dor' => 'sometimes|boolean',
            'exigences' => 'nullable|string|max:20000',
            'taches_techniques' => 'sometimes|nullable|string|max:20000',
            'effort_estime' => ['sometimes', 'nullable', 'integer', Rule::in(UserStory::POINTS)],
        ];
    }

    public function storeUserStory(Request $r, Projet $projet)
    {
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $v = $this->texteRiche($r->validate($this->reglesStory($projet), ['numero.unique' => 'Ce numéro de user story existe déjà dans le projet.']));
        $us = $projet->userStories()->create($v);
        AuditLog::log('creation_user_story', 'projet', $projet->code, "{$us->numero} - {$us->intitule}");
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with('succes', "User story {$us->numero} ajoutée au backlog.");
    }

    /**
     * Reformulation d'un epic : le nouveau libellé s'applique à toutes les user stories de l'epic
     * dans sa fonctionnalité (un libellé déjà utilisé dans la fonctionnalité regroupe les deux epics).
     */
    public function renommerEpic(Request $r, Projet $projet)
    {
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $v = $r->validate([
            'module' => 'nullable|string|max:150',
            'ancien' => 'required|string|max:150',
            'nouveau' => 'required|string|max:150',
        ], ['nouveau.required' => "Indiquez la nouvelle formulation de l'epic."]);
        $nouveau = trim(preg_replace('/\s+/u', ' ', $v['nouveau']));
        if ($nouveau === $v['ancien']) return back()->with('succes', 'Formulation inchangée.');
        $module = trim((string) ($v['module'] ?? ''));
        $requete = $projet->userStories()->where('epic', $v['ancien'])
            ->where(fn($q) => $module === '' ? $q->whereNull('module')->orWhere('module', '') : $q->where('module', $module));
        $fusion = $projet->userStories()->where('epic', $nouveau)
            ->where(fn($q) => $module === '' ? $q->whereNull('module')->orWhere('module', '') : $q->where('module', $module))->exists();
        // Story par story : la modification est datée et signalée en une fois aux développeurs du sprint en cours
        $concernees = $requete->get();
        $n = $concernees->count();
        if (!$n) return back()->with('erreur', "Epic introuvable : la page n'était peut-être plus à jour.");
        UserStory::$signaler = false;
        try { $concernees->each->update(['epic' => $nouveau]); } finally { UserStory::$signaler = true; }
        UserStory::signalerModifications($concernees);
        AuditLog::log('renommage_epic', 'projet', $projet->code, ($module !== '' ? "{$module} › " : '') . "« {$v['ancien']} » → « {$nouveau} » ({$n} user story(s))");
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])
            ->with('succes', "Epic reformulé : « {$nouveau} » ({$n} user story(s))." . ($fusion ? ' Il rejoint l\'epic du même nom déjà présent dans la fonctionnalité.' : ''));
    }

    public function updateUserStory(Request $r, UserStory $userStory)
    {
        $projet = $userStory->projet;
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $v = $this->texteRiche($r->validate($this->reglesStory($projet, $userStory), ['numero.unique' => 'Ce numéro de user story existe déjà dans le projet.']));
        $userStory->update($v);
        AuditLog::log('modification_user_story', 'projet', $projet->code, "{$userStory->numero} - {$userStory->intitule}");
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with('succes', "User story {$userStory->numero} mise à jour.");
    }

    /** Statut choisi par le gestionnaire ; avec des tâches, seules la mise en revue et l'acceptation restent possibles. */
    public function statutUserStory(Request $r, UserStory $userStory)
    {
        abort_unless($userStory->projet->peutAlimenterBacklog($r->user()), 403);
        $v = $r->validate(['statut' => ['required', Rule::in(array_keys(UserStory::STATUTS))]]);
        if (!in_array($v['statut'], $userStory->statutsPermis(), true)) {
            return back()->with('erreur', "{$userStory->numero} : le statut est calculé à partir de ses tâches.");
        }
        $avant = $userStory->statutLibelle();
        $userStory->update(['statut' => $v['statut']]);
        // Le suivi du sprint ouvert reflète le statut choisi
        $suivi = $userStory->estFinie() ? 'termine' : ($v['statut'] === 'a_faire' ? 'a_faire' : 'en_cours');
        foreach ($userStory->sprints()->where('sprints.statut', '!=', 'termine')->get() as $sprint) $userStory->sprints()->updateExistingPivot($sprint->id, ['statut_sprint' => $suivi]);
        AuditLog::log('statut_user_story', 'projet', $userStory->projet->code, "{$userStory->numero} : {$avant} → {$userStory->statutLibelle()}");
        return back()->with('succes', "{$userStory->numero} : {$userStory->statutLibelle()}.");
    }

    /** Niveau d'exécution déclaré par un développeur (0 à 100 %, par pas de 10) ; « vider » revient au calcul par les tâches. */
    public function avancementUserStory(Request $r, UserStory $userStory)
    {
        abort_unless(\App\Services\AvancementScrum::peutDeclarer($r->user(), $userStory), 403);
        $v = $r->validate([
            'avancement' => ['nullable', 'integer', 'between:0,100', fn($a, $val, $fail) => $val !== null && $val % 10 !== 0 ? $fail("Le niveau d'exécution se déclare par pas de 10 %.") : null],
            'avancement_note' => 'nullable|string|max:500',
        ]);
        $avant = $userStory->avancement;
        $userStory->update([
            'avancement' => $v['avancement'] ?? null,
            'avancement_note' => trim($v['avancement_note'] ?? '') ?: null,
            'avancement_par' => $r->user()->id,
            'avancement_le' => now(),
        ]);
        \App\Services\AvancementScrum::synchroniser($userStory->id);
        AuditLog::log('avancement_user_story', 'projet', $userStory->projet->code, "{$userStory->numero} : " . ($avant === null ? 'calculé' : $avant . ' %') . ' → ' . ($userStory->avancement === null ? 'calculé' : $userStory->avancement . ' %') . ($userStory->avancement_note ? " - {$userStory->avancement_note}" : ''));
        return back()->with('succes', "{$userStory->numero} : niveau d'exécution " . ($userStory->avancement === null ? 'calculé à partir des tâches.' : "déclaré à {$userStory->avancement} %."));
    }

    public function destroyUserStory(Request $r, UserStory $userStory)
    {
        $projet = $userStory->projet;
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5|max:500']);
        [$issue, $message] = $this->retirerStory($userStory, $r->motif_suppression);
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with($issue === 'refus' ? 'erreur' : 'succes', $message);
    }

    /**
     * Retrait d'une story du Product Backlog : refusé si elle est engagée dans un sprint en cours ; archivée si elle est
     * passée par un sprint clôturé (l'historique du sprint la conserve) ; sinon supprimée.
     * @return array{0: 'refus'|'archivee'|'supprimee', 1: string}
     */
    private function retirerStory(UserStory $userStory, string $motif): array
    {
        $projet = $userStory->projet;
        $numero = $userStory->numero;
        // Engagée dans un sprint en cours (Sprint Backlog ou tâches) : on ne la retire pas du sprint en douce
        $enCours = $userStory->sprints()->where('sprints.statut', 'en_cours')->first()
            ?? \App\Models\Sprint::where('statut', 'en_cours')->whereHas('taches', fn($t) => $t->where('user_story_id', $userStory->id))->first();
        if ($enCours) {
            return ['refus', "{$numero} est engagée dans le Sprint {$enCours->numero} en cours : retirez-la d'abord du Sprint Backlog (ses tâches restent au planning), ou attendez la clôture du sprint."];
        }
        // Déjà passée par un sprint clôturé : archivée, pour que l'historique du sprint reste complet
        $clos = $userStory->sprints()->where('sprints.statut', 'termine')->pluck('numero')
            ->merge(\App\Models\Sprint::where('statut', 'termine')->whereHas('taches', fn($t) => $t->where('user_story_id', $userStory->id))->pluck('numero'))
            ->unique()->sort()->values();
        if ($clos->isNotEmpty()) {
            $userStory->update(['archivee_le' => now(), 'archive_motif' => $motif]);
            $planifies = $userStory->sprints()->where('sprints.statut', 'planifie')->pluck('sprints.id');
            if ($planifies->isNotEmpty()) $userStory->sprints()->detach($planifies->all());
            AuditLog::log('archivage_user_story', 'projet', $projet->code, "{$numero} - Motif : {$motif}");
            return ['archivee', "User story {$numero} archivée : elle a été engagée dans le Sprint " . $clos->join(', ', ' et ') . ", dont l'historique la conserve. Elle ne figure plus dans le Product Backlog."];
        }
        AuditLog::log('suppression_user_story', 'projet', $projet->code, "{$numero} - Motif : {$motif}");
        $userStory->delete(); // jamais engagée (ou seulement dans un sprint planifié) : suppression définitive
        return ['supprimee', "User story {$numero} supprimée."];
    }

    /**
     * Action commune sur plusieurs user stories du Product Backlog : DoR, priorité MoSCoW, story points,
     * profil, classement (fonctionnalité / epic), statut, retrait. Chaque story suit les mêmes règles qu'à l'unité ;
     * celles qui ne s'y prêtent pas sont écartées et signalées. Les développeurs du sprint en cours sont prévenus en une fois.
     */
    public function actionGroupeeStories(Request $r, Projet $projet)
    {
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $v = $r->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => ['integer', Rule::exists('user_stories', 'id')->where('projet_id', $projet->id)],
            'action' => ['required', Rule::in(['dor', 'moscow', 'points', 'profil', 'classer', 'statut', 'retirer'])],
            'dor' => 'required_if:action,dor|nullable|boolean',
            'priorite_moscow' => ['nullable', Rule::in(UserStory::MOSCOW_BACKLOG)],
            'effort_estime' => ['nullable', 'integer', Rule::in(UserStory::POINTS)],
            'profil' => 'nullable|string|max:150',
            'module' => 'nullable|string|max:150',
            'epic' => 'nullable|string|max:150',
            'changer_module' => 'nullable|boolean',
            'changer_epic' => 'nullable|boolean',
            'statut' => ['required_if:action,statut', 'nullable', Rule::in(array_keys(UserStory::STATUTS))],
            'motif' => 'required_if:action,retirer|nullable|string|min:5|max:500',
        ], [
            'ids.required' => 'Sélectionnez au moins une user story.',
            'motif.required_if' => 'Le motif du retrait est obligatoire.',
            'motif.min' => 'Le motif doit compter au moins 5 caractères.',
        ]);
        $stories = UserStory::withCount(['taches' => fn($q) => $q->where('statut', '!=', 'reporte')])
            ->where('projet_id', $projet->id)->whereIn('id', $v['ids'])->get();
        $stories = UserStory::trierParNumero($stories)->values();
        $retour = redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog']);
        $faites = [];
        $ecartees = [];
        $libelle = '';

        UserStory::$signaler = false;
        $modifiees = collect();
        try {
            DB::transaction(function () use ($v, $stories, &$faites, &$ecartees, &$libelle, $modifiees) {
                $maj = function (UserStory $us, array $valeurs) use (&$faites, $modifiees) {
                    $us->fill($valeurs);
                    if (!$us->isDirty()) return;
                    $us->save();
                    if ($us->wasChanged('contenu_modifie_le')) $modifiees->push($us);
                    $faites[] = $us->numero;
                };
                switch ($v['action']) {
                    case 'dor':
                        $libelle = 'DoR : ' . ($v['dor'] ? 'Oui' : 'Non');
                        foreach ($stories as $us) $maj($us, ['dor' => (bool) $v['dor']]);
                        break;
                    case 'moscow':
                        $libelle = 'Priorité : ' . (isset($v['priorite_moscow']) ? UserStory::MOSCOW[$v['priorite_moscow']] : 'non définie');
                        foreach ($stories as $us) $maj($us, ['priorite_moscow' => $v['priorite_moscow'] ?? null]);
                        break;
                    case 'points':
                        $libelle = 'Story points : ' . ($v['effort_estime'] ?? 'non estimées');
                        foreach ($stories as $us) {
                            // Points figés d'un sprint clôturé : la story garde son estimation historique dans le sprint ; le backlog peut évoluer
                            if ($us->estFinie()) { $ecartees[$us->numero] = 'achevée : son estimation ne change plus'; continue; }
                            $maj($us, ['effort_estime' => $v['effort_estime'] ?? null]);
                        }
                        break;
                    case 'profil':
                        $libelle = 'Profil : ' . (trim($v['profil'] ?? '') ?: 'aucun');
                        foreach ($stories as $us) $maj($us, ['profil' => trim($v['profil'] ?? '') ?: null]);
                        break;
                    case 'classer':
                        $valeurs = [];
                        if (!empty($v['changer_module'])) $valeurs['module'] = trim($v['module'] ?? '') ?: null;
                        if (!empty($v['changer_epic'])) $valeurs['epic'] = trim($v['epic'] ?? '') ?: null;
                        $libelle = 'Classement : ' . collect($valeurs)->map(fn($x, $k) => ($k === 'module' ? 'fonctionnalité ' : 'epic ') . ($x ?? 'aucun(e)'))->join(', ');
                        if ($valeurs) foreach ($stories as $us) $maj($us, $valeurs);
                        break;
                    case 'statut':
                        $libelle = 'Statut : ' . UserStory::STATUTS[$v['statut']];
                        foreach ($stories as $us) {
                            if (!in_array($v['statut'], $us->statutsPermis(), true)) { $ecartees[$us->numero] = 'statut calculé à partir de ses tâches'; continue; }
                            if ($us->statut === $v['statut']) continue;
                            $us->update(['statut' => $v['statut']]);
                            $suivi = $us->estFinie() ? 'termine' : ($v['statut'] === 'a_faire' ? 'a_faire' : 'en_cours');
                            foreach ($us->sprints()->where('sprints.statut', '!=', 'termine')->get() as $sp) $us->sprints()->updateExistingPivot($sp->id, ['statut_sprint' => $suivi]);
                            $faites[] = $us->numero;
                        }
                        break;
                    case 'retirer':
                        $libelle = 'Retrait du Product Backlog';
                        foreach ($stories as $us) {
                            [$issue, $message] = $this->retirerStory($us, trim($v['motif']));
                            if ($issue === 'refus') $ecartees[$us->numero] = 'engagée dans le sprint en cours';
                            else $faites[] = $us->numero . ($issue === 'archivee' ? ' (archivée)' : '');
                        }
                        break;
                }
            });
        } finally {
            UserStory::$signaler = true;
        }
        if ($modifiees->isNotEmpty()) UserStory::signalerModifications($modifiees);

        if ($faites && $v['action'] !== 'retirer') {
            AuditLog::log('action_groupee_us', 'projet', $projet->code, $libelle . ' — ' . implode(', ', $faites));
        }
        $n = count($faites);
        $msg = $n ? "{$libelle} : {$n} user stor" . ($n > 1 ? 'ies' : 'y') . ' mise' . ($n > 1 ? 's' : '') . ' à jour.' : "{$libelle} : aucune modification.";
        if ($v['action'] === 'retirer' && $n) $msg = "{$n} user stor" . ($n > 1 ? 'ies retirées' : 'y retirée') . ' du Product Backlog (' . implode(', ', $faites) . ').';
        if ($ecartees) $retour->with('ecartees_us', $ecartees);
        return $retour->with($n ? 'succes' : 'erreur', $msg);
    }

    /** Une story archivée revient dans le Product Backlog. */
    public function restaurerUserStory(Request $r, UserStory $userStory)
    {
        $projet = $userStory->projet;
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        if (!$userStory->estArchivee()) return back();
        $userStory->update(['archivee_le' => null, 'archive_motif' => null]);
        AuditLog::log('restauration_user_story', 'projet', $projet->code, $userStory->numero);
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with('succes', "User story {$userStory->numero} restaurée dans le Product Backlog.");
    }

    /* ─── Import / export Excel du Product Backlog ─── */

    public function importerBacklog(Request $r, Projet $projet)
    {
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $r->validate([
            'fichier' => 'required|file|mimes:xlsx|max:10240',
            'mode' => 'required|in:maj,ajout',
        ], ['fichier.mimes' => 'Le fichier doit être au format .xlsx (Excel).']);

        $import = new ImportBacklog($projet);
        UserStory::$signaler = false;
        try {
            $rapport = DB::transaction(fn() => $import->importer($r->file('fichier')->getRealPath(), $r->mode));
        } catch (\Throwable $e) {
            return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->withErrors(['fichier' => 'Import impossible : ' . $e->getMessage()]);
        } finally {
            UserStory::$signaler = true;
        }
        UserStory::signalerModifications(collect($import->modifiees));
        AuditLog::log('import_backlog', 'projet', $projet->code, "Onglet {$rapport['onglet']} : {$rapport['crees']} créée(s), {$rapport['maj']} mise(s) à jour, {$rapport['ignores']} ignorée(s)");
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])
            ->with('succes', "Import de l'onglet « {$rapport['onglet']} » : {$rapport['crees']} user story(s) créée(s), {$rapport['maj']} mise(s) à jour, {$rapport['ignores']} ignorée(s).")
            ->with('remarques_import', $rapport['remarques']);
    }

    public function modeleImport(Request $r, Projet $projet)
    {
        abort_unless($projet->estVisiblePour($r->user()), 403);
        return ArchiveScrum::modeleImport()->telecharger('MODELE_IMPORT_PRODUCT_BACKLOG.xlsx');
    }

    public function exporterBacklog(Request $r, Projet $projet)
    {
        abort_unless($projet->estVisiblePour($r->user()), 403);
        $projet->load('responsable');
        return ArchiveScrum::backlog($projet)->telecharger('PRODUCT_BACKLOG_' . Str::upper(Str::slug($projet->code ?: $projet->libelle, '_')) . '_' . now()->format('Ymd') . '.xlsx');
    }

    /** Import consolidé : fichier multi-onglets couvrant N sprints. */
    public function importerConsolide(Request $r, Projet $projet)
    {
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $r->validate([
            'fichier' => 'required|file|mimes:xlsx|max:51200',
        ], ['fichier.mimes' => 'Le fichier doit être au format .xlsx (Excel).']);

        // Simulation : l'import est réalisé puis entièrement annulé ; seul le compte rendu est affiché
        $simulation = $r->boolean('simulation');
        if ($simulation) DB::beginTransaction();
        try {
            $import = new ImportConsolide($projet);
            $rapport = $import->importer($r->file('fichier')->getRealPath());
        } catch (\Throwable $e) {
            if ($simulation) DB::rollBack();
            return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->withErrors(['fichier' => 'Import consolidé impossible : ' . $e->getMessage()]);
        }
        if ($simulation) {
            DB::rollBack();
            return redirect()->route('projets.espace', [$projet, 'onglet' => 'sprints'])
                ->with('succes', "SIMULATION : rien n'a été enregistré. Résultat attendu : " . ImportConsolide::resume($rapport) . '.')
                ->with('erreurs_import', $rapport['erreurs']);
        }

        $resume = ImportConsolide::resume($rapport);

        AuditLog::log('import_consolide', 'projet', $projet->code, $resume);

        return redirect()->route('projets.espace', [$projet, 'onglet' => 'sprints'])
            ->with('succes', "Import consolidé terminé : {$resume}.")
            ->with('erreurs_import', $rapport['erreurs']);
    }

    /* ─── Outils ─── */

    /** Exigences / tâches techniques : HTML restreint du cadre de rédaction. */
    private function texteRiche(array $v): array
    {
        foreach (['exigences', 'taches_techniques'] as $k) if (array_key_exists($k, $v)) $v[$k] = TexteRiche::nettoyer($v[$k]);
        return $v;
    }

    private function notifier(int $userId, string $message, Projet $projet): void
    {
        if ($userId === auth()->id() || !Schema::hasTable('notifications_app')) return;
        NotificationApp::create(['user_id' => $userId, 'type' => 'projet', 'contenu' => $message, 'lien' => route('projets.espace', $projet), 'lu' => false, 'canal' => 'in_app']);
    }
}
