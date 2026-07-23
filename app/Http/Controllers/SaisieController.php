<?php
namespace App\Http\Controllers;

use App\Models\{Commentaire, ConfirmationTache, Evenement, JourFerie, JourSaisie, Tache, TypeEvenement};
use Illuminate\Http\Request;

class SaisieController extends Controller
{
    /** Page de saisie quotidienne : confirmation tâches + événements + historique. */
    public function index(Request $r)
    {
        $userId = $r->user()->id;

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

        return view('saisie.index', compact(
            'tachesDuJour', 'confirmations', 'jours', 'evenementsDuJour',
            'historique', 'evenementsRecents', 'typesEvenement'
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
            $tache->update(['statut' => 'termine', 'date_fin_reelle' => today()]);
        }
        // Si infirmée, la tâche reste en l'état (en_cours ou a_faire)

        // Marquer le jour comme saisi si pas encore fait
        JourSaisie::firstOrCreate(
            ['user_id' => $r->user()->id, 'date' => today()],
            ['statut' => 'saisi']
        );

        $msg = $r->statut === 'confirmee' ? 'Tâche confirmée comme réalisée.' : 'Tâche infirmée — motif enregistré.';
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
        $r->validate(['justification' => 'required|string|max:500']);

        $com = Commentaire::create([
            'tache_id' => null,
            'user_id' => $r->user()->id,
            'contenu' => "[Justification saisie {$jour->date->format('d/m/Y')}] {$r->justification}",
        ]);

        $jour->update([
            'statut' => 'justifie',
            'justification_commentaire_id' => $com->id,
        ]);

        return back()->with('succes', "Jour du {$jour->date->format('d/m/Y')} justifié.");
    }
}
