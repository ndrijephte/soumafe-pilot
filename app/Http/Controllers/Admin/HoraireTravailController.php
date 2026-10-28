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
        return view('admin.horaires.index', [
            'regles' => $regles,
            'enVigueur' => CalendrierTravail::regle(),
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
        ], [
            'date_effet.after_or_equal' => "La date d'effet ne peut pas être passée : l'historique des horaires n'est pas modifiable.",
            'heure_depart.after' => "L'heure de fin doit être postérieure à l'heure de début.",
            'jours.required' => 'Cochez au moins un jour travaillé.',
            'motif.required' => 'Le motif du changement est obligatoire (traçabilité).',
            'motif.min' => 'Le motif doit contenir au moins 5 caractères.',
        ]);

        $jours = collect($v['jours'])->map(fn($j) => (int) $j)->unique()->sort()->join(',');
        $precedente = CalendrierTravail::regle(Carbon::parse($v['date_effet'])->subDay());
        $existante = HoraireTravail::whereDate('date_effet', $v['date_effet'])->first();
        if (!$existante && $precedente->jours === $jours && $precedente->arrivee() === $v['heure_arrivee'] && $precedente->depart() === $v['heure_depart']) {
            return back()->with('erreur', 'Aucun changement : ces horaires sont déjà en vigueur à cette date.')->withInput();
        }

        $regle = HoraireTravail::updateOrCreate(['date_effet' => $v['date_effet']], [
            'heure_arrivee' => $v['heure_arrivee'] . ':00', 'heure_depart' => $v['heure_depart'] . ':00',
            'jours' => $jours, 'motif' => $v['motif'], 'cree_par' => $r->user()->id,
        ]);
        CalendrierTravail::vider();
        AuditLog::log($existante ? 'horaires_modifies' : 'horaires_definis', 'horaires',
            'À partir du ' . $regle->date_effet->format('d/m/Y'),
            ($existante ? 'Avant : ' . $existante->libelle() . "\n" : '') . 'Règle : ' . $regle->libelle() . "\nMotif : " . $v['motif']);

        return redirect()->route('admin.horaires.index')
            ->with('succes', 'Horaires enregistrés : ' . $regle->libelle() . ', à partir du ' . $regle->date_effet->format('d/m/Y') . '.');
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
