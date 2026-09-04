<?php

namespace App\Http\Controllers;

use App\Models\Pointage;
use App\Models\User;
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

        // Stats du mois
        $debutMois = $date->copy()->startOfMonth();
        $finMois   = $date->copy()->endOfMonth();
        $moisPointages = Pointage::whereBetween('date', [$debutMois, $finMois])->get();
        $joursOuvres   = 0;
        $d = $debutMois->copy();
        while ($d->lte(min($date, $finMois))) {
            if (!$d->isWeekend()) $joursOuvres++;
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

        return view('pointage.index', compact('users', 'pointages', 'date', 'stats'));
    }

    /** Auto-pointage arrivée */
    public function pointerArrivee(Request $r)
    {
        $user = auth()->user();
        $today = today()->toDateString();
        $now   = now()->format('H:i:s');

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
        ]);

        $pointage->heure_arrivee    = $r->heure_arrivee ? $r->heure_arrivee . ':00' : $pointage->heure_arrivee;
        $pointage->heure_depart     = $r->heure_depart ? $r->heure_depart . ':00' : $pointage->heure_depart;
        $pointage->corrige_par      = auth()->id();
        $pointage->motif_correction = $r->motif_correction;
        $pointage->statut           = $pointage->calculerStatut();
        $pointage->save();
        $this->auditer('corriger_pointage', $pointage);

        return redirect()->route('pointage.index', ['date' => $pointage->date->toDateString()])
            ->with('succes', 'Pointage corrigé.');
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

    /** Audit si table existe */
    private function auditer(string $action, Pointage $p)
    {
        if (Schema::hasTable('audit_logs') && class_exists(\App\Models\AuditLog::class)) {
            \App\Models\AuditLog::log($action, 'pointage', $p->user->nomComplet() . ' — ' . $p->date->format('d/m/Y'));
        }
    }
}
