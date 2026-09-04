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
        $canEditSprint = $r->user()->estPilote(); // seuls admin, charge_ops et direction modifient sprint/tâches
        return view('sprints.show', compact('sprint', 'users', 'canEdit', 'canEditSprint'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->peutGererProjet($r->input('projet_id')), 403);
        $r->validate([
            'projet_id' => 'required|exists:projets,id',
            'numero' => 'required|integer|min:1',
            'objectif' => 'required|string|max:1000',
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
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'objectif' => 'required|string|max:1000',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'engagement_points' => 'nullable|integer|min:1',
        ]);
        $sprint->update($r->only(['objectif', 'date_debut', 'date_fin', 'engagement_points']));
        return back()->with('succes', 'Sprint mis à jour.');
    }

    /** Démarrer un sprint planifié. */
    public function demarrer(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        if ($sprint->statut !== 'planifie') return back()->with('erreur', 'Ce sprint n\'est pas en statut planifié.');

        // Vérifier qu'il y a au moins une tâche
        if ($sprint->taches()->count() === 0) {
            return back()->with('erreur', 'Impossible de démarrer un sprint sans tâche. Ajoutez au moins une tâche.');
        }

        // Vérifier qu'aucun autre sprint du même projet n'est en cours
        $autreEnCours = Sprint::where('projet_id', $sprint->projet_id)
            ->where('id', '!=', $sprint->id)
            ->where('statut', 'en_cours')->exists();
        if ($autreEnCours) {
            return back()->with('erreur', 'Un autre sprint est déjà en cours sur ce projet. Clôturez-le d\'abord.');
        }

        $sprint->update(['statut' => 'en_cours']);

        // Passer le projet en "actif" s'il ne l'est pas déjà
        $projet = $sprint->projet;
        if ($projet && $projet->statut !== 'actif') {
            $projet->update(['statut' => 'actif']);
        }

        // Notifier tous les collaborateurs assignés
        $userIds = $sprint->taches()->whereNotNull('user_id')->pluck('user_id')->unique();
        foreach ($userIds as $uid) {
            NotificationApp::create([
                'user_id' => $uid,
                'type' => 'sprint_demarre',
                'contenu' => "Le sprint {$sprint->numero} du projet {$sprint->projet?->libelle} a démarré. Vos tâches sont maintenant actives.",
            ]);
        }

        // Notifier le responsable projet
        if ($sprint->projet?->responsable_projet_id && !$userIds->contains($sprint->projet->responsable_projet_id)) {
            NotificationApp::create([
                'user_id' => $sprint->projet->responsable_projet_id,
                'type' => 'sprint_demarre',
                'contenu' => "Le sprint {$sprint->numero} a démarré — {$sprint->taches()->count()} tâche(s).",
            ]);
        }

        return back()->with('succes', "Sprint {$sprint->numero} démarré. {$userIds->count()} collaborateur(s) notifié(s).");
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

    /** Arrêter un sprint en cours (avec justification obligatoire). */
    public function arreter(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->peutGererProjet($sprint->projet_id), 403);
        if ($sprint->statut !== 'en_cours') return back()->with('erreur', 'Ce sprint n\'est pas en cours.');

        $r->validate(['justification' => 'required|string|max:1000']);

        $sprint->update([
            'statut' => 'termine',
            'justification_arret' => $r->justification,
            'arrete_le' => now(),
        ]);

        // Notifier le responsable projet
        if ($sprint->projet?->responsable_projet_id) {
            NotificationApp::create([
                'user_id' => $sprint->projet->responsable_projet_id,
                'type' => 'sprint_arret',
                'contenu' => "Sprint {$sprint->numero} arrêté par " . $r->user()->nomComplet() . ". Motif : {$r->justification}",
            ]);
        }

        return back()->with('succes', "Sprint arrêté. Motif enregistré.");
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

    /** Modifier une tâche existante du sprint. */
    public function modifierTache(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);

        $r->validate([
            'intitule' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
            'priorite' => 'required|in:haute,moyenne,basse',
            'date_debut' => 'nullable|date',
            'echeance' => 'nullable|date',
            'points' => 'nullable|integer|min:1',
        ]);

        $tache->update($r->only(['intitule', 'user_id', 'priorite', 'date_debut', 'echeance', 'points']));

        return back()->with('succes', 'Tâche mise à jour.');
    }

    /** Supprimer une tâche du sprint. */
    public function supprimerTache(Request $r, Sprint $sprint, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        abort_unless($tache->sprint_id === $sprint->id, 404);
        $r->validate(['motif_suppression' => 'required|string|min:5']);

        $intitule = $tache->intitule;
        $tache->delete();

        return back()->with('succes', "Tâche « {$intitule} » supprimée.");
    }

    /** Supprimer un sprint et toutes ses tâches. */
    public function destroy(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);

        if ($sprint->statut === 'en_cours') {
            return back()->with('erreur', 'Impossible de supprimer un sprint en cours. Arrêtez-le d\'abord.');
        }

        $numero = $sprint->numero;
        $projetLib = $sprint->projet?->libelle ?? '';

        // Supprimer les tâches rattachées
        $sprint->taches()->delete();
        $sprint->delete();

        return redirect()->route('sprints.index')->with('succes', "Sprint {$numero} ({$projetLib}) supprimé.");
    }

    /** Import xlsx de tâches rattachées à un projet/sprint. */
    public function importerTaches(Request $r)
    {
        abort_unless($r->user()->peutGererProjet(), 403);

        // Vérifier le fichier
        if (!$r->hasFile('fichier')) {
            return back()->with('erreur', 'Aucun fichier sélectionné.');
        }

        $fichier = $r->file('fichier');
        $ext = strtolower($fichier->getClientOriginalExtension());
        if ($ext !== 'xlsx') {
            return back()->with('erreur', 'Format invalide. Seuls les fichiers .xlsx sont acceptés.');
        }

        try {
            $lecteur = new LecteurXlsx($fichier->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Impossible de lire le fichier Excel : ' . $e->getMessage());
        }

        try {
            // Détecter la ligne d'en-tête
            $brut = $lecteur->lire(1);
            if (empty($brut)) {
                return back()->with('erreur', 'Le fichier est vide.');
            }

            $ligneEntete = 1;
            foreach ($brut as $i => $row) {
                foreach ($row as $cell) {
                    if (is_string($cell) && preg_match('/intitul|tâche|tache/iu', $cell)) {
                        $ligneEntete = $i + 1;
                        break 2;
                    }
                }
            }

            $data = $lecteur->lire(1, $ligneEntete);
            if (empty($data)) {
                return back()->with('erreur', 'Aucune ligne de données trouvée après l\'en-tête.');
            }

            $importees = 0;
            $ignores = 0;
            $erreurs = [];

            foreach ($data as $idx => $row) {
                $numLigne = $idx + $ligneEntete + 1;

                try {
                    $intitule = trim($this->chercher($row, ['intitulé', 'intitule', 'tâche', 'tache', 'libellé', 'libelle']) ?? '');
                    if (empty($intitule)) { $ignores++; continue; }

                    $projetStr = trim($this->chercher($row, ['projet']) ?? '');
                    $assigneStr = trim($this->chercher($row, ['assigné', 'assigne', 'assigné_à', 'assigne_a', 'collaborateur', 'responsable']) ?? '');
                    $dateDebutStr = trim($this->chercher($row, ['date_début', 'date_debut', 'début', 'debut', 'date']) ?? '');
                    $echeanceStr = trim($this->chercher($row, ['échéance', 'echeance', 'date_fin', 'fin']) ?? '');

                    // Champs obligatoires : assigné, date début, échéance
                    $manquants = [];
                    if (empty($assigneStr)) $manquants[] = 'Assigné à';
                    if (empty($dateDebutStr)) $manquants[] = 'Date début';
                    if (empty($echeanceStr)) $manquants[] = 'Échéance';
                    if (!empty($manquants)) {
                        $erreurs[] = "Ligne {$numLigne} : champ(s) obligatoire(s) manquant(s) — " . implode(', ', $manquants);
                        $ignores++;
                        continue;
                    }
                    $priorite = trim($this->chercher($row, ['priorité', 'priorite']) ?? '');
                    $points = trim($this->chercher($row, ['points', 'story_points']) ?? '');

                    // Trouver le projet
                    $projetId = null;
                    if ($projetStr) {
                        $projet = Projet::whereRaw('LOWER(libelle) = ?', [mb_strtolower($projetStr)])->first();
                        if (!$projet) {
                            $projet = Projet::create([
                                'code' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::slug($projetStr, '_')),
                                'libelle' => $projetStr,
                                'statut' => 'actif',
                            ]);
                        }
                        $projetId = $projet->id;
                    }

                    // Sprint rattaché → utiliser son projet_id si pas de projet dans le fichier
                    $sprintId = $r->sprint_id ?: null;
                    if ($sprintId && !$projetId) {
                        $sprint = Sprint::find($sprintId);
                        $projetId = $sprint?->projet_id;
                    }

                    // Trouver le collaborateur assigné
                    $userId = $r->user()->id;
                    if ($assigneStr) {
                        $parts = preg_split('/\s+/', $assigneStr, 2);
                        $nom = mb_strtolower($parts[0]);
                        $prenom = isset($parts[1]) ? mb_strtolower($parts[1]) : null;
                        $user = User::whereRaw('LOWER(name) = ?', [$nom])->first();
                        if (!$user && $prenom) {
                            $user = User::whereRaw('LOWER(prenom) = ?', [$prenom])->first();
                        }
                        if ($user) $userId = $user->id;
                    }

                    // Mapper priorité
                    $prioMapped = match (mb_strtolower($priorite)) {
                        'haute', 'élevée', 'high', 'elevee' => 'haute',
                        'basse', 'faible', 'low' => 'basse',
                        default => 'moyenne',
                    };

                    // Parser les dates (gère les dates texte ET les nombres sériels Excel)
                    $dateDebut = $this->parserDate($dateDebutStr);
                    $echeance = $this->parserDate($echeanceStr);

                    Tache::create([
                        'user_id' => $userId,
                        'projet_id' => $projetId,
                        'sprint_id' => $sprintId,
                        'intitule' => $intitule,
                        'type' => $sprintId ? 'T1' : 'T2',
                        'statut' => 'a_faire',
                        'priorite' => $prioMapped,
                        'date_debut' => $dateDebut ?? today()->format('Y-m-d'),
                        'echeance' => $echeance,
                        'points' => is_numeric($points) ? (int) $points : null,
                    ]);
                    $importees++;
                } catch (\Throwable $e) {
                    $erreurs[] = "Ligne {$numLigne} : {$e->getMessage()}";
                    $ignores++;
                    if (count($erreurs) >= 5) break;
                }
            }

            $msg = "{$importees} tâche(s) importée(s)";
            if ($ignores > 0) $msg .= ", {$ignores} ignorée(s)";
            if (!empty($erreurs)) {
                $msg .= '. Erreurs : ' . implode(' | ', array_slice($erreurs, 0, 3));
                return back()->with('erreur', $msg);
            }
            return back()->with('succes', $msg . '.');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    /** Cherche une valeur dans un tableau associatif par clés possibles (accent-insensitive). */
    private function chercher(array $row, array $cles): ?string
    {
        // Normaliser les clés du row (retirer accents pour comparaison)
        $normalized = [];
        foreach ($row as $key => $val) {
            $normalized[$this->sansAccent($key)] = $val;
        }
        foreach ($cles as $cle) {
            $cleSans = $this->sansAccent($cle);
            // Chercher exact d'abord
            if (isset($row[$cle]) && $row[$cle] !== null) return (string) $row[$cle];
            // Chercher sans accent
            if (isset($normalized[$cleSans]) && $normalized[$cleSans] !== null) return (string) $normalized[$cleSans];
        }
        return null;
    }

    /** Retirer les accents d'une chaîne. */
    private function sansAccent(string $str): string
    {
        $str = mb_strtolower($str);
        $map = ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a',
                'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c'];
        return strtr($str, $map);
    }

    /** Parser une date (texte ou nombre sériel Excel). */
    private function parserDate(?string $val): ?string
    {
        if (!$val || $val === '') return null;
        // Nombre sériel Excel (ex: 45000 → date)
        if (is_numeric($val) && (int) $val > 30000 && (int) $val < 80000) {
            $timestamp = \Carbon\Carbon::createFromDate(1899, 12, 30)->addDays((int) $val);
            return $timestamp->format('Y-m-d');
        }
        try {
            return \Carbon\Carbon::parse($val)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
