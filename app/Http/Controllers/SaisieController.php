<?php
namespace App\Http\Controllers;

use App\Models\{Commentaire, ConfirmationTache, Evenement, JourFerie, JourSaisie, NotificationApp, Tache, TypeEvenement, User};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SaisieController extends Controller
{
    /** Page de saisie quotidienne : confirmation tâches + événements + historique. */
    public function index(Request $r)
    {
        $user = $r->user();
        $userId = $user->id;

        // Tâches planifiées du jour (T1 sprint + T2 datées) non terminées
        $tachesDuJour = Tache::with(['projet', 'sprint'])
            ->where('user_id', $userId)
            ->whereIn('statut', ['a_faire', 'en_cours', 'bloque'])
            ->where(function ($q) {
                $q->whereDate('date_debut', '<=', today())
                  ->where(function ($q2) {
                      $q2->whereNull('echeance')->orWhereDate('echeance', '>=', today());
                  });
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
        $typesEvenement = \Illuminate\Support\Facades\Schema::hasTable('types_evenement')
            ? TypeEvenement::orderBy('libelle')->get()
            : collect();

        // === Section pilote : collaborateurs sans saisie hier + justifications en attente ===
        $sanseSaisie = collect();
        $justificationsEnAttente = collect();
        if ($user->estPilote()) {
            $hier = today()->subDay();
            if (!$hier->isWeekend() && !JourFerie::whereDate('date', $hier)->exists()) {
                $actifs = User::where('actif', true)->whereNotIn('role', ['direction', 'invite'])->get();
                $saisisHier = JourSaisie::whereDate('date', $hier)
                    ->whereIn('statut', ['saisi', 'justifie'])->pluck('user_id')->toArray();
                $sanseSaisie = $actifs->filter(fn ($u) => !in_array($u->id, $saisisHier));
            }

            // Justifications soumises en attente de validation pilote
            $justificationsEnAttente = JourSaisie::with('user')
                ->where('statut', 'justifie')
                ->orderByDesc('date')->limit(20)->get();
        }

        return view('saisie.index', compact(
            'tachesDuJour', 'confirmations', 'jours', 'evenementsDuJour',
            'historique', 'evenementsRecents', 'typesEvenement',
            'sanseSaisie', 'justificationsEnAttente'
        ));
    }

    /** Confirmer ou infirmer une tâche du jour. */
    public function confirmer(Request $r, Tache $tache)
    {
        abort_unless($tache->user_id === $r->user()->id, 403);
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
            // T1 (sprint) par un collaborateur → circuit de validation
            if ($tache->type === 'T1' && !$r->user()->estPilote()) {
                $tache->update(['statut' => 'a_valider']);
                // Notifier les pilotes
                $pilotes = User::where('actif', true)->whereIn('role', ['administrateur', 'charge_operations', 'direction'])->get();
                foreach ($pilotes as $p) {
                    NotificationApp::create([
                        'user_id' => $p->id, 'type' => 'validation',
                        'contenu' => $r->user()->nomComplet() . ' a confirmé « ' . Str::limit($tache->intitule, 40) . ' » — en attente de validation.',
                        'lu' => false,
                    ]);
                }
            } else {
                $tache->update(['statut' => 'termine', 'date_fin_reelle' => today()]);
            }
        }
        // Si infirmée, la tâche reste en l'état (en_cours ou a_faire)

        // Marquer le jour comme saisi si pas encore fait
        JourSaisie::firstOrCreate(
            ['user_id' => $r->user()->id, 'date' => today()],
            ['statut' => 'saisi']
        );

        if ($r->statut === 'confirmee') {
            $msg = ($tache->type === 'T1' && !$r->user()->estPilote())
                ? 'Tâche soumise à validation par le pilote.'
                : 'Tâche confirmée comme réalisée.';
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
        abort_unless($jour->user_id === $r->user()->id, 403);
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
