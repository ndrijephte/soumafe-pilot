<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

/** User story du Product Backlog d'un projet. */
class UserStory extends Model
{
    protected $table = 'user_stories';
    protected $fillable = ['projet_id', 'numero', 'module', 'intitule', 'exigences', 'taches_techniques', 'priorite', 'statut', 'effort_estime', 'origine'];

    public const STATUTS = ['a_faire' => 'À faire', 'planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée'];
    public const STATUTS_SPRINT = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'termine' => 'Terminée'];

    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
    public function sprints(): BelongsToMany
    {
        return $this->belongsToMany(Sprint::class, 'sprint_user_stories')->withPivot('statut_sprint')->withTimestamps();
    }

    public const ORIGINES = ['manuel' => 'Saisie', 'import' => 'Import Excel', 'review' => 'Sprint Review', 'retrospective' => 'Rétrospective'];

    /**
     * Prochain numéro libre : dans un module existant « US a.(b+1) » ; pour un nouveau module « US (a+1).1 » ;
     * sans module, à la suite du dernier numéro du backlog.
     */
    public static function prochainNumero(Projet $projet, ?string $module = null): string
    {
        $stories = $projet->userStories()->get(['numero', 'module']);
        $num = fn($n) => preg_match('/(\d+)(?:\.(\d+))?\s*$/', $n, $m) ? [(int) $m[1], (int) ($m[2] ?? 0)] : null;
        $tous = $stories->pluck('numero')->map($num)->filter()->sort()->values();
        if ($module !== null && trim($module) !== '') {
            $duModule = $stories->filter(fn($s) => mb_strtolower(trim((string) $s->module)) === mb_strtolower(trim($module)))->pluck('numero')->map($num)->filter()->sort()->values();
            $max = $duModule->last();
            $numero = $max ? 'US ' . $max[0] . '.' . ($max[1] + 1) : 'US ' . (($tous->max(fn($n) => $n[0]) ?? 0) + 1) . '.1';
        } else {
            $max = $tous->last();
            $numero = $max ? 'US ' . $max[0] . '.' . ($max[1] + 1) : 'US 1.1';
        }
        // Garantit l'unicité (numéros saisis à la main)
        $existants = $stories->pluck('numero')->map(fn($n) => mb_strtolower($n))->all();
        while (in_array(mb_strtolower($numero), $existants, true)) {
            $numero = preg_replace_callback('/(\d+)$/', fn($m) => $m[1] + 1, $numero);
        }
        return $numero;
    }

    /** Échelle des story points (complexité estimée de la user story). */
    public const POINTS = [1, 2, 3, 5, 8, 13];
    public const COMPLEXITES = [
        'simple' => ['libelle' => 'Très simple', 'points' => '1-2', 'aide' => 'Story claire, sans dépendance, réalisable en moins d’1 jour'],
        'moderee' => ['libelle' => 'Modérée', 'points' => '3-5', 'aide' => 'Quelques inconnus, 1 à 3 jours de travail'],
        'complexe' => ['libelle' => 'Complexe', 'points' => '8-13', 'aide' => 'Dépendance technique, à décomposer si possible'],
    ];

    /** Complexité déduite des story points (null si non estimée). */
    public static function complexite(?int $points): ?array
    {
        if (!$points) return null;
        $cle = $points <= 2 ? 'simple' : ($points <= 5 ? 'moderee' : 'complexe');
        return self::COMPLEXITES[$cle] + ['cle' => $cle];
    }

    /** Ramène une valeur saisie / importée sur l'échelle (valeur supérieure la plus proche). */
    public static function normaliserPoints($v): ?int
    {
        if (!is_numeric($v) || (float) $v <= 0) return null;
        foreach (self::POINTS as $p) if ((float) $v <= $p) return $p;
        return 13;
    }

    public function complexiteStory(): ?array { return self::complexite($this->effort_estime); }

    /**
     * Statut piloté par les tâches : toutes terminées → terminée ; au moins une
     * démarrée → en cours. On ne fait que progresser (un statut posé à la main
     * plus avancé n'est pas rétrogradé).
     */
    public function synchroniserAvecTaches(?int $sprintId = null): void
    {
        $taches = $this->taches()->get(['id', 'statut', 'sprint_id']);
        if ($taches->isEmpty()) return;
        $ordre = ['a_faire' => 0, 'planifiee' => 1, 'en_cours' => 2, 'terminee' => 3];
        $toutes = $taches->every(fn($t) => $t->statut === 'termine');
        $demarre = $taches->contains(fn($t) => in_array($t->statut, ['en_cours', 'termine', 'bloque']));
        $cible = $toutes ? 'terminee' : ($demarre ? 'en_cours' : null);
        if ($cible && $ordre[$cible] > ($ordre[$this->statut] ?? 0)) $this->update(['statut' => $cible]);

        // Avancement dans le(s) sprint(s) concerné(s)
        foreach ($this->sprints()->when($sprintId, fn($q) => $q->whereKey($sprintId))->get() as $sp) {
            $ts = $taches->where('sprint_id', $sp->id);
            if ($ts->isEmpty()) continue;
            $c = $ts->every(fn($t) => $t->statut === 'termine') ? 'termine' : ($ts->contains(fn($t) => in_array($t->statut, ['en_cours', 'termine', 'bloque'])) ? 'en_cours' : null);
            $o = ['a_faire' => 0, 'en_cours' => 1, 'termine' => 2];
            if ($c && $o[$c] > ($o[$sp->pivot->statut_sprint] ?? 0)) $this->sprints()->updateExistingPivot($sp->id, ['statut_sprint' => $c]);
        }
    }

    public function statutLibelle(): string { return self::STATUTS[$this->statut] ?? ucfirst($this->statut); }

    /**
     * Modules dans l'ordre où ils sont apparus dans le backlog (ordre du fichier importé),
     * puis tri naturel des numéros (« US 1.2 » avant « US 1.10 »).
     */
    public static function trierParNumero($collection)
    {
        $ordreModule = [];
        foreach ($collection as $us) {
            $m = mb_strtolower($us->module ?? '');
            $ordreModule[$m] = min($ordreModule[$m] ?? PHP_INT_MAX, $us->id ?? PHP_INT_MAX);
        }
        return $collection->sortBy(fn($us) => [
            $ordreModule[mb_strtolower($us->module ?? '')],
            preg_replace_callback('/\d+/', fn($m) => str_pad($m[0], 6, '0', STR_PAD_LEFT), $us->numero),
        ]);
    }
}
