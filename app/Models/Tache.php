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
        static::saved(function (Tache $t) {
            if ($t->user_story_id && ($t->wasChanged('statut') || $t->wasChanged('user_story_id') || $t->wasRecentlyCreated)) {
                $t->userStory?->synchroniserAvecTaches($t->sprint_id);
            }
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function sprint(): BelongsTo { return $this->belongsTo(Sprint::class); }
    public function userStory(): BelongsTo { return $this->belongsTo(UserStory::class); }
    public function blocages(): HasMany { return $this->hasMany(Blocage::class); }
    public function commentaires(): HasMany { return $this->hasMany(Commentaire::class); }
    public function arbitrage(): HasOne { return $this->hasOne(ArbitrageT3::class); }

    public function scopeDuJour($q, $date = null) { return $q->whereDate('date_debut', $date ?? today()); }
    public function scopeEnRetard($q) { return $q->whereNull('date_fin_reelle')->whereDate('echeance', '<', today()); }

    /** Nombre de jours ouvrés (lundi → vendredi) entre deux dates incluses. */
    public static function joursOuvres($debut, $fin): int
    {
        if (!$debut || !$fin) return 0;
        $d = \Carbon\Carbon::parse($debut)->startOfDay(); $f = \Carbon\Carbon::parse($fin)->startOfDay();
        if ($f->lt($d)) return 0;
        $n = 0;
        for ($j = $d->copy(); $j->lte($f); $j->addDay()) if (!$j->isWeekend()) $n++;
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
        if ($this->echeance && $this->echeance->lt(today())) {
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
