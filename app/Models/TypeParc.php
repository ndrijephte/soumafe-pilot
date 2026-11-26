<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TypeParc extends Model
{
    protected $table = 'types_parc';
    protected $fillable = ['categorie', 'code', 'libelle', 'actif', 'ordre', 'intervalle_jours'];
    protected $casts = ['actif' => 'boolean'];

    public static function penalites()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'penalite')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function maintenances()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'maintenance')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function motifsAbsence()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'motif_absence')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function depenses()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'depense')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function actionsPlanning()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'action_planning')->where('actif', true)->orderBy('ordre')->get();
    }

    public static function autresRessources()
    {
        if (!Schema::hasTable('types_parc')) return collect();
        return static::where('categorie', 'autre_ressource')->where('actif', true)->orderBy('ordre')->get();
    }

    /**
     * Listes de choix du parc gérées dans le paramétrage : libellé, usage (table et colonne qui stockent le code),
     * aide affichée, et si la liste porte un intervalle (jours entre deux entretiens).
     */
    public const LISTES = [
        'penalite'        => ["Types de pénalité", 'penalites', 'type', "Proposés à la déclaration d'une pénalité.", "un type de pénalité"],
        'maintenance'     => ["Types d'entretien", 'maintenances_preventives', 'type_echeance', "L'intervalle (en jours) sert à calculer la prochaine échéance.", "un type d'entretien"],
        'motif_absence'   => ["Motifs d'absence de recette", 'recettes_vehicules', 'motif_absence', "Obligatoires quand un chauffeur ne verse pas de recette.", "un motif d'absence"],
        'depense'         => ["Types de dépense", 'depenses', 'type_depense', "Pour classer les dépenses (salaires, réparations, factures…).", "un type de dépense"],
        'autre_ressource' => ["Types de ressource", 'autres_ressources', 'type_ressource', "Pour classer les autres entrées d'argent.", "un type de ressource"],
    ];


    /**
     * Règle de validation : le code doit exister dans la liste (actif ou non, pour pouvoir modifier un ancien enregistrement).
     * Une valeur absente s'ajoute d'abord par la saisie manuelle « + Ajouter … ». Liste vide : pas de contrôle.
     */
    public static function regle(string $categorie): \Illuminate\Contracts\Validation\ValidationRule|\Illuminate\Validation\Rules\Exists|string
    {
        if (!Schema::hasTable('types_parc') || !static::where('categorie', $categorie)->exists()) return 'string';
        return \Illuminate\Validation\Rule::exists('types_parc', 'code')->where('categorie', $categorie);
    }

    /** Nombre d'enregistrements qui utilisent chaque code d'une liste : [code => n]. */
    public static function usages(string $categorie): array
    {
        [, $table, $col] = self::LISTES[$categorie] ?? [null, null, null];
        if (!$table || !Schema::hasTable($table) || !Schema::hasColumn($table, $col)) return [];
        return \Illuminate\Support\Facades\DB::table($table)->whereNotNull($col)->groupBy($col)
            ->selectRaw("$col as code, count(*) as n")->pluck('n', 'code')->map(fn($n) => (int) $n)->all();
    }

    /**
     * Ajoute une modalité à une liste (paramétrage ou saisie à la volée). Le code est tiré du libellé.
     * Si le libellé existe déjà dans la liste, la modalité existante est réactivée et renvoyée.
     */
    public static function ajouter(string $categorie, string $libelle, ?int $intervalle = null): self
    {
        $libelle = trim(preg_replace('/\s+/u', ' ', $libelle));
        $existant = static::where('categorie', $categorie)->get()
            ->first(fn($t) => mb_strtolower($t->libelle) === mb_strtolower($libelle));
        if ($existant) {
            $existant->update(array_filter(['actif' => true, 'intervalle_jours' => $intervalle], fn($v) => $v !== null));
            return $existant;
        }
        $base = mb_substr(\Illuminate\Support\Str::slug($libelle, '_'), 0, 45) ?: 'modalite';
        $code = $base;
        for ($i = 2; static::where('categorie', $categorie)->where('code', $code)->exists(); $i++) $code = $base . '_' . $i;
        return static::create([
            'categorie' => $categorie, 'code' => $code, 'libelle' => $libelle, 'actif' => true,
            'ordre' => (static::where('categorie', $categorie)->max('ordre') ?? 0) + 1,
            'intervalle_jours' => $categorie === 'maintenance' ? $intervalle : null,
        ]);
    }

    /** Libellé lisible d'un motif d'absence / d'écart enregistré sur une recette (« vehicule_garage » → « Véhicule au garage »). */
    public static function libelleMotif(?string $code): string
    {
        static $libelles = null;
        if ($code === null || $code === '') return '';
        $libelles ??= Schema::hasTable('types_parc')
            ? static::where('categorie', 'motif_absence')->pluck('libelle', 'code')->all() : [];
        $connus = ['sous_objectif' => 'Objectif non atteint', 'jour_repos' => 'Jour de repos'];
        return $libelles[$code] ?? $connus[$code] ?? ucfirst(str_replace('_', ' ', $code));
    }
}
