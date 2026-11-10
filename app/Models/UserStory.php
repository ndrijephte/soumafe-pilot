<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

/** User story du Product Backlog d'un projet. */
class UserStory extends Model
{
    protected $table = 'user_stories';
    protected $fillable = ['projet_id', 'numero', 'module', 'intitule', 'exigences', 'taches_techniques', 'priorite', 'statut', 'effort_estime', 'origine', 'criteres_acceptation', 'priorite_moscow', 'points_effort', 'epic', 'profil', 'dor', 'source_cr_id', 'avancement', 'avancement_note', 'avancement_par', 'avancement_le'];
    protected $casts = ['criteres_acceptation' => 'array', 'avancement_le' => 'datetime', 'dor' => 'boolean'];

    public const STATUTS = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'en_revue' => 'En revue', 'terminee' => 'Terminée', 'acceptee' => 'Acceptée', 'bloquee' => 'Bloquée'];
    /** Stories achevées : terminées, et acceptées par le Product Owner à la Review. */
    public const FINIES = ['terminee', 'acceptee'];
    public const STATUTS_SPRINT = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'termine' => 'Terminée'];

    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
    public function sprints(): BelongsToMany
    {
        return $this->belongsToMany(Sprint::class, 'sprint_user_stories')->withPivot('statut_sprint')->withTimestamps();
    }

    public const ORIGINES = ['manuel' => 'Saisie', 'import' => 'Import Excel', 'review' => 'Sprint Review', 'retrospective' => 'Rétrospective'];
    public const MOSCOW = ['must' => 'Must have', 'should' => 'Should have', 'could' => 'Could have', 'wont' => 'Won\'t have'];
    /** Priorités proposées au Product Backlog. */
    public const MOSCOW_BACKLOG = ['must', 'should', 'could'];

    public function sourceCr(): BelongsTo { return $this->belongsTo(CompteRendu::class, 'source_cr_id'); }
    public function avancementPar(): BelongsTo { return $this->belongsTo(User::class, 'avancement_par'); }

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

    /** Statut piloté par les tâches, dans les deux sens (une tâche rouverte fait revenir la story « en cours »). */
    public function synchroniserAvecTaches(?int $sprintId = null): void
    {
        \App\Services\AvancementScrum::synchroniser($this->id);
    }

    /** Stories du backlog où puiser : prêtes (DoR = Oui), non achevées et engagées dans aucun sprint ouvert. */
    public function scopeDisponibles($q)
    {
        return $q->where('dor', true)->whereNotIn('statut', self::FINIES)->whereDoesntHave('sprints', fn($s) => $s->where('sprints.statut', '!=', 'termine'));
    }

    public function estFinie(): bool { return in_array($this->statut, self::FINIES, true); }

    /** Statuts qu'un gestionnaire peut choisir : avec des tâches, le statut en découle ; la mise en revue et l'acceptation restent des décisions. */
    public function statutsPermis(): array
    {
        $avecTaches = $this->relationLoaded('taches')
            ? $this->taches->where('statut', '!=', 'reporte')->isNotEmpty()
            : ($this->taches_count ?? $this->taches()->where('statut', '!=', 'reporte')->count()) > 0;
        if (!$avecTaches) return array_keys(self::STATUTS);
        if ($this->estFinie()) return self::FINIES;
        if ($this->statut === 'bloquee') return ['bloquee'];
        return $this->statut === 'en_revue' ? ['en_cours', 'en_revue'] : [$this->statut, 'en_revue'];
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
