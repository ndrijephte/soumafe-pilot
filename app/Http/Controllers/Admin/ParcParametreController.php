<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, ParametreParc, TypeParc};
use App\Services\ExerciceParc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ParcParametreController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $parametres = Schema::hasTable('parametres_parc') ? ParametreParc::orderBy('groupe')->orderBy('cle')->get() : collect();
        $listes = [];
        if (Schema::hasTable('types_parc')) {
            foreach (TypeParc::LISTES as $cat => $def) {
                $listes[$cat] = [
                    'titre' => $def[0], 'aide' => $def[3],
                    'types' => TypeParc::where('categorie', $cat)->orderByDesc('actif')->orderBy('ordre')->get(),
                    'usages' => TypeParc::usages($cat),
                ];
            }
        }
        return view('admin.parc-parametres', compact('parametres', 'listes'));
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

    /* ===== EXERCICE MENSUEL ===== */
    public function updateExercice(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $v = $r->validate([
            'jour_debut' => 'required|integer|min:1|max:' . ExerciceParc::JOUR_MAX,
            'a_partir_de' => 'required|date_format:Y-m',
        ], [], ['jour_debut' => 'jour de début', 'a_partir_de' => "exercice d'application"]);

        $avant = ExerciceParc::jourDebut($v['a_partir_de']);
        ExerciceParc::definirRegle($v['a_partir_de'], (int) $v['jour_debut']);

        if (Schema::hasTable('audit_logs')) {
            AuditLog::log('modification', 'parc', 'Exercice mensuel', "Jour de début : {$avant} → {$v['jour_debut']} à partir de l'exercice {$v['a_partir_de']}");
        }

        return back()->with('succes', "Exercice mis à jour : à partir de " . ExerciceParc::libelle($v['a_partir_de']) . '.');
    }

    /* ===== LISTES DE CHOIX (types, motifs) ===== */
    public function storeType(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $data = $r->validate([
            'categorie' => 'required|in:' . implode(',', array_keys(TypeParc::LISTES)),
            'libelle' => 'required|string|min:2|max:80',
            'intervalle_jours' => 'nullable|integer|min:1|max:3650',
        ], [], ['libelle' => 'libellé', 'intervalle_jours' => 'intervalle']);
        $t = TypeParc::ajouter($data['categorie'], $data['libelle'], $data['intervalle_jours'] ?? null);
        AuditLog::log('creation', 'parametre_parc', TypeParc::LISTES[$t->categorie][0], $t->libelle);
        return redirect()->route('admin.parc-parametres.index', ['tab' => 'listes', 'liste' => $t->categorie])
            ->with('succes', "« {$t->libelle} » ajouté.");
    }

    /** Modification en place : libellé, intervalle, activation (réponse JSON). */
    public function updateType(Request $r, TypeParc $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $data = $r->validate([
            'libelle' => 'sometimes|required|string|min:2|max:80',
            'intervalle_jours' => 'sometimes|nullable|integer|min:1|max:3650',
            'actif' => 'sometimes|boolean',
        ], [], ['libelle' => 'libellé', 'intervalle_jours' => 'intervalle']);
        if (isset($data['libelle'])) {
            $data['libelle'] = trim(preg_replace('/\s+/u', ' ', $data['libelle']));
            $doublon = TypeParc::where('categorie', $type->categorie)->where('id', '!=', $type->id)->get()
                ->contains(fn($t) => mb_strtolower($t->libelle) === mb_strtolower($data['libelle']));
            if ($doublon) return response()->json(['message' => 'Ce libellé existe déjà dans la liste.'], 422);
        }
        $avant = $type->only(array_keys($data));
        $type->update($data);
        $chg = collect($data)->filter(fn($v, $k) => ($avant[$k] ?? null) != $v)
            ->map(fn($v, $k) => $k === 'actif' ? ($v ? 'activé' : 'désactivé') : "$k : " . ($avant[$k] ?? '—') . " → $v")->implode(', ');
        if ($chg) AuditLog::log('modification', 'parametre_parc', TypeParc::LISTES[$type->categorie][0] ?? $type->categorie, "{$type->libelle} ({$chg})");
        return response()->json(['ok' => true, 'type' => $type->only(['id', 'code', 'libelle', 'intervalle_jours', 'actif'])]);
    }

    public function toggleType(Request $r, TypeParc $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $type->update(['actif' => !$type->actif]);
        AuditLog::log('modification', 'parametre_parc', TypeParc::LISTES[$type->categorie][0] ?? $type->categorie, $type->libelle . ($type->actif ? ' activé' : ' désactivé'));
        return back()->with('succes', "« {$type->libelle} » " . ($type->actif ? 'activé' : 'désactivé') . '.');
    }

    /** Suppression : seulement si la modalité n'a jamais servi (sinon, la désactiver). */
    public function destroyType(Request $r, TypeParc $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $n = TypeParc::usages($type->categorie)[$type->code] ?? 0;
        if ($n) return back()->withErrors(['type' => "« {$type->libelle} » est utilisé {$n} fois : désactivez-le plutôt que de le supprimer."]);
        $type->delete();
        AuditLog::log('suppression', 'parametre_parc', TypeParc::LISTES[$type->categorie][0] ?? $type->categorie, $type->libelle . ($r->filled('motif_suppression') ? ', motif : ' . $r->motif_suppression : ''));
        return redirect()->route('admin.parc-parametres.index', ['tab' => 'listes', 'liste' => $type->categorie])
            ->with('succes', "« {$type->libelle} » supprimé.");
    }
}
