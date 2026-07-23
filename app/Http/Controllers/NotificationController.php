<?php
namespace App\Http\Controllers;

use App\Models\NotificationApp;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Notifications non lues (JSON pour la cloche). */
    public function nonLues(Request $r)
    {
        $notifs = NotificationApp::where('user_id', $r->user()->id)
            ->where('lu', false)
            ->orderByDesc('created_at')
            ->limit(20)->get(['id', 'type', 'contenu', 'created_at']);
        return response()->json($notifs);
    }

    /** Marquer une notification comme lue. */
    public function lire(Request $r, NotificationApp $notification)
    {
        abort_unless($notification->user_id === $r->user()->id, 403);
        $notification->update(['lu' => true]);
        return response()->json(['ok' => true]);
    }

    /** Marquer toutes comme lues. */
    public function toutLire(Request $r)
    {
        NotificationApp::where('user_id', $r->user()->id)->where('lu', false)->update(['lu' => true]);
        return response()->json(['ok' => true]);
    }
}
