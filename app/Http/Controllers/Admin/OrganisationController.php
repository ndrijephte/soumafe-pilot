<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Domaine, Pole, Service};
use Illuminate\Http\Request;

/**
 * Structure de l'organisation sur un seul écran : domaines → services → pôles (→ collaborateurs).
 * Renommer et rattacher se font sur place ; une branche qui contient encore quelque chose ne se supprime pas.
 */
class OrganisationController extends Controller
{
    /** niveau => [modèle, colonne du parent, niveau parent, libellé] */
    private const NIVEAUX = [
        'domaine' => [Domaine::class, null, null, 'Domaine'],
        'service' => [Service::class, 'domaine_id', 'domaine', 'Service'],
        'pole'    => [Pole::class, 'service_id', 'service', 'Pôle'],
    ];

    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $domaines = Domaine::orderBy('libelle')->get();
        $services = Service::orderBy('libelle')->get();
        $poles = Pole::withCount('users')->orderBy('libelle')->get();
        return view('admin.organisation', compact('domaines', 'services', 'poles'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $v = $r->validate([
            'niveau' => 'required|in:' . implode(',', array_keys(self::NIVEAUX)),
            'libelle' => 'required|string|min:2|max:100',
            'parent_id' => 'nullable|integer',
        ], [], ['libelle' => 'libellé']);
        [$modele, $colParent, $niveauParent, $nom] = self::NIVEAUX[$v['niveau']];
        $libelle = trim(preg_replace('/\s+/u', ' ', $v['libelle']));
        if ($modele::whereRaw('LOWER(libelle) = ?', [mb_strtolower($libelle)])->exists()) {
            return back()->withErrors(['libelle' => "{$nom} « {$libelle} » existe déjà."])->withInput();
        }
        $donnees = ['libelle' => $libelle];
        if ($colParent && !empty($v['parent_id'])) {
            abort_unless(self::NIVEAUX[$niveauParent][0]::whereKey($v['parent_id'])->exists(), 422);
            $donnees[$colParent] = $v['parent_id'];
        }
        $item = $modele::create($donnees);
        AuditLog::log('creation', 'parametres', 'Organisation', "{$nom} : {$libelle}");
        return redirect()->route('admin.organisation.index', ['ouvert' => $v['niveau'] . '-' . $item->id])->with('succes', "{$nom} « {$libelle} » ajouté.");
    }

    /** Sur place (JSON) : renommer, ou changer de rattachement. */
    public function update(Request $r, string $niveau, int $id)
    {
        abort_unless($r->user()->estAdmin(), 403);
        abort_unless(isset(self::NIVEAUX[$niveau]), 404);
        [$modele, $colParent, $niveauParent, $nom] = self::NIVEAUX[$niveau];
        $item = $modele::findOrFail($id);
        $v = $r->validate(['libelle' => 'sometimes|required|string|min:2|max:100', 'parent_id' => 'sometimes|nullable|integer'], [], ['libelle' => 'libellé']);
        $chg = [];
        if (isset($v['libelle'])) {
            $libelle = trim(preg_replace('/\s+/u', ' ', $v['libelle']));
            if ($modele::where('id', '!=', $id)->whereRaw('LOWER(libelle) = ?', [mb_strtolower($libelle)])->exists()) {
                return response()->json(['message' => "{$nom} « {$libelle} » existe déjà."], 422);
            }
            if ($libelle !== $item->libelle) $chg[] = "renommé : {$item->libelle} → {$libelle}";
            $item->libelle = $libelle;
        }
        if ($colParent && array_key_exists('parent_id', $v)) {
            $p = $v['parent_id'] ?: null;
            if ($p && !self::NIVEAUX[$niveauParent][0]::whereKey($p)->exists()) return response()->json(['message' => 'Rattachement introuvable.'], 422);
            if ((int) $item->{$colParent} !== (int) $p) {
                $parent = $p ? self::NIVEAUX[$niveauParent][0]::find($p)?->libelle : 'aucun';
                $chg[] = "rattaché à : {$parent}";
            }
            $item->{$colParent} = $p;
        }
        $item->save();
        if ($chg) AuditLog::log('modification', 'parametres', 'Organisation', "{$nom} {$item->libelle} (" . implode(', ', $chg) . ')');
        return response()->json(['ok' => true]);
    }

    /** Suppression : seulement une branche vide (sans service, sans pôle, sans collaborateur). */
    public function destroy(Request $r, string $niveau, int $id)
    {
        abort_unless($r->user()->estAdmin(), 403);
        abort_unless(isset(self::NIVEAUX[$niveau]), 404);
        [$modele, , , $nom] = self::NIVEAUX[$niveau];
        $item = $modele::findOrFail($id);
        $plein = match ($niveau) {
            'domaine' => $item->services()->exists() ? 'des services y sont rattachés' : null,
            'service' => $item->poles()->exists() ? 'des pôles y sont rattachés' : null,
            'pole' => $item->users()->exists() ? 'des collaborateurs y sont rattachés' : null,
        };
        if ($plein) return back()->withErrors(['organisation' => "Impossible de supprimer « {$item->libelle} » : {$plein}."]);
        $item->delete();
        AuditLog::log('suppression', 'parametres', 'Organisation', "{$nom} : {$item->libelle}");
        return redirect()->route('admin.organisation.index')->with('succes', "{$nom} « {$item->libelle} » supprimé.");
    }
}
