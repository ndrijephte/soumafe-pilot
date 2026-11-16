<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, HoraireTravail};
use App\Services\CalendrierTravail;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Horaires et jours de travail. Une règle s'applique à partir de sa date d'effet (aujourd'hui ou plus tard) :
 * les règles passées ne sont jamais modifiées, l'historique reste traçable et les statistiques passées stables.
 */
class HoraireTravailController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $regles = HoraireTravail::with('createur')->orderByDesc('date_effet')->get();
        $enVigueur = CalendrierTravail::regle();
        // Modifier une programmation : une règle à venir se modifie en place (date comprise) ;
        // la règle en vigueur ou passée sert de base à une nouvelle règle datée (l'historique n'est pas réécrit)
        $base = $r->filled('modifier') ? $regles->firstWhere('id', (int) $r->modifier) : null;
        return view('admin.horaires.index', [
            'regles' => $regles,
            'enVigueur' => $enVigueur,
            'modele' => $base ?? $enVigueur,
            'edition' => $base && $base->date_effet->isFuture() ? $base : null,
            'base' => $base,
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $v = $r->validate([
            'date_effet' => 'required|date|after_or_equal:today',
            'heure_arrivee' => 'required|date_format:H:i',
            'heure_depart' => 'required|date_format:H:i|after:heure_arrivee',
            'jours' => 'required|array|min:1',
            'jours.*' => 'integer|between:1,7',
            'motif' => 'required|string|min:5|max:255',
            'remplace_id' => 'nullable|integer',
            'par_jour' => 'nullable|array', 'par_jour.*.arrivee' => 'nullable|date_format:H:i', 'par_jour.*.depart' => 'nullable|date_format:H:i',
            'pause_debut' => 'nullable|date_format:H:i|required_with:pause_fin', 'pause_fin' => 'nullable|date_format:H:i|required_with:pause_debut|after:pause_debut',
            'tolerance_arrivee' => 'nullable|integer|between:0,120', 'tolerance_depart' => 'nullable|integer|between:0,120',
            'heure_controle_saisie' => 'required|date_format:H:i', 'heure_daily_auto' => 'required|date_format:H:i',
        ], [
            'pause_fin.after' => 'La fin de la pause doit suivre son début.',
            'pause_debut.required_with' => 'Indiquez le début et la fin de la pause (ou laissez les deux vides).',
            'pause_fin.required_with' => 'Indiquez le début et la fin de la pause (ou laissez les deux vides).',
            'date_effet.after_or_equal' => "La date d'effet ne peut pas être passée : l'historique des horaires n'est pas modifiable.",
            'heure_depart.after' => "L'heure de fin doit être postérieure à l'heure de début.",
            'jours.required' => 'Cochez au moins un jour travaillé.',
            'motif.required' => 'Le motif du changement est obligatoire (traçabilité).',
            'motif.min' => 'Le motif doit contenir au moins 5 caractères.',
        ]);

        $joursListe = collect($v['jours'])->map(fn($j) => (int) $j)->unique()->sort()->values();
        $jours = $joursListe->join(',');
        // Horaires propres à un jour travaillé : seulement s'ils diffèrent de l'horaire général
        $parJour = [];
        foreach ($joursListe as $j) {
            $h = $v['par_jour'][$j] ?? [];
            if (empty($h['arrivee']) && empty($h['depart'])) continue;
            $a = $h['arrivee'] ?: $v['heure_arrivee'];
            $d = $h['depart'] ?: $v['heure_depart'];
            if ($d <= $a) return back()->withInput()->with('erreur', HoraireTravail::JOURS[$j] . " : l'heure de fin doit être postérieure à l'heure de début.");
            if ($a !== $v['heure_arrivee'] || $d !== $v['heure_depart']) $parJour[$j] = ['arrivee' => $a, 'depart' => $d];
        }
        $champs = [
            'heure_arrivee' => $v['heure_arrivee'] . ':00', 'heure_depart' => $v['heure_depart'] . ':00', 'jours' => $jours,
            'horaires_jours' => $parJour ?: null,
            'pause_debut' => !empty($v['pause_debut']) ? $v['pause_debut'] . ':00' : null, 'pause_fin' => !empty($v['pause_fin']) ? $v['pause_fin'] . ':00' : null,
            'tolerance_arrivee' => (int) ($v['tolerance_arrivee'] ?? 0), 'tolerance_depart' => (int) ($v['tolerance_depart'] ?? 0),
            'heure_controle_saisie' => $v['heure_controle_saisie'] . ':00', 'heure_daily_auto' => $v['heure_daily_auto'] . ':00',
        ];
        // Programmation à venir modifiée : elle est remplacée (sa date d'effet peut changer)
        $remplacee = !empty($v['remplace_id']) ? HoraireTravail::find($v['remplace_id']) : null;
        if ($remplacee && !$remplacee->date_effet->isFuture()) {
            return back()->withInput()->with('erreur', "Une règle déjà entrée en vigueur ne se modifie pas : enregistrez une nouvelle règle à partir d'une date à venir.");
        }
        $existante = HoraireTravail::whereDate('date_effet', $v['date_effet'])->first();
        if ($existante && !$remplacee) {
            return back()->withInput()->with('erreur', 'Une programmation commence déjà le ' . Carbon::parse($v['date_effet'])->format('d/m/Y') . ' : utilisez « Modifier » sur sa ligne, ou choisissez une autre date.');
        }
        if ($existante && $remplacee && !$existante->is($remplacee)) {
            return back()->withInput()->with('erreur', 'Une autre programmation commence déjà le ' . Carbon::parse($v['date_effet'])->format('d/m/Y') . ' : modifiez-la directement ou choisissez une autre date.');
        }
        $precedente = HoraireTravail::whereDate('date_effet', '<', $v['date_effet'])->when($remplacee, fn($q) => $q->whereKeyNot($remplacee->id))->orderByDesc('date_effet')->first() ?? CalendrierTravail::regle(Carbon::parse($v['date_effet'])->subDay());
        if (!$existante && !$remplacee && $precedente->empreinte() === (new HoraireTravail($champs))->empreinte()) {
            return back()->with('erreur', 'Aucun changement : ces horaires sont déjà en vigueur à cette date.')->withInput();
        }

        $avant = $remplacee ?? $existante;
        $avantLibelle = $avant ? $avant->libelle() . ' (à partir du ' . $avant->date_effet->format('d/m/Y') . ')' : null;
        $regle = \Illuminate\Support\Facades\DB::transaction(function () use ($remplacee, $v, $champs, $r) {
            if ($remplacee && $remplacee->date_effet->toDateString() !== $v['date_effet']) $remplacee->delete();
            return HoraireTravail::updateOrCreate(['date_effet' => $v['date_effet']], $champs + ['motif' => $v['motif'], 'cree_par' => $r->user()->id]);
        });
        $existante = $avant;
        CalendrierTravail::vider();
        AuditLog::log($existante ? 'horaires_modifies' : 'horaires_definis', 'horaires',
            'À partir du ' . $regle->date_effet->format('d/m/Y'),
            ($avantLibelle ? 'Avant : ' . $avantLibelle . "\n" : '') . 'Règle : ' . $regle->libelle() . "\nMotif : " . $v['motif']);

        return redirect()->route('admin.horaires.index')
            ->with('succes', ($avant ? 'Programmation modifiée : ' : 'Horaires enregistrés : ') . $regle->libelle() . ', à partir du ' . $regle->date_effet->format('d/m/Y') . '.');
    }

    /** Seule une règle à venir peut être retirée. */
    public function destroy(Request $r, HoraireTravail $horaire)
    {
        abort_unless($r->user()->estAdmin(), 403);
        if (!$horaire->date_effet->isFuture()) {
            return back()->with('erreur', "Une règle déjà entrée en vigueur fait partie de l'historique : elle ne peut pas être supprimée.");
        }
        AuditLog::log('horaires_annules', 'horaires', 'À partir du ' . $horaire->date_effet->format('d/m/Y'), 'Règle annulée : ' . $horaire->libelle());
        $horaire->delete();
        CalendrierTravail::vider();
        return back()->with('succes', 'Règle à venir annulée.');
    }
}
