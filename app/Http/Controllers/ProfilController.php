<?php
namespace App\Http\Controllers;

use App\Models\{NotificationApp, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Schema};

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

    /**
     * Reset mot de passe d'un collaborateur (charge_operations / admin uniquement)
     */
    public function resetMotDePasse(Request $r, User $utilisateur)
    {
        abort_unless($r->user()->estAdmin(), 403);

        $nouveau = 'ChangezMoi!' . now()->format('Y');
        $utilisateur->update(['password' => Hash::make($nouveau)]);

        // Notifier le collaborateur
        if (Schema::hasTable('notification_apps')) {
            NotificationApp::create([
                'user_id' => $utilisateur->id,
                'type'    => 'reset_mdp',
                'message' => "Votre mot de passe a été réinitialisé par {$r->user()->nomComplet()}. Nouveau mot de passe : {$nouveau}",
                'lien'    => route('profil.index'),
            ]);
        }

        return back()->with('succes', "Mot de passe de {$utilisateur->nomComplet()} réinitialisé à : {$nouveau}");
    }
}
