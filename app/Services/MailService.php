<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Envoie une notification e-mail à un utilisateur.
     * Silencieux en cas d'erreur (SMTP non configuré, etc.)
     */
    public static function notifier(User $destinataire, string $sujet, string $message, ?string $lien = null): bool
    {
        if (!$destinataire->email) return false;

        try {
            $data = [
                'nom'     => $destinataire->nomComplet(),
                'contenu' => $message,
                'lien'    => $lien,
                'sujet'   => $sujet,
            ];

            Mail::send('emails.notification', $data, function ($m) use ($destinataire, $sujet) {
                $m->to($destinataire->email, $destinataire->nomComplet())
                  ->subject('[SOUMAFE PILOT] ' . $sujet);
            });

            return true;
        } catch (\Throwable $e) {
            Log::warning('MailService::notifier échoué - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une notification à plusieurs utilisateurs.
     */
    public static function notifierPlusieurs($destinataires, string $sujet, string $message, ?string $lien = null): int
    {
        $envoyes = 0;
        foreach ($destinataires as $user) {
            if (self::notifier($user, $sujet, $message, $lien)) $envoyes++;
        }
        return $envoyes;
    }
}
