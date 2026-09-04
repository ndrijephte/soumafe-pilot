<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ParametreParc, TypeParc};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ParcParametreController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $parametres = collect();
        if (Schema::hasTable('parametres_parc')) {
            $parametres = ParametreParc::orderBy('groupe')->orderBy('cle')->get();
        }
        $typesPenalite = Schema::hasTable('types_parc')
            ? TypeParc::where('categorie', 'penalite')->orderBy('ordre')->get()
            : collect();
        $typesMaintenance = Schema::hasTable('types_parc')
            ? TypeParc::where('categorie', 'maintenance')->orderBy('ordre')->get()
            : collect();
        $motifsAbsence = Schema::hasTable('types_parc')
            ? TypeParc::where('categorie', 'motif_absence')->orderBy('ordre')->get()
            : collect();
        $typesDepense = Schema::hasTable('types_parc')
            ? TypeParc::where('categorie', 'depense')->orderBy('ordre')->get()
            : collect();

        return view('admin.parc-parametres', compact('parametres', 'typesPenalite', 'typesMaintenance', 'motifsAbsence', 'typesDepense'));
    }

    public function update(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $params = $r->input('params', []);
        foreach ($params as $id => $valeur) {
            ParametreParc::where('id', $id)->update(['valeur' => $valeur]);
        }
        return back()->with('succes', 'Paramètres du parc enregistrés.');
    }

    /* ===== TYPES PARC CRUD ===== */
    public function storeType(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $data = $r->validate([
            'categorie' => 'required|in:penalite,maintenance,motif_absence,depense',
            'code' => 'required|string|max:50',
            'libelle' => 'required|string|max:80',
        ]);
        $maxOrdre = TypeParc::where('categorie', $data['categorie'])->max('ordre') ?? 0;
        TypeParc::create(array_merge($data, ['actif' => true, 'ordre' => $maxOrdre + 1]));
        return back()->with('succes', 'Type ajouté.');
    }

    public function toggleType(Request $r, TypeParc $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $type->update(['actif' => !$type->actif]);
        return back()->with('succes', 'Type ' . ($type->actif ? 'activé' : 'désactivé') . '.');
    }

    public function destroyType(Request $r, TypeParc $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $type->delete();
        return back()->with('succes', 'Type supprimé.');
    }
}
