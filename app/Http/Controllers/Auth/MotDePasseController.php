<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Illuminate\Support\Str;

class MotDePasseController extends Controller
{
    /**
     * Affiche le formulaire "Mot de passe oublié"
     */
    public function formulaireOubli()
    {
        return view('auth.mot-de-passe-oublie');
    }

    /**
     * Envoie le lien de réinitialisation par email
     */
    public function envoyerLien(Request $r)
    {
        $r->validate(['email' => 'required|email']);

        $user = User::where('email', $r->email)->where('actif', true)->first();

        if (!$user) {
            return back()->withInput()
                ->withErrors(['email' => 'Aucun compte actif trouvé avec cette adresse.']);
        }

        // Créer la table si absente
        if (!Schema::hasTable('password_resets')) {
            Schema::create('password_resets', function ($table) {
                $table->string('email')->index();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Supprimer les anciens tokens
        DB::table('password_resets')->where('email', $user->email)->delete();

        // Générer un token
        $token = Str::random(64);
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        // Lien de réinitialisation
        $lien = url('/reinitialiser-mot-de-passe?token=' . $token . '&email=' . urlencode($user->email));

        $message = "Vous avez demandé la réinitialisation de votre mot de passe SOUMAFE PILOT.\n\n"
            . "Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe.\n"
            . "Ce lien est valide pendant 60 minutes.\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.";

        $envoye = MailService::notifier($user, 'Réinitialisation de mot de passe', $message, $lien);

        if ($envoye) {
            return back()->with('succes', 'Un lien de réinitialisation a été envoyé à votre adresse email.');
        }

        // Email échoué — afficher le lien directement (usage interne)
        return back()->with('lien_reset', $lien)
            ->with('warn', 'L\'envoi d\'email a échoué (SMTP non configuré). Utilisez le lien ci-dessous.');
    }

    /**
     * Affiche le formulaire de réinitialisation
     */
    public function formulaireReset(Request $r)
    {
        return view('auth.reinitialiser-mot-de-passe', [
            'token' => $r->get('token'),
            'email' => $r->get('email'),
        ]);
    }

    /**
     * Réinitialise le mot de passe
     */
    public function reset(Request $r)
    {
        $r->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Schema::hasTable('password_resets')) {
            return back()->withErrors(['email' => 'Lien invalide ou expiré.']);
        }

        $record = DB::table('password_resets')
            ->where('email', $r->email)
            ->first();

        if (!$record) {
            return back()->withErrors(['email' => 'Lien invalide ou expiré.']);
        }

        // Vérifier expiration (60 minutes)
        if (now()->diffInMinutes($record->created_at) > 60) {
            DB::table('password_resets')->where('email', $r->email)->delete();
            return back()->withErrors(['email' => 'Ce lien a expiré. Veuillez refaire une demande.']);
        }

        // Vérifier le token
        if (!Hash::check($r->token, $record->token)) {
            return back()->withErrors(['email' => 'Lien invalide ou expiré.']);
        }

        // Mettre à jour le mot de passe
        $user = User::where('email', $r->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Compte introuvable.']);
        }

        $user->password = Hash::make($r->password);
        $user->save();

        // Supprimer le token utilisé
        DB::table('password_resets')->where('email', $r->email)->delete();

        return redirect()->route('login')
            ->with('succes', 'Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
    }
}
