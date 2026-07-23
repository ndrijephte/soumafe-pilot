<?php
namespace App\Http\Controllers;

use App\Models\Tache;
use Illuminate\Http\Request;

class CommentaireController extends Controller
{
    public function store(Request $r, Tache $tache)
    {
        $r->validate(['contenu' => 'required|string|max:2000']);
        $tache->commentaires()->create([
            'user_id' => $r->user()->id,
            'contenu' => $r->contenu,
        ]);
        return back()->with('succes', 'Commentaire ajouté.');
    }
}
