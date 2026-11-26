<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, ProfilMesure, Role, TitrePoste, TypeAbsence, TypeBlocage, TypeEvenement};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;

/**
 * Listes de choix du système (hors parc) réunies sur un seul écran, sur le modèle du paramétrage du parc :
 * modification du libellé sur place, nombre d'utilisations, suppression interdite tant qu'une valeur sert.
 */
class ListeChoixController extends Controller
{
    /**
     * modele, titre, aide, table et colonne d'usage, clé stockée dans les données (code ou libellé),
     * groupe de l'Administration où la liste s'affiche, unité de comptage (« 3 comptes », « 2 fois »).
     */
    public const LISTES = [
        'type_evenement' => [TypeEvenement::class, "Types d'événement", "Natures proposées au signalement d'un événement. La couleur distingue le type dans les listes et le reporting.", 'evenements', 'type', 'code', 'listes', null],
        'type_absence'   => [TypeAbsence::class, "Natures d'absence", "Proposées à la déclaration d'une absence (congé, mission…).", 'absences', 'type', 'code', 'listes', null],
        'type_blocage'   => [TypeBlocage::class, 'Types de blocage', "Proposés au signalement d'un blocage sur une tâche.", 'blocages', 'type', 'code', 'listes', null],
        'titre_poste'    => [TitrePoste::class, 'Titres de poste', 'Fonctions proposées sur la fiche d’un compte. Renommer un titre met à jour les comptes qui le portent.', 'users', 'titre_poste', 'libelle', 'listes', 'compte'],
        'role'           => [Role::class, 'Rôles', "Le rôle fixe les droits d'un compte. Les rôles système portent les droits du logiciel (administration, pilotage, direction) : renommables, non supprimables.", 'users', 'role', 'code', 'acces', 'compte'],
        'profil_mesure'  => [ProfilMesure::class, 'Profils de mesure', "Le profil détermine les indicateurs suivis pour un collaborateur (développeur, opérationnel, support…).", 'users', 'profil_mesure', 'code', 'acces', 'compte'],
    ];
    public const GROUPES = [
        'listes' => ['Listes de choix', 'Valeurs proposées dans les formulaires.'],
        'acces'  => ['Rôles et profils', 'Rôles (droits) et profils de mesure (indicateurs) attribués aux comptes.'],
    ];

    /** Couleurs proposées pour les types d'événement : [fond, texte, nom]. */
    public const COULEURS = [
        'rouge' => ['#fee2e2', '#b91c1c', 'Rouge'], 'orange' => ['#fef3c7', '#b45309', 'Orange'], 'vert' => ['#dcfce7', '#15803d', 'Vert'],
        'bleu' => ['#dbeafe', '#1d4ed8', 'Bleu'], 'violet' => ['#ede9fe', '#6d28d9', 'Violet'], 'gris' => ['#e2e8f0', '#475569', 'Gris'],
    ];

    public function index(Request $r, string $groupe = 'listes')
    {
        abort_unless($r->user()->estAdmin(), 403);
        $listes = [];
        foreach (self::LISTES as $cle => [$modele, $titre, $aide, $table, $col, $cleData, $grp, $unite]) {
            if ($grp !== $groupe || !Schema::hasTable((new $modele)->getTable())) continue;
            $listes[$cle] = [
                'titre' => $titre, 'aide' => $aide, 'cleData' => $cleData, 'unite' => $unite,
                'items' => $modele::orderBy('libelle')->get(),
                'usages' => self::usages($cle),
            ];
        }
        return view('admin.listes', ['listes' => $listes, 'couleurs' => self::COULEURS, 'groupe' => $groupe, 'infoGroupe' => self::GROUPES[$groupe]]);
    }

    public function acces(Request $r)
    {
        return $this->index($r, 'acces');
    }

    /** Nombre d'enregistrements qui utilisent chaque valeur : [code ou libellé => n]. */
    public static function usages(string $cle): array
    {
        [, , , $table, $col] = self::LISTES[$cle];
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $col)) return [];
        return DB::table($table)->whereNotNull($col)->groupBy($col)->selectRaw("$col as v, count(*) as n")
            ->pluck('n', 'v')->map(fn($n) => (int) $n)->all();
    }

    /** Création (paramétrage ou saisie manuelle depuis un formulaire). Un libellé existant est réutilisé. */
    public static function creer(string $cle, string $libelle, array $extra = [])
    {
        [$modele, $titre] = self::LISTES[$cle];
        $libelle = trim(preg_replace('/\s+/u', ' ', $libelle));
        $existant = $modele::all()->first(fn($x) => mb_strtolower($x->libelle) === mb_strtolower($libelle));
        if ($existant) return $existant;
        $donnees = ['libelle' => $libelle];
        if (Schema::hasColumn((new $modele)->getTable(), 'code')) {
            $base = mb_substr(Str::slug($libelle, '_'), 0, 45) ?: 'valeur';
            $code = $base;
            for ($i = 2; $modele::where('code', $code)->exists(); $i++) $code = $base . '_' . $i;
            $donnees += ['code' => $code, 'systeme' => false];
        }
        $t = $modele::create($donnees + $extra);
        AuditLog::log('creation', 'parametres', $titre, $libelle);
        return $t;
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $v = $r->validate([
            'liste' => 'required|in:' . implode(',', array_keys(self::LISTES)),
            'libelle' => 'required|string|min:2|max:80',
            'couleur' => 'nullable|in:' . implode(',', array_keys(self::COULEURS)),
        ], [], ['libelle' => 'libellé']);
        $extra = [];
        if ($v['liste'] === 'type_evenement') {
            [$fond, $texte] = self::COULEURS[$v['couleur'] ?? 'gris'];
            $extra = ['couleur_fond' => $fond, 'couleur_texte' => $texte];
        }
        $t = self::creer($v['liste'], $v['libelle'], $extra);
        return redirect()->to(self::ecran($v['liste']))->with('succes', "« {$t->libelle} » ajouté.");
    }

    /** Modification sur place (JSON) : libellé, couleur. */
    public function update(Request $r, string $liste, int $id)
    {
        abort_unless($r->user()->estAdmin(), 403);
        abort_unless(isset(self::LISTES[$liste]), 404);
        [$modele, $titre, , $table, $col, $cleData] = self::LISTES[$liste];
        $item = $modele::findOrFail($id);
        $v = $r->validate([
            'libelle' => 'sometimes|required|string|min:2|max:80',
            'couleur' => 'sometimes|in:' . implode(',', array_keys(self::COULEURS)),
        ], [], ['libelle' => 'libellé']);

        $chg = [];
        if (isset($v['libelle'])) {
            $nouveau = trim(preg_replace('/\s+/u', ' ', $v['libelle']));
            $doublon = $modele::where('id', '!=', $item->id)->get()->contains(fn($x) => mb_strtolower($x->libelle) === mb_strtolower($nouveau));
            if ($doublon) return response()->json(['message' => 'Ce libellé existe déjà dans la liste.'], 422);
            $ancien = $item->libelle;
            if ($ancien !== $nouveau) {
                DB::transaction(function () use ($item, $nouveau, $ancien, $cleData, $table, $col) {
                    $item->update(['libelle' => $nouveau]);
                    // Liste stockée par libellé (titres de poste) : les données suivent le nouveau nom
                    if ($cleData === 'libelle') DB::table($table)->where($col, $ancien)->update([$col => $nouveau]);
                });
                $chg[] = "libellé : {$ancien} → {$nouveau}";
            }
        }
        if (isset($v['couleur']) && $liste === 'type_evenement') {
            [$fond, $texte] = self::COULEURS[$v['couleur']];
            $item->update(['couleur_fond' => $fond, 'couleur_texte' => $texte]);
            $chg[] = 'couleur : ' . self::COULEURS[$v['couleur']][2];
        }
        if ($chg) AuditLog::log('modification', 'parametres', $titre, $item->libelle . ' (' . implode(', ', $chg) . ')');
        return response()->json(['ok' => true, 'libelle' => $item->libelle]);
    }

    /** Suppression : refusée pour une valeur système ou déjà utilisée. */
    public function destroy(Request $r, string $liste, int $id)
    {
        abort_unless($r->user()->estAdmin(), 403);
        abort_unless(isset(self::LISTES[$liste]), 404);
        [$modele, $titre, , , , $cleData] = self::LISTES[$liste];
        $item = $modele::findOrFail($id);
        if (!empty($item->systeme)) return back()->withErrors(['liste' => "« {$item->libelle} » est une valeur système : elle ne peut pas être supprimée."]);
        $n = self::usages($liste)[$item->{$cleData}] ?? 0;
        if ($n) return back()->withErrors(['liste' => "« {$item->libelle} » est utilisé {$n} fois : renommez-le plutôt que de le supprimer."]);
        $item->delete();
        AuditLog::log('suppression', 'parametres', $titre, $item->libelle);
        return redirect()->to(self::ecran($liste))->with('succes', "« {$item->libelle} » supprimé.");
    }

    /** Adresse de l'écran qui affiche une liste. */
    public static function ecran(string $liste): string
    {
        return self::LISTES[$liste][6] === 'acces' ? route('admin.acces.index', ['liste' => $liste]) : route('admin.listes.index', ['liste' => $liste]);
    }
}
