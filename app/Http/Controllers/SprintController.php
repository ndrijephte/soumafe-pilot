<?php
namespace App\Http\Controllers;

use App\Models\{NotificationApp, Projet, Sprint, Tache, User};
use App\Services\{ClotureService, LecteurXlsx};
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $r)
    {
        $projets = Projet::with(['sprints' => fn ($q) => $q->orderByDesc('numero'), 'sprints.taches', 'responsable'])
            ->orderBy('libelle')->get();
        return view('sprints.index', compact('projets'));
    }

    /** Page détail d'un sprint : tâches, import, ajout. */
    public function show(Request $r, Sprint $sprint)
    {
        $sprint->load(['projet.responsable', 'taches.user']);
        $users = User::where('actif', true)->orderBy('name')->get();
        $canEdit = $r->user()->peutGererProjet($sprint->projet_id);
        return view('sprints.show', compact('sprint', 'users', 'canEdit'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->peutGererProjet($r->input('projet_id')), 403);
        $r->validate([
            'projet_id' => 'required|exists:projets,id',
            'numero' => 'required|integer|min:1',
            'objectif' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'engagement_points' => 'nullable|integer|min:1',
        ]);
        $exists = Sprint::where('projet_id', $r->projet_id)->where('numero', $r->numero)->exists();
        if ($exists) return back()->with('erreur', "Le sprint n°{$r->numero} existe déjà pour ce projet.");
        Sprint::create(array_merge($r->only(['projet_id', 'numero', 'objectif', 'date_debut', 'date_fin', 'engagement_points']), ['statut' => 'planifie']));
        return back()->with('succes', 'Sprint créé.');
    }

    public function update(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        $r->validate([
            'objectif' => 'required|string|max:255',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'engagement_points' => 'nullable|integer|min:1',
            'statut' => 'required|in:planifie,en_cours,termine',
        ]);
        $sprint->update($r->only(['objectif', 'date_debut', 'date_fin', 'engagement_points', 'statut']));
        return back()->with('succes', 'Sprint mis à jour.');
    }

    /** Clôturer un sprint : qualifier toutes les tâches et passer en terminé. */
    public function cloturer(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        if ($sprint->statut === 'termine') return back()->with('erreur', 'Ce sprint est déjà clôturé.');

        $cloture = new ClotureService();
        $taches = $sprint->taches()->whereIn('statut', ['a_faire', 'en_cours', 'bloque', 'termine'])->get();

        $stats = ['a_temps' => 0, 'en_retard' => 0, 'anticipee' => 0, 'non_termine' => 0, 'total' => $taches->count()];

        foreach ($taches as $t) {
            if ($t->statut === 'termine') {
                // Qualifier si pas encore fait
                if (!$t->qualification) {
                    $cloture->cloturer($t, $t->date_fin_reelle);
                }
                $stats[$t->qualification] = ($stats[$t->qualification] ?? 0) + 1;
            } else {
                // Tâche non terminée à la clôture → marquée en retard
                $t->update([
                    'qualification' => 'non_termine_sprint',
                    'justification_requise' => true,
                ]);
                $stats['non_termine']++;
            }
        }

        $sprint->update(['statut' => 'termine']);

        // Résumé
        $pointsLivres = $sprint->pointsLivres();
        $engagement = $sprint->engagement_points ?? 0;
        $velocite = $engagement > 0 ? round(($pointsLivres / $engagement) * 100) : 0;

        // Notifier le responsable projet
        if ($sprint->projet?->responsable_projet_id) {
            NotificationApp::create([
                'user_id' => $sprint->projet->responsable_projet_id,
                'type' => 'sprint_cloture',
                'contenu' => "Sprint {$sprint->numero} clôturé — {$stats['a_temps']} à temps, {$stats['en_retard']} en retard, {$stats['non_termine']} non terminée(s). Vélocité : {$velocite}%.",
            ]);
        }

        return back()->with('succes', "Sprint clôturé. {$pointsLivres}/{$engagement} pts livrés. {$stats['a_temps']} à temps, {$stats['en_retard']} en retard, {$stats['non_termine']} non terminée(s).");
    }

    /** API JSON : sprints d'un projet (pour le select dynamique T1). */
    public function parProjet(Projet $projet)
    {
        return response()->json(
            $projet->sprints()->whereIn('statut', ['planifie', 'en_cours'])->orderBy('numero')->get(['id', 'numero', 'objectif', 'statut'])
        );
    }

    /** Ajouter une tâche directement au sprint. */
    public function ajouterTache(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        $r->validate([
            'intitule' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'priorite' => 'required|in:haute,moyenne,basse',
            'date_debut' => 'nullable|date',
            'echeance' => 'nullable|date',
            'points' => 'nullable|integer|min:1',
        ]);

        Tache::create([
            'sprint_id' => $sprint->id,
            'projet_id' => $sprint->projet_id,
            'user_id' => $r->user_id,
            'intitule' => $r->intitule,
            'type' => 'T1',
            'statut' => 'a_faire',
            'priorite' => $r->priorite,
            'date_debut' => $r->date_debut ?? today(),
            'echeance' => $r->echeance,
            'points' => $r->points,
        ]);

        return back()->with('succes', 'Tâche ajoutée au sprint.');
    }

    /** Import xlsx de tâches rattachées à un projet/sprint. */
    public function importerTaches(Request $r)
    {
        abort_unless($r->user()->peutGererProjet(), 403);
        $r->validate([
            'fichier' => 'required|file|mimes:xlsx',
            'sprint_id' => 'nullable|exists:sprints,id',
        ]);

        $lecteur = new LecteurXlsx($r->file('fichier')->getRealPath());

        // Détecter la ligne d'en-tête
        $brut = $lecteur->lire(1);
        $ligneEntete = 1;
        foreach ($brut as $i => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && preg_match('/intitul|tâche|tache/i', $cell)) {
                    $ligneEntete = $i + 1;
                    break 2;
                }
            }
        }

        $data = $lecteur->lire(1, $ligneEntete);
        $importees = 0;
        $ignores = 0;

        foreach ($data as $row) {
            $intitule = trim($this->chercher($row, ['intitulé', 'intitule', 'tâche', 'tache', 'libellé', 'libelle']) ?? '');
            $projetStr = trim($this->chercher($row, ['projet']) ?? '');
            $assigneStr = trim($this->chercher($row, ['assigné', 'assigne', 'assigné_à', 'assigne_a', 'collaborateur', 'responsable']) ?? '');
            $dateDebut = trim($this->chercher($row, ['date_début', 'date_debut', 'début', 'debut', 'date']) ?? '');
            $echeance = trim($this->chercher($row, ['échéance', 'echeance', 'date_fin', 'fin']) ?? '');
            $priorite = trim($this->chercher($row, ['priorité', 'priorite']) ?? '');
            $points = trim($this->chercher($row, ['points', 'story_points']) ?? '');

            if (empty($intitule)) { $ignores++; continue; }

            // Trouver le projet
            $projet = null;
            if ($projetStr) {
                $projet = Projet::whereRaw('LOWER(libelle) = ?', [mb_strtolower($projetStr)])->first();
                if (!$projet) {
                    $projet = Projet::create([
                        'code' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($projetStr, '_')),
                        'libelle' => $projetStr,
                        'statut' => 'actif',
                    ]);
                }
            }

            // Trouver le collaborateur assigné
            $user = null;
            if ($assigneStr) {
                $parts = preg_split('/\s+/', $assigneStr, 2);
                $user = User::whereRaw('LOWER(name) = ?', [mb_strtolower($parts[0])])->first();
            }

            // Mapper priorité
            $prioMapped = match (mb_strtolower($priorite)) {
                'haute', 'élevée', 'high' => 'haute',
                'basse', 'faible', 'low' => 'basse',
                default => 'moyenne',
            };

            Tache::create([
                'user_id' => $user?->id ?? $r->user()->id,
                'projet_id' => $projet?->id,
                'sprint_id' => $r->sprint_id,
                'intitule' => $intitule,
                'type' => $r->sprint_id ? 'T1' : 'T2',
                'statut' => 'a_faire',
                'priorite' => $prioMapped,
                'date_debut' => $dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('Y-m-d') : today()->format('Y-m-d'),
                'echeance' => $echeance ? \Carbon\Carbon::parse($echeance)->format('Y-m-d') : null,
                'points' => is_numeric($points) ? (int) $points : null,
            ]);
            $importees++;
        }

        return back()->with('succes', "Import terminé : {$importees} tâche(s) importée(s), {$ignores} ignorée(s).");
    }

    /** Cherche une valeur dans un tableau associatif par clés possibles. */
    private function chercher(array $row, array $cles): ?string
    {
        foreach ($cles as $cle) {
            if (isset($row[$cle]) && $row[$cle] !== null) return (string) $row[$cle];
        }
        return null;
    }
}
