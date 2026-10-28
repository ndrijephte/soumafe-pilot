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
            $projet->userStories()->with(['sprints:id,numero'])->withCount(['taches', 'taches as taches_terminees_count' => fn($t) => $t->where('statut', 'termine')])->get()
        )->values();
        $membresIds = $projet->collaborateurs->where('actif', true)->pluck('user_id');
        $disponibles = User::where('actif', true)->whereNotIn('id', $membresIds)->orderBy('name')->get();

        return view('projets.espace', [
            'projet' => $projet,
            'stories' => $stories,
            'modules' => $stories->pluck('module')->filter()->unique()->values(),
            'membres' => $projet->collaborateurs->sortBy([['actif', 'desc'], fn($c) => $c->user?->name])->values(),
            'disponibles' => $disponibles,
            'canEdit' => $u->peutGererProjet($projet->id),
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
            'intitule' => 'required|string|max:2000',
            'exigences' => 'nullable|string|max:20000',
            // Tâches techniques et story points se renseignent au Sprint Planning ;
            // priorité et statut ne sont plus saisis dans le Product Backlog (statut piloté par les sprints).
            'taches_techniques' => 'sometimes|nullable|string|max:20000',
            'effort_estime' => ['sometimes', 'nullable', 'integer', Rule::in(UserStory::POINTS)],
        ];
    }

    public function storeUserStory(Request $r, Projet $projet)
    {
        abort_unless($r->user()->peutGererProjet($projet->id), 403);
        $v = $this->texteRiche($r->validate($this->reglesStory($projet), ['numero.unique' => 'Ce numéro de user story existe déjà dans le projet.']));
        $us = $projet->userStories()->create($v);
        AuditLog::log('creation_user_story', 'projet', $projet->code, "{$us->numero} - {$us->intitule}");
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with('succes', "User story {$us->numero} ajoutée au backlog.");
    }

    public function updateUserStory(Request $r, UserStory $userStory)
    {
        $projet = $userStory->projet;
        abort_unless($r->user()->peutGererProjet($projet->id), 403);
        $v = $this->texteRiche($r->validate($this->reglesStory($projet, $userStory), ['numero.unique' => 'Ce numéro de user story existe déjà dans le projet.']));
        $userStory->update($v);
        AuditLog::log('modification_user_story', 'projet', $projet->code, "{$userStory->numero} - {$userStory->intitule}");
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with('succes', "User story {$userStory->numero} mise à jour.");
    }

    public function destroyUserStory(Request $r, UserStory $userStory)
    {
        $projet = $userStory->projet;
        abort_unless($r->user()->peutGererProjet($projet->id), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        AuditLog::log('suppression_user_story', 'projet', $projet->code, "{$userStory->numero} - Motif : {$r->motif_suppression}");
        $numero = $userStory->numero;
        $userStory->delete(); // les tâches restent, détachées de la story
        return redirect()->route('projets.espace', [$projet, 'onglet' => 'backlog'])->with('succes', "User story {$numero} supprimée.");
    }

    /* ─── Import / export Excel du Product Backlog ─── */

    public function importerBacklog(Request $r, Projet $projet)
    {
        abort_unless($r->user()->peutGererProjet($projet->id), 403);
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
