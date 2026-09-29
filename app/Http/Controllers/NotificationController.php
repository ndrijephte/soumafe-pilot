<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, NotificationApp, User};
use App\Services\MailService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Page complète : toutes les notifications. */
    public function index(Request $r)
    {
        $filtre = $r->get('filtre', 'toutes');
        $query = NotificationApp::where('user_id', $r->user()->id)->orderByDesc('created_at');
        if ($filtre === 'non_lues') $query->where('lu', false);
        if ($filtre === 'lues') $query->where('lu', true);
        $notifications = $query->paginate(30)->appends(['filtre' => $filtre]);
        return view('notifications.index', compact('notifications', 'filtre'));
    }

    /** Notifications non lues (JSON pour la cloche). */
    public function nonLues(Request $r)
    {
        $notifs = NotificationApp::where('user_id', $r->user()->id)
            ->where('lu', false)
            ->orderByDesc('created_at')
            ->limit(20)->get(['id', 'type', 'contenu', 'lien', 'created_at']);
        return response()->json($notifs);
    }

    /** Marquer une notification comme lue. */
    public function lire(Request $r, NotificationApp $notification)
    {
        abort_unless((int) $notification->user_id === (int) $r->user()->id, 403);
        $notification->update(['lu' => true]);
        return response()->json(['ok' => true]);
    }

    /** Marquer toutes comme lues. */
    public function toutLire(Request $r)
    {
        NotificationApp::where('user_id', $r->user()->id)->where('lu', false)->update(['lu' => true]);
        return response()->json(['ok' => true]);
    }

    /** Envoyer une notification depuis la fiche collaborateur (pilote uniquement). */
    public function envoyer(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403, 'Réservé aux pilotes.');
        $r->validate([
            'user_id' => 'required|exists:users,id',
            'sujet'   => 'required|string|max:200',
            'contenu' => 'required|string|max:5000',
            'canal'   => 'required|in:in_app,email,les_deux',
        ]);

        $dest = User::findOrFail($r->user_id);
        $contenuHtml = strip_tags($r->contenu, '<b><i><u><strong><em><ul><ol><li><br><p>');
        $contenuTexte = strip_tags($r->contenu);
        $envoyeApp = false;
        $envoyeEmail = false;

        // Notification in-app (HTML formaté)
        if (in_array($r->canal, ['in_app', 'les_deux'])) {
            NotificationApp::create([
                'user_id' => $dest->id,
                'type'    => 'message',
                'contenu' => '<strong>' . e($r->sujet) . '</strong><br>' . $contenuHtml,
                'lien'    => null,
                'lu'      => false,
                'canal'   => 'in_app',
            ]);
            $envoyeApp = true;
        }

        // Notification email
        if (in_array($r->canal, ['email', 'les_deux'])) {
            $envoyeEmail = MailService::notifier($dest, $r->sujet, $r->contenu);
        }

        AuditLog::log('notification', 'collaborateur', $dest->nomComplet(), "Sujet: {$r->sujet}, Canal: {$r->canal}");

        $msg = 'Notification envoyée';
        if ($envoyeApp && $envoyeEmail) $msg .= ' (in-app + email).';
        elseif ($envoyeApp) $msg .= ' (in-app).';
        elseif ($envoyeEmail) $msg .= ' (email).';
        else $msg = 'Echec de l\'envoi.';

        return back()->with('succes', $msg);
    }
}
