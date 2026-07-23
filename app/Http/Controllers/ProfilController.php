<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfilController extends Controller
{
    public function index(Request $r)
    {
        return view('profil.index', ['user' => $r->user()]);
    }

    public function updateMotDePasse(Request $r)
    {
        $r->validate([
            'mot_de_passe_actuel' => 'required',
            'mot_de_passe' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($r->mot_de_passe_actuel, $r->user()->password)) {
            return back()->withErrors(['mot_de_passe_actuel' => 'Le mot de passe actuel est incorrect.']);
        }

        $r->user()->update(['password' => Hash::make($r->mot_de_passe)]);

        return back()->with('succes', 'Mot de passe modifié avec succès.');
    }
}
