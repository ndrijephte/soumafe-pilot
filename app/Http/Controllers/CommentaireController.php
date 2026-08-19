<?php
namespace App\Http\Controllers;

use App\Models\{NotificationApp, Tache};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommentaireController extends Controller
{
    public function store(Request $r, Tache $tache)
    {
        $r->validate(['contenu' => 'required|string|max:2000']);
        $tache->commentaires()->create([
            'user_id'  => $r->user()->id,
            'contenu'  => $r->contenu,
            'contexte' => 'suivi',
        ]);

        // Notifier le collaborateur assigné si c'est un pilote/DG qui commente
        $auteur = $r->user();
        if ($tache->user_id !== $auteur->id && ($auteur->estPilote() || $auteur->aRole('direction'))) {
            NotificationApp::create([
                'user_id' => $tache->user_id,
                'type'    => 'commentaire',
                'contenu' => $auteur->nomComplet() . ' a commenté votre tâche « ' . Str::limit($tache->intitule, 40) . ' »',
                'lu'      => false,
            ]);
        }

        return back()->with('succes', 'Commentaire ajouté.');
    }
}
