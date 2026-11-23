<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Sprint extends Model
{
    protected $fillable = ['projet_id', 'numero', 'objectif', 'date_debut', 'date_fin', 'engagement_points', 'statut', 'visible_collaborateurs', 'collaborateurs_exclus', 'justification_arret', 'arrete_le', 'periode_exercice', 'periode_arbitree_par', 'periode_arbitree_le'];
    protected $casts = ['date_debut' => 'date', 'date_fin' => 'date', 'arrete_le' => 'datetime', 'periode_exercice' => 'date', 'periode_arbitree_le' => 'datetime'];

    /* ─── Période d'exercice (05 → 04) ─── */

    /** Débuts des périodes d'exercice couvertes par le sprint. */
    public function periodesCouvertes(): \Illuminate\Support\Collection
    {
        return \App\Services\PeriodeExercice::couvertes($this->date_debut, $this->date_fin);
    }

    /** Sprint à cheval sur deux périodes d'exercice (ou plus). */
    public function estACheval(): bool { return $this->periodesCouvertes()->count() > 1; }

    /** Proposition d'arbitrage : la période qui contient le plus de jours ouvrés du sprint (à égalité, la dernière). */
    public function periodeProposee(): \Carbon\Carbon
    {
        return $this->periodesCouvertes()->sortBy(function ($debut) {
            [$d, $f] = \App\Services\PeriodeExercice::bornes($debut);
            return [-Tache::joursOuvres($this->date_debut->max($d), $this->date_fin->min($f)), -$debut->timestamp];
        })->first();
    }

    /** Période d'exercice retenue : unique, ou arbitrée par un pilote ; null tant qu'un sprint à cheval n'est pas arbitré. */
    public function periodeExercice(): ?\Carbon\Carbon
    {
        $couvertes = $this->periodesCouvertes();
        if ($this->periode_exercice && $couvertes->contains(fn($p) => $p->isSameDay($this->periode_exercice))) return $this->periode_exercice->copy();
        return $couvertes->count() === 1 ? $couvertes->first() : null;
    }

    public function arbitragePeriodeRequis(): bool { return $this->periodeExercice() === null; }
    public function arbitrePar(): BelongsTo { return $this->belongsTo(User::class, 'periode_arbitree_par'); }

    /** IDs des collaborateurs exclus (JSON → array). */
    public function getExclusAttribute(): array
    {
        return json_decode($this->collaborateurs_exclus ?? '[]', true) ?: [];
    }

    /** Ce sprint est-il visible pour un collaborateur donné ? */
    public function visiblePour(int $userId): bool
    {
        if (!$this->visible_collaborateurs) return false;
        return !in_array($userId, $this->exclus);
    }

    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function taches(): HasMany { return $this->hasMany(Tache::class); }
    /** Sprint Backlog : user stories retenues pour ce sprint. */
    public function userStories(): BelongsToMany
    {
        return $this->belongsToMany(UserStory::class, 'sprint_user_stories')->withPivot('statut_sprint', 'instantane', 'fige_le')->withTimestamps();
    }

    /**
     * Clôture : chaque story du Sprint Backlog est figée telle qu'elle est (intitulé, fonctionnalité, epic, points…).
     * Le sprint se lit ensuite à travers cet instantané, quelles que soient les évolutions du Product Backlog.
     */
    public function figerBacklog(): int
    {
        $stories = $this->userStories()->get();
        foreach ($stories as $us) {
            $valeurs = collect(UserStory::CHAMPS_FIGES)->mapWithKeys(fn($c) => [$c => $us->valeurActuelle($c)])->all();
            $valeurs['effort_estime'] = $valeurs['effort_estime'] === null ? null : (int) $valeurs['effort_estime'];
            $valeurs['statut'] = $us->statut;
            $this->userStories()->updateExistingPivot($us->id, ['instantane' => json_encode($valeurs, JSON_UNESCAPED_UNICODE), 'fige_le' => now()]);
        }
        $this->unsetRelation('userStories');
        return $stories->count();
    }
    /** Ajustements d'équipe propres au sprint (ajouts hors équipe projet, retraits). */
    public function equipeAjustements(): HasMany { return $this->hasMany(SprintEquipe::class); }
    public function dailyScrums(): HasMany { return $this->hasMany(DailyScrum::class)->orderBy('numero_jour'); }

    /**
     * Objectif du sprint découpé en objectifs distincts (un par ligne, ou séparés par « ; »),
     * pour un affichage en liste. Un objectif d'un seul tenant reste un seul élément.
     * @return string[]
     */
    public function objectifs(): array
    {
        return self::decouperObjectif($this->objectif);
    }

    public static function decouperObjectif(?string $texte): array
    {
        $parts = preg_split('/\r?\n|;/u', (string) $texte);
        return array_values(array_filter(array_map(function ($p) {
            $p = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $p)), " \t-•·.");
            return $p === '' ? '' : mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1);
        }, $parts)));
    }

    /** Durée du sprint en jours travaillés (horaires paramétrables). */
    public function dureeJours(): int
    {
        return Tache::joursOuvres($this->date_debut, $this->date_fin);
    }

    /**
     * Urgence du sprint pour le voyant : échéance de fin, en jours ouvrés.
     * @return array{niveau: string, libelle: string, court: string}
     */
    public function urgence(): array
    {
        if ($this->statut === 'termine') return ['niveau' => 'fait', 'libelle' => 'Sprint clôturé', 'court' => 'Clôturé'];
        if ($this->statut === 'planifie') return ['niveau' => 'aucune', 'libelle' => 'Sprint planifié, non démarré', 'court' => 'Planifié'];
        $fin = $this->date_fin->format('d/m');
        if ($this->date_fin->lt(today())) return ['niveau' => 'retard', 'libelle' => "Sprint échu le {$fin} : à clôturer", 'court' => "Échu le {$fin}"];
        if ($this->date_fin->isToday()) return ['niveau' => 'proche', 'libelle' => "Le sprint se termine aujourd'hui", 'court' => "Fin aujourd'hui"];
        $j = Tache::joursOuvres(today()->addDay(), $this->date_fin);
        return ['niveau' => $j <= 2 ? 'proche' : ($j <= 5 ? 'semaine' : 'ok'), 'libelle' => "{$j} jour(s) ouvré(s) restant(s), fin le {$fin}", 'court' => "{$j} j restant(s)"];
    }

    /*
     * Story points : une seule règle pour tous les écrans (fiche, avancement, listes, rapports, archives).
     *  - Base « stories » dès qu'une story du Sprint Backlog est estimée : engagé = Σ points des stories,
     *    livré = Σ points des stories terminées dans le sprint (une story compte quand elle est finie).
     *  - Sinon base « tâches » : engagé = Σ points des tâches du sprint, livré = Σ points des tâches terminées.
     *  - En dernier recours, l'engagement saisi sur le sprint.
     * La progression pondérée (Σ points × exécution de chaque story) montre l'avancement avant qu'une story soit finie.
     */
    public function basePoints(): string
    {
        return $this->userStories->sum('effort_estime') > 0 ? 'stories' : 'taches';
    }

    /** Points engagés. Sprint clôturé : points figés à la clôture (instantané des stories). */
    public function pointsEngages(): int
    {
        if ($this->basePoints() === 'stories') return (int) $this->userStories->sum('effort_estime');
        $pts = (int) $this->taches()->where('statut', '!=', 'reporte')->sum('points');
        return $pts ?: (int) $this->engagement_points;
    }

    /** Points livrés : stories terminées dans le sprint (ou, en base tâches, tâches terminées). */
    public function pointsLivres(): int
    {
        if ($this->basePoints() === 'stories') {
            return (int) $this->userStories->filter(fn($us) => ($us->pivot->statut_sprint ?? null) === 'termine' || ($this->statut !== 'termine' && $us->estFinie()))->sum('effort_estime');
        }
        return (int) $this->taches()->where('statut', 'termine')->sum('points');
    }

    /** Points réalisés, pondérés par l'exécution de chaque story (ou par les tâches terminées en base tâches). */
    public function pointsRealises(): int
    {
        // Sprint clôturé ou base tâches : ce qui est réalisé est ce qui est livré
        if ($this->basePoints() !== 'stories' || $this->statut === 'termine') return $this->pointsLivres();
        $taches = $this->taches()->get(['id', 'user_story_id', 'statut']);
        return (int) round($this->userStories->sum(fn($us) => (int) $us->effort_estime
            * \App\Services\AvancementScrum::pctStory($us, $taches->where('user_story_id', $us->id)) / 100));
    }

    /** Stories du Sprint Backlog sans story points : leur travail n'entre pas dans les points. */
    public function storiesNonEstimees(): int
    {
        return $this->userStories->whereNull('effort_estime')->count();
    }

    /** Résumé lisible de la progression des points, pour les infobulles. */
    public function resumePoints(): string
    {
        $eng = $this->pointsEngages();
        if (!$eng) return 'Aucun point engagé : estimez les user stories du Sprint Backlog (ou les tâches).';
        $txt = $this->basePoints() === 'stories'
            ? "{$this->pointsLivres()} pts livrés (stories terminées) sur {$eng} engagés · {$this->pointsRealises()} pts réalisés en tenant compte de l'exécution des stories en cours"
            : "{$this->pointsLivres()} pts de tâches terminées sur {$eng} (aucune story estimée)";
        if ($n = $this->storiesNonEstimees()) $txt .= " · {$n} story(s) non estimée(s) : leur travail n'est pas compté";
        return $txt;
    }
}
