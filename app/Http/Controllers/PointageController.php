<?php

namespace App\Http\Controllers;

use App\Models\{Pointage, User, JourOuvrableSpecial};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Carbon\Carbon;

class PointageController extends Controller
{
    /** Liste de présence du jour (ou date filtrée) */
    public function index(Request $r)
    {
        if (!Schema::hasTable('pointages')) {
            return view('pointage.index', ['users' => collect(), 'pointages' => collect(), 'date' => today(), 'stats' => []]);
        }

        // Clôturer automatiquement les oublis de départ des jours précédents
        $this->cloturerOublis();

        $date = $r->input('date', today()->toDateString());
        $date = Carbon::parse($date);

        // Tous les utilisateurs actifs (sauf invités, admin et DG)
        $users = User::where('actif', true)
            ->whereNotIn('role', ['invite', 'administrateur', 'direction'])
            ->orderBy('name')
            ->get();

        $pointages = Pointage::where('date', $date->toDateString())
            ->get()
            ->keyBy('user_id');

        // Stats du jour
        $total    = $users->count();
        $pointes  = $pointages->whereNotIn('statut', ['non_pointe', 'absent'])->count();
        $retards  = $pointages->where('statut', 'retard')->count();
        $absents  = $pointages->where('statut', 'absent')->count();
        $nonPointes = $total - $pointages->count();

        // Jours ouvrables spéciaux du mois (si table existe)
        $joursSpeciaux = collect();
        if (Schema::hasTable('jours_ouvrables_speciaux')) {
            $joursSpeciaux = JourOuvrableSpecial::dansLaPeriode($date->copy()->startOfMonth(), $date->copy()->endOfMonth());
        }

        // Stats du mois
        $debutMois = $date->copy()->startOfMonth();
        $finMois   = $date->copy()->endOfMonth();
        $moisPointages = Pointage::whereBetween('date', [$debutMois, $finMois])->get();
        $joursOuvres   = 0;
        $d = $debutMois->copy();
        while ($d->lte(min($date, $finMois))) {
            if (!$d->isWeekend() || $joursSpeciaux->contains($d->toDateString())) $joursOuvres++;
            $d->addDay();
        }
        $totalPointsMois = $moisPointages->where('statut', 'present')->count();
        $totalRetardsMois = $moisPointages->where('statut', 'retard')->count();
        $tauxPonctualite = ($joursOuvres * $total) > 0
            ? round(($totalPointsMois / ($joursOuvres * $total)) * 100)
            : 0;

        // Durée moyenne
        $dureeMoy = $moisPointages->filter(fn($p) => $p->heure_arrivee && $p->heure_depart)
            ->avg(fn($p) => $p->dureeHeures());

        $stats = compact('total', 'pointes', 'retards', 'absents', 'nonPointes',
            'tauxPonctualite', 'totalRetardsMois', 'dureeMoy', 'joursOuvres');

        // Jours spéciaux pour la gestion (admin/charge_ops)
        $estGestionnaire = auth()->user()->estAdmin();
        $tousJoursSpeciaux = $estGestionnaire && Schema::hasTable('jours_ouvrables_speciaux')
            ? JourOuvrableSpecial::orderBy('date', 'desc')->get()
            : collect();

        // Forcer le dialog motif retard si l'utilisateur a un retard non justifié aujourd'hui
        $monPointage = Pointage::where('user_id', auth()->id())->where('date', today()->toDateString())->first();
        if ($monPointage && $monPointage->statut === 'retard' && !$monPointage->motif_retard && !session('demande_motif_retard')) {
            session()->flash('demande_motif_retard', $monPointage->id);
        }

        return view('pointage.index', compact('users', 'pointages', 'date', 'stats', 'joursSpeciaux', 'tousJoursSpeciaux', 'estGestionnaire'));
    }

    /**
     * Vérifie si une date est un jour ouvré (lun-ven hors férié, ou jour ouvrable spécial).
     */
    private function estJourOuvre(Carbon $date): bool
    {
        $estFerie = \App\Models\JourFerie::whereDate('date', $date)->exists();
        $estSpecial = Schema::hasTable('jours_ouvrables_speciaux')
            && JourOuvrableSpecial::where('date', $date->toDateString())->exists();

        if ($estSpecial) return true;
        if ($date->isWeekend() || $estFerie) return false;
        return true;
    }

    /** Auto-pointage arrivée */
    public function pointerArrivee(Request $r)
    {
        $user = auth()->user();
        $today = today()->toDateString();
        $now   = now()->format('H:i:s');

        // Bloquer pointage les jours non ouvrés
        if (!$this->estJourOuvre(today())) {
            return redirect()->route('dashboard')
                ->with('erreur', 'Le pointage n\'est pas autorisé aujourd\'hui (jour non ouvré).');
        }

        $pointage = Pointage::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['heure_arrivee' => $now, 'statut' => 'non_pointe']
        );

        if ($pointage->wasRecentlyCreated || !$pointage->heure_arrivee) {
            $pointage->heure_arrivee = $now;
            $pointage->statut = $pointage->calculerStatut();

            // Si retard, le motif sera demandé via le formulaire
            if ($pointage->statut === 'retard' && $r->filled('motif_retard')) {
                $pointage->motif_retard = $r->input('motif_retard');
            }

            $pointage->save();
            $this->auditer('pointer_arrivee', $pointage);
        }

        // Si retard sans motif, rediriger pour saisir le motif
        if ($pointage->statut === 'retard' && !$pointage->motif_retard) {
            return redirect()->route('pointage.index')
                ->with('demande_motif_retard', $pointage->id);
        }

        return redirect()->route('dashboard')
            ->with('succes', 'Arrivée pointée à ' . Carbon::parse($now)->format('H:i'));
    }

    /** Auto-pointage départ */
    public function pointerDepart(Request $r)
    {
        $user = auth()->user();
        $today = today()->toDateString();
        $now   = now()->format('H:i:s');

        $pointage = Pointage::where('user_id', $user->id)->where('date', $today)->first();

        if (!$pointage) {
            return redirect()->route('dashboard')
                ->with('erreur', 'Vous devez d\'abord pointer votre arrivée.');
        }

        if ($pointage->heure_depart) {
            return redirect()->route('dashboard')
                ->with('erreur', 'Départ déjà enregistré.');
        }

        // Motif obligatoire si départ avant 18h
        $hDepart = Carbon::parse($now);
        if ($hDepart->lt(Carbon::parse('18:00')) && !$r->filled('motif_depart')) {
            return redirect()->route('pointage.index')
                ->with('demande_motif_depart_avant', $pointage->id);
        }

        $pointage->heure_depart = $now;
        $pointage->statut = $pointage->calculerStatut();

        if ($r->filled('motif_depart')) {
            $pointage->motif_depart = $r->input('motif_depart');
        }

        $pointage->save();
        $this->auditer('pointer_depart', $pointage);

        return redirect()->route('dashboard')
            ->with('succes', 'Départ pointé à ' . Carbon::parse($now)->format('H:i'));
    }

    /** Sauvegarder un motif (retard ou départ) */
    public function sauverMotif(Request $r, Pointage $pointage)
    {
        $r->validate([
            'type' => 'required|in:retard,depart,absence',
            'motif' => ['required', 'string', 'max:500', function ($attr, $val, $fail) {
                if (mb_strlen(preg_replace('/[\s.\-_,;!?]+/', '', $val)) < 5) {
                    $fail('Le motif doit contenir au moins 5 caractères significatifs.');
                }
            }],
        ]);

        $champ = match ($r->type) {
            'retard'  => 'motif_retard',
            'depart'  => 'motif_depart',
            'absence' => 'motif_absence',
        };

        $pointage->$champ = $r->motif;
        $pointage->save();
        $this->auditer('motif_' . $r->type, $pointage);

        return redirect()->route('pointage.index')
            ->with('succes', 'Motif enregistré.');
    }

    /** Marquer absent (par admin) */
    public function marquerAbsent(Request $r)
    {
        $r->validate([
            'user_id' => 'required|exists:users,id',
            'date'    => 'required|date',
            'motif'   => 'required|string|max:500',
        ]);

        $pointage = Pointage::firstOrCreate(
            ['user_id' => $r->user_id, 'date' => $r->date],
            ['statut' => 'absent']
        );

        $pointage->statut = 'absent';
        $pointage->motif_absence = $r->motif;
        $pointage->save();
        $this->auditer('marquer_absent', $pointage);

        return redirect()->route('pointage.index', ['date' => $r->date])
            ->with('succes', 'Absence enregistrée.');
    }

    /** Correction par admin */
    public function corriger(Request $r, Pointage $pointage)
    {
        $r->validate([
            'heure_arrivee'    => 'nullable|date_format:H:i',
            'heure_depart'     => 'nullable|date_format:H:i',
            'motif_correction' => 'required|string|max:500',
            'motif_retard'     => 'nullable|string|max:500',
            'motif_depart'     => 'nullable|string|max:500',
        ]);

        $pointage->heure_arrivee    = $r->heure_arrivee ? $r->heure_arrivee . ':00' : $pointage->heure_arrivee;
        $pointage->heure_depart     = $r->heure_depart ? $r->heure_depart . ':00' : $pointage->heure_depart;
        $pointage->corrige_par      = auth()->id();
        $pointage->motif_correction = $r->motif_correction;
        if ($r->filled('motif_retard'))  $pointage->motif_retard  = $r->motif_retard;
        if ($r->filled('motif_depart')) $pointage->motif_depart = $r->motif_depart;
        $pointage->statut           = $pointage->calculerStatut();
        $pointage->save();
        $this->auditer('corriger_pointage', $pointage);

        return redirect()->route('pointage.index', ['date' => $pointage->date->toDateString()])
            ->with('succes', 'Pointage corrigé.');
    }

    /** Pointage manuel par admin (pour un collaborateur non-pointé) */
    public function pointerPour(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'user_id'       => 'required|exists:users,id',
            'date'          => 'required|date',
            'heure_arrivee' => 'required|date_format:H:i',
            'heure_depart'  => 'nullable|date_format:H:i',
            'motif_retard'  => 'nullable|string|max:500',
            'motif_depart'  => 'nullable|string|max:500',
        ]);

        $pointage = Pointage::firstOrNew(
            ['user_id' => $r->user_id, 'date' => $r->date]
        );

        $pointage->heure_arrivee = $r->heure_arrivee . ':00';
        if ($r->filled('heure_depart')) {
            $pointage->heure_depart = $r->heure_depart . ':00';
        }
        if ($r->filled('motif_retard'))  $pointage->motif_retard  = $r->motif_retard;
        if ($r->filled('motif_depart')) $pointage->motif_depart = $r->motif_depart;
        $pointage->corrige_par = auth()->id();
        $pointage->motif_correction = 'Pointage manuel par ' . auth()->user()->nomComplet();
        $pointage->statut = $pointage->calculerStatut();
        $pointage->save();

        $user = User::find($r->user_id);
        \App\Models\AuditLog::log('pointage_manuel', 'pointage', $user->nomComplet(), "Arrivée: {$r->heure_arrivee}" . ($r->heure_depart ? ", Départ: {$r->heure_depart}" : ''));

        return redirect()->route('pointage.index', ['date' => $r->date])
            ->with('succes', 'Pointage enregistré pour ' . $user->nomComplet() . '.');
    }

    /** Clôturer les oublis de départ de la veille (appelé dans index) */
    private function cloturerOublis()
    {
        $hier = today()->subDay()->toDateString();
        $oublis = Pointage::where('date', '<', today()->toDateString())
            ->whereNotNull('heure_arrivee')
            ->whereNull('heure_depart')
            ->where('statut', '!=', 'absent')
            ->get();

        foreach ($oublis as $p) {
            $p->heure_depart = '18:00:00';
            $p->motif_depart = 'Oubli de pointage départ — clôturé automatiquement';
            $p->statut = $p->calculerStatut();
            $p->save();
            $this->auditer('cloture_oubli_depart', $p);
        }
    }

    /* ===== JOURS OUVRABLES SPÉCIAUX ===== */

    /** Ajouter un jour ouvrable spécial */
    public function ajouterJourSpecial(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'date' => 'required|date',
            'motif' => 'required|string|max:255',
        ]);

        $date = Carbon::parse($r->date);

        // Vérifier que ce n'est pas déjà un jour ouvré normal (lun-ven hors férié)
        if (!$date->isWeekend() && !\App\Models\JourFerie::whereDate('date', $date)->exists()) {
            return back()->withErrors(['Cette date est déjà un jour ouvré normal.']);
        }

        JourOuvrableSpecial::firstOrCreate(
            ['date' => $date->toDateString()],
            ['motif' => $r->motif, 'created_by' => auth()->id()]
        );

        if (Schema::hasTable('audit_logs') && class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log('creation', 'pointage', 'Jour ouvrable + ' . $date->format('d/m/Y'), $r->motif);
        }

        return redirect()->route('pointage.index', ['date' => $r->input('retour_date', today()->toDateString())])
            ->with('succes', $date->isoFormat('dddd D MMM YYYY') . ' défini comme jour ouvrable.');
    }

    /** Retirer un jour ouvrable spécial */
    public function retirerJourSpecial(Request $r, JourOuvrableSpecial $jour)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);

        $dateStr = $jour->date->format('d/m/Y');
        $jour->delete();

        if (Schema::hasTable('audit_logs') && class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log('suppression', 'pointage', 'Jour ouvrable + ' . $dateStr, "Motif: {$r->motif_suppression}");
        }

        return redirect()->route('pointage.index')
            ->with('succes', $dateStr . ' retiré des jours ouvrables spéciaux.');
    }

    /** Audit si table existe */
    private function auditer(string $action, Pointage $p)
    {
        if (Schema::hasTable('audit_logs') && class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log($action, 'pointage', $p->user->nomComplet() . ' — ' . $p->date->format('d/m/Y'));
        }
    }
}
