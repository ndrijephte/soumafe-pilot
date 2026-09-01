<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConnexionController extends Controller
{
    public function formulaire()
    {
        return auth()->check() ? redirect()->route('taches.index') : view('auth.login');
    }

    public function connecter(Request $r)
    {
        $identifiants = $r->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($identifiants + ['actif' => 1], true)) {
            $r->session()->regenerate();
            AuditLog::log('connexion', 'auth', auth()->user()->email);
            return redirect()->intended(route('taches.index'));
        }

        return back()->withInput($r->only('email'))
            ->withErrors(['email' => 'Identifiants incorrects ou compte inactif.']);
    }

    public function deconnecter(Request $r)
    {
        AuditLog::log('deconnexion', 'auth', auth()->user()?->email);
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('login');
    }
}
