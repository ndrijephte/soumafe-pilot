<?php
namespace App\Services;

use App\Models\{MaintenancePreventive, NotificationApp, Panne, Penalite, RecetteVehicule, User, Vehicule};
use Illuminate\Support\Facades\{DB, Schema};

class AlerteParcService
{
    /**
     * Vérifie les conditions d'alerte parc et crée des notifications
     * pour tous les utilisateurs ayant accès au parc.
     * Anti-doublon : max 1 notif par type/jour.
     */
    public static function verifier(): void
    {
        if (!Schema::hasTable('vehicules') || !Schema::hasTable('notifications_app')) return;

        $destinataires = User::where('actif', true)->get()
            ->filter(fn($u) => $u->peutGererParc());

        if ($destinataires->isEmpty()) return;

        $aujourdhui = today()->toDateString();

        // ─── 1. Maintenances en retard ───
        $maintRetard = MaintenancePreventive::with('vehicule')
            ->whereNotNull('prochaine_echeance')
            ->whereDate('prochaine_echeance', '<', today())
            ->get();

        if ($maintRetard->count() > 0) {
            $details = $maintRetard->take(3)->map(fn($m) =>
                ($m->vehicule->immatriculation ?? '?') . ' - ' . $m->typeLabel()
            )->join(', ');

            $contenu = $maintRetard->count() . ' maintenance(s) en retard : ' . $details;
            if ($maintRetard->count() > 3) $contenu .= '...';

            self::creerSiAbsent($destinataires, 'alerte_maintenance_retard', $contenu, $aujourdhui);
        }

        // ─── 2. Maintenances proches (≤ 7 jours) ───
        $maintProche = MaintenancePreventive::with('vehicule')
            ->whereNotNull('prochaine_echeance')
            ->whereDate('prochaine_echeance', '>=', today())
            ->whereDate('prochaine_echeance', '<=', today()->addDays(7))
            ->get();

        if ($maintProche->count() > 0) {
            $details = $maintProche->take(3)->map(fn($m) =>
                ($m->vehicule->immatriculation ?? '?') . ' - ' . $m->typeLabel() .
                ' (' . $m->prochaine_echeance->format('d/m') . ')'
            )->join(', ');

            $contenu = $maintProche->count() . ' maintenance(s) à échéance sous 7 jours : ' . $details;
            if ($maintProche->count() > 3) $contenu .= '...';

            self::creerSiAbsent($destinataires, 'alerte_maintenance_proche', $contenu, $aujourdhui);
        }

        // ─── 3. Recettes sous objectif (veille) ───
        $hier = today()->subDay();
        $recettesHier = RecetteVehicule::with('vehicule')
            ->whereDate('date', $hier)
            ->where('ecart', '<', 0)
            ->get();

        if ($recettesHier->count() > 0) {
            $details = $recettesHier->take(3)->map(fn($r) =>
                ($r->vehicule->immatriculation ?? '?') . ' : ' .
                number_format($r->recette, 0, ',', ' ') . '/' .
                number_format($r->objectif, 0, ',', ' ')
            )->join(', ');

            $contenu = $recettesHier->count() . ' véhicule(s) sous objectif hier : ' . $details;
            if ($recettesHier->count() > 3) $contenu .= '...';

            self::creerSiAbsent($destinataires, 'alerte_recette_objectif', $contenu, $aujourdhui);
        }

        // ─── 4. Pénalités impayées ───
        if (Schema::hasTable('penalites')) {
            $penImpayees = Penalite::with('vehicule')
                ->where('statut', 'non_payee')
                ->get();

            if ($penImpayees->count() > 0) {
                $totalMontant = $penImpayees->sum('montant');
                $contenu = $penImpayees->count() . ' pénalité(s) impayée(s) - total : ' .
                    number_format($totalMontant, 0, ',', ' ') . ' FCFA';

                self::creerSiAbsent($destinataires, 'alerte_penalite_impayee', $contenu, $aujourdhui);
            }
        }

        // ─── 5. Pannes non résolues depuis plus de 7 jours ───
        $pannesAgees = Panne::with('vehicule')
            ->whereIn('statut', ['declaree', 'en_diagnostic', 'en_intervention'])
            ->whereDate('date_declaration', '<=', today()->subDays(7))
            ->get();

        if ($pannesAgees->count() > 0) {
            $details = $pannesAgees->take(3)->map(fn($p) =>
                ($p->vehicule->immatriculation ?? '?') . ' - ' .
                $p->date_declaration->diffInDays(today()) . 'j'
            )->join(', ');

            $contenu = $pannesAgees->count() . ' panne(s) non résolue(s) depuis +7j : ' . $details;
            if ($pannesAgees->count() > 3) $contenu .= '...';

            self::creerSiAbsent($destinataires, 'alerte_panne_agee', $contenu, $aujourdhui);
        }
    }

    /**
     * Crée une notification pour chaque destinataire si aucune du même type
     * n'existe déjà aujourd'hui.
     */
    private static function creerSiAbsent($destinataires, string $type, string $contenu, string $date): void
    {
        foreach ($destinataires as $user) {
            $existe = NotificationApp::where('user_id', $user->id)
                ->where('type', $type)
                ->whereDate('created_at', $date)
                ->exists();

            if (!$existe) {
                NotificationApp::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'contenu' => $contenu,
                    'lien' => route('parc.index'),
                    'lu' => false,
                    'canal' => 'in_app',
                ]);
            }
        }
    }
}
