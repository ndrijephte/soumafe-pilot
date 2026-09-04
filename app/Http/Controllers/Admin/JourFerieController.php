<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JourFerie;
use Illuminate\Http\Request;

class JourFerieController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $annee = $r->get('annee', now()->year);
        return view('admin.feries.index', [
            'feries' => JourFerie::where('annee', $annee)->orderBy('date')->get(),
            'annee' => $annee,
            'annees' => JourFerie::selectRaw('DISTINCT annee')->orderBy('annee', 'desc')->pluck('annee'),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'date' => 'required|date|unique:jours_feries',
            'libelle' => 'required|string|max:100',
        ]);
        JourFerie::create([
            'date' => $r->date,
            'libelle' => $r->libelle,
            'annee' => \Carbon\Carbon::parse($r->date)->year,
        ]);
        return back()->with('succes', 'Jour férié ajouté.');
    }

    public function update(Request $r, JourFerie $ferie)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'date' => 'required|date|unique:jours_feries,date,' . $ferie->id,
            'libelle' => 'required|string|max:100',
        ]);
        $ferie->update([
            'date' => $r->date,
            'libelle' => $r->libelle,
            'annee' => \Carbon\Carbon::parse($r->date)->year,
        ]);
        return back()->with('succes', 'Jour férié mis à jour.');
    }

    public function destroy(Request $r, JourFerie $ferie)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $ferie->delete();
        return back()->with('succes', 'Jour férié supprimé.');
    }
}
