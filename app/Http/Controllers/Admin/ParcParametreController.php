<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParametreParc;
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
        return view('admin.parc-parametres', compact('parametres'));
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
}
