<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Database\Eloquent\SoftDeletes;

class Tache extends Model
{
    use SoftDeletes;
    protected $table = 'taches';
    protected $fillable = ['user_id', 'type', 'intitule', 'projet_id', 'sprint_id', 'activite',
        'priorite', 'date_debut', 'echeance', 'date_fin_reelle', 'points', 'statut',
        'qualification', 'effort_volontaire', 'justification_requise', 'user_story_id', 'teste'];
    protected $casts = ['date_debut' => 'date', 'echeance' => 'date', 'date_fin_reelle' => 'date', 'teste' => 'boolean'];

    /** Module Scrum : l'avancement des tâches fait progresser la user story (backlog + sprint). */
    protected static function booted(): void
    {
        // Hiérarchie dynamique : la user story (et donc son epic et son module) suit ses tâches
        static::saved(function (Tache $t) {
            if ($t->wasRecentlyCreated || $t->wasChanged(['statut', 'user_story_id', 'sprint_id'])) {
                \App\Services\AvancementScrum::synchroniser($t->user_story_id);
                if ($t->wasChanged('user_story_id')) \App\Services\AvancementScrum::synchroniser($t->getOriginal('user_story_id'), true);
            }
        });
        static::deleted(fn (Tache $t) => \App\Services\AvancementScrum::synchroniser($t->user_story_id, true));
        static::restored(fn (Tache $t) => \App\Services\AvancementScrum::synchroniser($t->user_story_id));
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function sprint(): BelongsTo { return $this->belongsTo(Sprint::class); }
    public function userStory(): BelongsTo { return $this->belongsTo(UserStory::class); }
    public function blocages(): HasMany { return $this->hasMany(Blocage::class); }
    public function commentaires(): HasMany { return $this->hasMany(Commentaire::class); }
    public function arbitrage(): HasOne { return $this->hasOne(ArbitrageT3::class); }

    public function scopeDuJour($q, $date = null) { return $q->whereDate('date_debut', $date ?? today()); }
    /**
     * Tâches en retard : non terminées, échéance dépassée. Une tâche d'un sprint clôturé n'est plus en retard :
     * sa qualité est figée à la clôture (« non terminée au sprint ») et ne pèse pas sur les périodes suivantes.
     */
    /**
     * Tâche archivée : rattachée à un sprint clôturé (sa qualité est figée au sprint), ou tâche hors sprint
     * terminée avant le début de la période d'exercice en cours (05 → 04). Consultable sur demande uniquement.
     */
    public function scopeArchivees($q)
    {
        $debut = \App\Services\PeriodeExercice::debut(today());
        return $q->where(fn ($w) => $w->whereHas('sprint', fn ($s) => $s->where('statut', 'termine'))
            ->orWhere(fn ($h) => $h->whereNull('sprint_id')->where('statut', 'termine')->whereDate('date_fin_reelle', '<', $debut)));
    }

    public function scopeNonArchivees($q)
    {
        $debut = \App\Services\PeriodeExercice::debut(today());
        return $q->whereDoesntHave('sprint', fn ($s) => $s->where('statut', 'termine'))
            ->where(fn ($w) => $w->whereNotNull('sprint_id')
                ->orWhere('statut', '!=', 'termine')
                ->orWhereNull('date_fin_reelle')
                ->orWhereDate('date_fin_reelle', '>=', $debut));
    }

    public function scopeEnRetard($q)
    {
        return $q->whereNull('date_fin_reelle')->whereNotIn('statut', ['termine', 'reporte'])
            ->whereNotNull('echeance')->whereDate('echeance', '<', today())
            ->where(fn($w) => $w->whereNull('sprint_id')->orWhereHas('sprint', fn($s) => $s->where('statut', '!=', 'termine')));
    }

    /** Même règle pour une tâche chargée. */
    public function estEnRetard(): bool
    {
        if (in_array($this->statut, ['termine', 'reporte'], true) || $this->date_fin_reelle || !$this->echeance) return false;
        if ($this->sprint_id && $this->sprint?->statut === 'termine') return false;
        return $this->echeance->lt(today());
    }

    /** Tâche restée non terminée dans un sprint clôturé. */
    public function nonTermineeSprintClos(): bool
    {
        return $this->statut !== 'termine' && $this->sprint_id && $this->sprint?->statut === 'termine';
    }

    /** Nombre de jours travaillés (selon les horaires en vigueur) entre deux dates incluses. */
    public static function joursOuvres($debut, $fin): int
    {
        if (!$debut || !$fin) return 0;
        $d = \Carbon\Carbon::parse($debut)->startOfDay(); $f = \Carbon\Carbon::parse($fin)->startOfDay();
        if ($f->lt($d)) return 0;
        $n = 0;
        for ($j = $d->copy(); $j->lte($f); $j->addDay()) if (\App\Services\CalendrierTravail::estJourTravaille($j)) $n++;
        return $n;
    }

    /** Durée planifiée en jours ouvrés (début → échéance). */
    public function dureeJours(): int { return self::joursOuvres($this->date_debut, $this->echeance); }

    /**
     * Code Scrum affiché dans le Sprint Planning, la Review et le Daily, à partir du statut réel :
     * D Démarré (en cours, 1er jour) · E En cours · T Terminé · TT Terminé testé · ER En retard (n jours).
     * @return array{code: string, libelle: string, classe: string}
     */
    public function codeScrum(): array
    {
        if ($this->statut === 'termine') {
            return $this->teste ? ['code' => 'TT', 'libelle' => 'Terminé testé', 'classe' => 'sc-tt'] : ['code' => 'T', 'libelle' => 'Terminé', 'classe' => 'sc-t'];
        }
        if ($this->nonTermineeSprintClos()) {
            return ['code' => 'NT', 'libelle' => 'Non terminé (sprint clos)', 'classe' => 'sc-autre'];
        }
        if ($this->estEnRetard()) {
            $j = self::joursOuvres($this->echeance->copy()->addDay(), today());
            return ['code' => 'ER', 'libelle' => "En retard de {$j} jour(s)", 'classe' => 'sc-er'];
        }
        return match ($this->statut) {
            'en_cours' => ($this->date_debut && $this->date_debut->isToday())
                ? ['code' => 'D', 'libelle' => 'Démarré', 'classe' => 'sc-d']
                : ['code' => 'E', 'libelle' => 'En cours', 'classe' => 'sc-e'],
            'bloque'  => ['code' => '—', 'libelle' => 'Bloqué', 'classe' => 'sc-bloque'],
            'reporte' => ['code' => '—', 'libelle' => 'Reporté', 'classe' => 'sc-autre'],
            default   => ['code' => '—', 'libelle' => 'Non démarré', 'classe' => 'sc-autre'],
        };
    }

    /** Niveaux du voyant d'urgence, du plus urgent au moins urgent. */
    public const URGENCES = [
        'retard' => 'En retard', 'bloque' => 'Bloquée', 'proche' => '2 jours ou moins', 'semaine' => '3 à 5 jours',
        'ok' => 'Plus de 5 jours', 'aucune' => 'Sans échéance', 'fait' => 'Terminée', 'clos' => 'Sprint clos / reportée',
    ];

    /**
     * Urgence de la tâche pour le voyant (délais en jours ouvrés, comme le code Scrum ER).
     * @return array{niveau: string, libelle: string, court: string}
     */
    public function urgence(): array
    {
        if ($this->statut === 'termine') {
            $fin = $this->date_fin_reelle?->format('d/m/Y');
            return ['niveau' => 'fait', 'libelle' => $fin ? "Terminée le {$fin}" : 'Terminée', 'court' => 'Terminée'];
        }
        if ($this->nonTermineeSprintClos()) return ['niveau' => 'clos', 'libelle' => 'Non terminée (sprint clos)', 'court' => 'Sprint clos'];
        if ($this->statut === 'reporte') return ['niveau' => 'clos', 'libelle' => 'Reportée', 'court' => 'Reportée'];
        if ($this->estEnRetard()) {
            $j = self::joursOuvres($this->echeance->copy()->addDay(), today());
            return ['niveau' => 'retard', 'libelle' => "En retard de {$j} jour(s) ouvré(s)", 'court' => "{$j} j de retard"];
        }
        if ($this->statut === 'bloque') return ['niveau' => 'bloque', 'libelle' => 'Tâche bloquée', 'court' => 'Bloquée'];
        if (!$this->echeance) return ['niveau' => 'aucune', 'libelle' => 'Sans échéance', 'court' => 'Sans échéance'];
        if ($this->echeance->isToday()) return ['niveau' => 'proche', 'libelle' => "Échéance aujourd'hui", 'court' => "Aujourd'hui"];
        $j = self::joursOuvres(today()->addDay(), $this->echeance);
        return [
            'niveau' => $j <= 2 ? 'proche' : ($j <= 5 ? 'semaine' : 'ok'),
            'libelle' => "Échéance dans {$j} jour(s) ouvré(s)",
            'court' => "Dans {$j} j",
        ];
    }

    /** Libellé lisible du statut. */
    public function labelStatut(): string
    {
        return match ($this->statut) {
            'a_faire'    => 'À faire',
            'en_cours'   => 'En cours',
            'a_valider'  => 'En cours de validation',
            'bloque'     => 'Bloqué',
            'termine'    => 'Terminé',
            'reporte'    => 'Reporté',
            default      => ucfirst(str_replace('_', ' ', $this->statut)),
        };
    }
}
