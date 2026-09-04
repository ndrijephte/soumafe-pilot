<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Domaine, Service};
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.services.index', [
            'services' => Service::with('domaine')->withCount('poles')->orderBy('libelle')->get(),
            'domaines' => Domaine::orderBy('libelle')->get(),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'libelle' => 'required|string|max:100|unique:services',
            'domaine_id' => 'nullable|exists:domaines,id',
        ]);
        Service::create($r->only(['libelle', 'domaine_id']));
        return back()->with('succes', 'Service ajouté.');
    }

    public function update(Request $r, Service $service)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'libelle' => 'required|string|max:100|unique:services,libelle,' . $service->id,
            'domaine_id' => 'nullable|exists:domaines,id',
        ]);
        $service->update($r->only(['libelle', 'domaine_id']));
        return back()->with('succes', 'Service mis à jour.');
    }

    public function destroy(Request $r, Service $service)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        if ($service->poles()->exists()) return back()->with('erreur', 'Impossible : des pôles sont rattachés à ce service.');
        $service->delete();
        return back()->with('succes', 'Service supprimé.');
    }
}
