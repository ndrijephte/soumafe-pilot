<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

/** User story du Product Backlog d'un projet. */
class UserStory extends Model
{
    protected $table = 'user_stories';
    protected $fillable = ['projet_id', 'numero', 'module', 'intitule', 'exigences', 'taches_techniques', 'priorite', 'statut', 'effort_estime', 'origine', 'criteres_acceptation', 'priorite_moscow', 'points_effort', 'epic', 'profil', 'dor', 'source_cr_id', 'avancement', 'avancement_note', 'avancement_par', 'avancement_le', 'archivee_le', 'archive_motif'];
    protected $casts = ['criteres_acceptation' => 'array', 'avancement_le' => 'datetime', 'dor' => 'boolean', 'contenu_modifie_le' => 'datetime', 'archivee_le' => 'datetime'];

    /** Contenu suivi : sa modification est datée et signalée aux développeurs d'un sprint en cours. */
    public const CHAMPS_SUIVIS = [
        'numero' => 'N°', 'intitule' => 'Intitulé', 'exigences' => 'Exigences', 'module' => 'Fonctionnalité', 'epic' => 'Epic / Module',
        'profil' => 'Profil', 'priorite_moscow' => 'MoSCoW', 'effort_estime' => 'Story points', 'dor' => 'DoR',
    ];
    /** Valeurs conservées à la clôture d'un sprint (instantané du Sprint Backlog). */
    public const CHAMPS_FIGES = ['numero', 'intitule', 'module', 'epic', 'profil', 'priorite_moscow', 'effort_estime'];

    /** Signalement des modifications aux développeurs (désactivé pendant un traitement groupé, qui signale une seule fois). */
    public static bool $signaler = true;

    protected static function booted(): void
    {
        static::updating(function (self $us) {
            $champs = array_intersect_key(self::CHAMPS_SUIVIS, $us->getDirty());
            if (!$champs) return;
            $us->contenu_modifie_le = now();
            $us->contenu_modifie_champs = implode(', ', $champs);
        });
        static::updated(function (self $us) {
            if (self::$signaler && $us->wasChanged('contenu_modifie_le')) self::signalerModifications(collect([$us]));
        });
    }

    /**
     * Dans un sprint clôturé, la story se lit telle qu'elle était à la clôture (instantané du pivot) :
     * points engagés, vélocité, archives et rapports restent stables si le Product Backlog évolue.
     */
    public function getAttribute($key)
    {
        if (in_array($key, self::CHAMPS_FIGES, true) && ($fige = $this->instantaneSprint()) !== null && array_key_exists($key, $fige)) {
            return $fige[$key];
        }
        return parent::getAttribute($key);
    }

    /** Instantané de clôture, quand la story est lue à travers un sprint figé. */
    public function instantaneSprint(): ?array
    {
        if (!$this->relationLoaded('pivot')) return null;
        $brut = $this->getRelation('pivot')?->getAttributes()['instantane'] ?? null;
        return $brut ? (json_decode($brut, true) ?: null) : null;
    }

    /** Valeurs actuelles de la story, hors instantané. */
    public function valeurActuelle(string $champ) { return parent::getAttribute($champ); }

    /** Modifiée après son entrée dans un sprint en cours (contenu, pas le statut). */
    public function modifieeDansSprint(Sprint $sprint): bool
    {
        if (!$this->contenu_modifie_le || $sprint->statut !== 'en_cours') return false;
        $depuis = $sprint->date_debut->copy()->startOfDay();
        $entree = $this->relationLoaded('pivot') ? $this->getRelation('pivot')?->created_at : null;
        if ($entree && $entree->gt($depuis)) $depuis = $entree;
        return $this->contenu_modifie_le->gt($depuis);
    }

    /**
     * Prévient les développeurs qui ont des tâches non terminées sur ces stories dans un sprint en cours
     * (une notification par développeur et par sprint).
     */
    public static function signalerModifications(\Illuminate\Support\Collection $stories): void
    {
        $ids = $stories->pluck('id')->filter()->unique();
        if ($ids->isEmpty() || !\Illuminate\Support\Facades\Schema::hasTable('notifications_app')) return;
        $sprints = Sprint::where('statut', 'en_cours')->whereHas('userStories', fn($q) => $q->whereIn('user_stories.id', $ids))->get();
        foreach ($sprints as $sp) {
            $taches = Tache::where('sprint_id', $sp->id)->whereIn('user_story_id', $ids)->whereNotNull('user_id')
                ->whereNotIn('statut', ['termine', 'reporte'])->get(['user_id', 'user_story_id']);
            foreach ($taches->groupBy('user_id') as $userId => $lot) {
                if ((int) $userId === auth()->id()) continue;
                $concernees = $stories->whereIn('id', $lot->pluck('user_story_id')->unique());
                $quoi = $concernees->count() === 1
                    ? "La user story {$concernees->first()->numero} a été modifiée dans le Product Backlog (" . ($concernees->first()->contenu_modifie_champs ?: 'contenu') . ')'
                    : 'Les user stories ' . $concernees->pluck('numero')->join(', ', ' et ') . ' ont été modifiées dans le Product Backlog';
                NotificationApp::create([
                    'user_id' => $userId, 'type' => 'story_modifiee', 'lu' => false, 'canal' => 'in_app',
                    'contenu' => "Sprint {$sp->numero} : {$quoi}. Vérifiez vos tâches.",
                    'lien' => route('sprints.show', $sp),
                ]);
            }
        }
    }

    public function estArchivee(): bool { return $this->archivee_le !== null; }
    public function scopeActives($q) { return $q->whereNull('user_stories.archivee_le'); }

    public const STATUTS = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'en_revue' => 'En revue', 'terminee' => 'Terminée', 'acceptee' => 'Acceptée', 'bloquee' => 'Bloquée'];
    /** Stories achevées : terminées, et acceptées par le Product Owner à la Review. */
    public const FINIES = ['terminee', 'acceptee'];
    public const STATUTS_SPRINT = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'termine' => 'Terminée'];

    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
    public function sprints(): BelongsToMany
    {
        return $this->belongsToMany(Sprint::class, 'sprint_user_stories')->withPivot('statut_sprint', 'fige_le')->withTimestamps();
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
        return $q->whereNull('user_stories.archivee_le')->where('dor', true)->whereNotIn('statut', self::FINIES)->whereDoesntHave('sprints', fn($s) => $s->where('sprints.statut', '!=', 'termine'));
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
