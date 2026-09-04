<?php
namespace App\Http\Controllers;

use App\Models\{Absence, Commentaire, ConfirmationTache, Evenement, JourFerie, JourSaisie, NotificationApp, Tache, TypeEvenement, User};
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SaisieController extends Controller
{
    /** Page de saisie quotidienne : confirmation tâches + événements + historique. */
    public function index(Request $r)
    {
        $user = $r->user();
        if ($user?->aRole('direction')) {
            return redirect()->route('dashboard');
        }
        $userId = $user->id;

        // Tâches du jour : uniquement celles dont le sprint est en_cours (ou sans sprint / T3)
        $tachesDuJour = Tache::with(['projet', 'sprint'])
            ->where('user_id', $userId)
            ->whereIn('statut', ['a_faire', 'en_cours', 'bloque'])
            ->where(function ($q) {
                // T1/T2 : date_debut passée et échéance non dépassée
                $q->where(function ($q1) {
                    $q1->whereDate('date_debut', '<=', today())
                        ->where(function ($q2) {
                            $q2->whereNull('echeance')->orWhereDate('echeance', '>=', today());
                        });
                })
                // T3 urgentes : toujours visibles tant qu'actives (pas de filtre date)
                ->orWhere('type', 'T3');
            })
            // Exclure les tâches de sprints non démarrés ou terminés
            ->where(function ($q) {
                $q->whereNull('sprint_id') // tâches hors sprint (T2, T3)
                  ->orWhereHas('sprint', fn ($s) => $s->where('statut', 'en_cours'));
            })
            ->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")
            ->get();

        // Confirmations déjà faites aujourd'hui
        $confirmations = ConfirmationTache::where('user_id', $userId)
            ->whereDate('date', today())->pluck('statut', 'tache_id');

        // Jours à justifier
        $jours = JourSaisie::where('user_id', $userId)
            ->where('statut', 'a_justifier')
            ->orderByDesc('date')->get();

        // Événements du jour
        $evenementsDuJour = Evenement::where('user_id', $userId)
            ->whereDate('date', today())->get();

        // Historique 30 jours
        $historique = JourSaisie::where('user_id', $userId)
            ->orderByDesc('date')->limit(30)->get();

        // Événements récents (pour l'historique)
        $evenementsRecents = Evenement::where('user_id', $userId)
            ->orderByDesc('date')->limit(10)->get();

        // Types d'événements paramétrables
        $typesEvenement = Schema::hasTable('types_evenement')
            ? TypeEvenement::orderBy('libelle')->get()
            : collect();

        // Absences déclarées (à venir ou en cours)
        $absences = collect();
        if (Schema::hasTable('absences')) {
            $absences = Absence::where('user_id', $userId)
                ->whereDate('date_fin', '>=', today())
                ->orderBy('date_debut')->get();
        }

        // === Section pilote : collaborateurs sans saisie hier + justifications en attente ===
        $sanseSaisie = collect();
        $justificationsEnAttente = collect();
        if ($user->estPilote()) {
            $hier = today()->subDay();
            if (!$hier->isWeekend() && !JourFerie::whereDate('date', $hier)->exists()) {
                $actifs = User::where('actif', true)->whereNotIn('role', ['direction', 'invite'])->get();

                // IDs ayant un JourSaisie saisi/justifié hier
                $saisisHier = JourSaisie::whereDate('date', $hier)
                    ->whereIn('statut', ['saisi', 'justifie'])->pluck('user_id')->map(fn($v) => (int)$v)->toArray();

                // IDs ayant confirmé/infirmé au moins une tâche hier (via saisie ou kanban)
                if (Schema::hasTable('confirmation_taches')) {
                    $confirmesHier = ConfirmationTache::whereDate('date', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->unique()->toArray();
                    $saisisHier = array_values(array_unique(array_merge($saisisHier, $confirmesHier)));
                }

                // IDs en absence déclarée hier
                if (Schema::hasTable('absences')) {
                    $absentsHier = Absence::whereDate('date_debut', '<=', $hier)
                        ->whereDate('date_fin', '>=', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->toArray();
                    $saisisHier = array_values(array_unique(array_merge($saisisHier, $absentsHier)));
                }

                // Aussi vérifier tâches terminées hier (date_fin_reelle = hier)
                if (Schema::hasTable('taches')) {
                    $terminesHier = \App\Models\Tache::where('statut', 'termine')
                        ->whereDate('date_fin_reelle', $hier)->pluck('user_id')->map(fn($v) => (int)$v)->unique()->toArray();
                    $saisisHier = array_values(array_unique(array_merge($saisisHier, $terminesHier)));
                }

                $sanseSaisie = $actifs->filter(fn ($u) => !in_array((int)$u->id, $saisisHier, true));
            }

            // Justifications soumises en attente de validation pilote
            $justificationsEnAttente = JourSaisie::with('user')
                ->where('statut', 'justifie')
                ->orderByDesc('date')->limit(20)->get();

            // IDs des collaborateurs déjà rappelés aujourd'hui
            $rappelsAujourdhui = NotificationApp::where('type', 'rappel')
                ->whereDate('created_at', today())
                ->pluck('user_id')->map(fn($v) => (int)$v)->toArray();
        }

        // Absences des collaborateurs (pour pilotes)
        $absencesEquipe = collect();
        if ($user->estPilote() && Schema::hasTable('absences')) {
            $absencesEquipe = Absence::with('user')
                ->whereDate('date_fin', '>=', today())
                ->orderBy('date_debut')->get();
        }

        $rappelsAujourdhui = $rappelsAujourdhui ?? [];

        return view('saisie.index', compact(
            'tachesDuJour', 'confirmations', 'jours', 'evenementsDuJour',
            'historique', 'evenementsRecents', 'typesEvenement',
            'sanseSaisie', 'justificationsEnAttente', 'absences', 'absencesEquipe',
            'rappelsAujourdhui'
        ));
    }

    /** Confirmer ou infirmer une tâche du jour. */
    public function confirmer(Request $r, Tache $tache)
    {
        abort_unless((int) $tache->user_id === (int) $r->user()->id, 403);

        // Bloquer si la tâche appartient à un sprint non en_cours
        if ($tache->sprint_id && $tache->sprint?->statut !== 'en_cours') {
            return back()->with('erreur', 'Ce sprint n\'est pas en cours. Impossible d\'agir sur cette tâche.');
        }
        $r->validate([
            'statut' => 'required|in:confirmee,infirmee',
            'motif' => 'required_if:statut,infirmee|nullable|string|max:500',
            'plan_action' => 'nullable|string|max:500',
        ]);

        ConfirmationTache::updateOrCreate(
            ['user_id' => $r->user()->id, 'tache_id' => $tache->id, 'date' => today()],
            ['statut' => $r->statut, 'motif' => $r->motif, 'plan_action' => $r->plan_action]
        );

        // Mettre à jour le statut de la tâche
        if ($r->statut === 'confirmee') {
            // V1 : clôture directe pour tous les types (T1, T2, T3)
            $tache->update(['statut' => 'termine', 'date_fin_reelle' => today()]);
        }
        // Si infirmée, la tâche reste en l'état (en_cours ou a_faire)

        // Marquer le jour comme saisi si pas encore fait
        JourSaisie::firstOrCreate(
            ['user_id' => $r->user()->id, 'date' => today()],
            ['statut' => 'saisi']
        );

        if ($r->statut === 'confirmee') {
            $msg = 'Tâche confirmée comme réalisée.';
        } else {
            $msg = 'Tâche infirmée — motif enregistré.';
        }
        return back()->with('succes', $msg);
    }

    /** Enregistrer un événement / fait marquant (pilotes uniquement). */
    public function evenement(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'type' => 'required|string|max:50',
            'description' => 'required|string|max:500',
            'action_engagee' => 'nullable|string|max:500',
            'impact' => 'nullable|string|max:255',
        ]);

        Evenement::create([
            'user_id' => $r->user()->id,
            'date' => today(),
            'type' => $r->type,
            'description' => $r->description,
            'action_engagee' => $r->action_engagee,
            'impact' => $r->impact,
        ]);

        // Marquer le jour comme saisi
        JourSaisie::firstOrCreate(
            ['user_id' => $r->user()->id, 'date' => today()],
            ['statut' => 'saisi']
        );

        return back()->with('succes', 'Événement enregistré.');
    }

    /** Justifier un jour manquant. */
    public function justifier(Request $r, JourSaisie $jour)
    {
        abort_unless((int) $jour->user_id === (int) $r->user()->id, 403);
        $r->validate([
            'justification' => 'required|string|max:500',
            'motif_type'    => 'nullable|string|in:conge,mission,maladie,autre',
        ]);

        $type = match($r->motif_type) {
            'conge'   => 'Congé',
            'mission' => 'Mission',
            'maladie' => 'Maladie',
            default   => 'Autre',
        };

        $com = Commentaire::create([
            'tache_id' => null,
            'user_id'  => $r->user()->id,
            'contenu'  => "[{$type} — {$jour->date->format('d/m/Y')}] {$r->justification}",
        ]);

        $jour->update([
            'statut' => 'justifie',
            'justification_commentaire_id' => $com->id,
        ]);

        return back()->with('succes', "Jour du {$jour->date->format('d/m/Y')} justifié ({$type}).");
    }

    /** Déclarer une absence (collaborateur pour lui-même ou pilote pour un collab). */
    public function declarerAbsence(Request $r)
    {
        // Créer la table si la migration n'a pas encore été jouée
        if (!Schema::hasTable('absences')) {
            \Illuminate\Support\Facades\DB::statement("
                CREATE TABLE absences (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    date_debut DATE NOT NULL,
                    date_fin DATE NOT NULL,
                    type VARCHAR(30) NOT NULL,
                    motif VARCHAR(500) NULL,
                    declare_par BIGINT UNSIGNED NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX absences_user_dates (user_id, date_debut, date_fin),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (declare_par) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        $r->validate([
            'user_id' => 'nullable|exists:users,id',
            'date_debut' => 'required|date|after_or_equal:today',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'type' => 'required|in:conge,mission,maladie,formation,autre',
            'motif' => 'nullable|string|max:500',
        ]);

        $userId = $r->user_id && $r->user()->estPilote() ? (int) $r->user_id : $r->user()->id;

        // Vérifier chevauchement
        $chevauchement = Absence::where('user_id', $userId)
            ->where(function ($q) use ($r) {
                $q->whereBetween('date_debut', [$r->date_debut, $r->date_fin])
                  ->orWhereBetween('date_fin', [$r->date_debut, $r->date_fin])
                  ->orWhere(function ($q2) use ($r) {
                      $q2->whereDate('date_debut', '<=', $r->date_debut)
                          ->whereDate('date_fin', '>=', $r->date_fin);
                  });
            })->exists();

        if ($chevauchement) return back()->with('erreur', 'Une absence est déjà déclarée sur cette période.');

        Absence::create([
            'user_id' => $userId,
            'date_debut' => $r->date_debut,
            'date_fin' => $r->date_fin,
            'type' => $r->type,
            'motif' => $r->motif,
            'declare_par' => $r->user()->id,
        ]);

        $nom = $userId != $r->user()->id ? User::find($userId)->nomComplet() : 'Vous';
        return back()->with('succes', "Absence déclarée pour {$nom} du {$r->date_debut} au {$r->date_fin}.");
    }

    /** Annuler une absence déclarée. */
    public function annulerAbsence(Request $r, Absence $absence)
    {
        abort_unless((int) $absence->user_id === (int) $r->user()->id || $r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $absence->delete();
        return back()->with('succes', 'Absence annulée.');
    }

    /** Pilote : envoyer un rappel de saisie à un collaborateur. */
    public function rappeler(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['user_id' => 'required|exists:users,id']);

        $cible = User::findOrFail($r->user_id);
        NotificationApp::create([
            'user_id' => $cible->id,
            'type'    => 'rappel',
            'contenu' => 'Rappel : merci de mettre à jour votre saisie quotidienne. — ' . $r->user()->nomComplet(),
            'lu'      => false,
        ]);

        return back()->with('succes', "Rappel envoyé à {$cible->nomComplet()}.");
    }
}
