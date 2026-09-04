<?php
namespace App\Http\Controllers;

use App\Models\{Registre, RegistreEntree};
use Illuminate\Http\Request;

class RegistreController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $registres = Registre::withCount('entrees')->orderBy('libelle')->get();
        return view('registres.index', compact('registres'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'periodicite' => 'required|in:quotidien,hebdomadaire,mensuel',
            'colonnes' => 'required|string', // JSON string
        ]);

        $colonnes = json_decode($r->colonnes, true);
        if (!is_array($colonnes) || count($colonnes) === 0) {
            return back()->with('erreur', 'Définissez au moins une colonne.')->withInput();
        }

        Registre::create([
            'libelle' => $r->libelle,
            'description' => $r->description,
            'periodicite' => $r->periodicite,
            'definition_colonnes' => $colonnes,
        ]);

        return back()->with('succes', 'Registre créé.');
    }

    public function show(Request $r, Registre $registre)
    {
        abort_unless($r->user()->estPilote(), 403);
        $entrees = $registre->entrees()->with('user')->orderByDesc('date')->paginate(50);
        return view('registres.show', compact('registre', 'entrees'));
    }

    public function storeEntree(Request $r, Registre $registre)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'date' => 'required|date',
            'valeurs' => 'required|array',
        ]);

        RegistreEntree::create([
            'registre_id' => $registre->id,
            'user_id' => $r->user()->id,
            'date' => $r->date,
            'valeurs' => $r->valeurs,
        ]);

        return back()->with('succes', 'Entrée ajoutée.');
    }

    public function destroyEntree(Request $r, RegistreEntree $entree)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $entree->delete();
        return back()->with('succes', 'Entrée supprimée.');
    }

    public function destroy(Request $r, Registre $registre)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $registre->delete();
        return redirect()->route('registres.index')->with('succes', 'Registre supprimé.');
    }
}
