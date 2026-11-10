<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, NotificationApp, Projet, ProjetCollaborateur, User, UserStory};
use App\Services\{ArchiveScrum, ImportBacklog, TexteRiche};
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

        $stories = UserStory::trierParNumero(
            $projet->userStories()->with(['sprints:id,numero,statut'])->withCount(['taches', 'taches as taches_terminees_count' => fn($t) => $t->where('statut', 'termine')])->get()
        )->values();
        $membresIds = $projet->collaborateurs->where('actif', true)->pluck('user_id');
        $disponibles = User::where('actif', true)->whereNotIn('id', $membresIds)->orderBy('name')->get();

        return view('projets.espace', [
            'projet' => $projet,
            'stories' => $stories,
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
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression_user_story', 'projet', $projet->code, "{$userStory->numero} - Motif : {$r->motif_suppression}");
        $numero = $userStory->numero;
        $userStory->delete(); // les tâches restent, détachées de la story
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with('succes', "User story {$numero} supprimée.");
    }

    /* ─── Import / export Excel du Product Backlog ─── */

    public function importerBacklog(Request $r, Projet $projet)
    {
        abort_unless($projet->peutAlimenterBacklog($r->user()), 403);
        $r->validate([
            'fichier' => 'required|file|mimes:xlsx|max:10240',
            'mode' => 'required|in:maj,ajout',
        ], ['fichier.mimes' => 'Le fichier doit être au format .xlsx (Excel).']);

        try {
            $rapport = DB::transaction(fn() => (new ImportBacklog($projet))->importer($r->file('fichier')->getRealPath(), $r->mode));
        } catch (\Throwable $e) {
            return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->withErrors(['fichier' => 'Import impossible : ' . $e->getMessage()]);
        }
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
