<?php
namespace App\Models;

use App\Services\ExerciceParc;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Schema;

class Objectif extends Model
{
    protected $fillable = [
        'user_id', 'type', 'projet_id', 'source', 'libelle',
        'ponderation', 'periode', 'date_debut', 'date_echeance',
        'avancement', 'evaluation', 'statut_validation', 'commentaire_pilote',
        'parent_id', 'priorite',
        'note_auto', 'commentaire_auto', 'note_pilote', 'date_evaluation',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_echeance' => 'date',
        'date_evaluation' => 'date',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function enfants(): HasMany { return $this->hasMany(self::class, 'parent_id'); }

    public function resultats(): HasMany
    {
        return $this->hasMany(ResultatCle::class)->orderBy('ordre');
    }

    public function historique(): HasMany
    {
        return $this->hasMany(HistoriqueAvancement::class)->orderBy('date');
    }

    public function tachesLiees(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Tache::class, 'objectif_tache');
    }

    public function scopePeriode($q, string $p) { return $q->where('periode', $p); }
    public function scopeValides($q) { return $q->where('statut_validation', 'valide'); }
    public function scopeIndividuels($q) { return $q->where('type', 'individuel'); }
    public function scopeProjet($q) { return $q->where('type', 'projet'); }
    public function scopeRacines($q) { return $q->whereNull('parent_id'); }

    /**
     * Périodes d'exercice disponibles (6 avant → 3 après), du 05 au 04 du mois suivant (ExerciceParc).
     * La clé 'Y-m' d'un objectif désigne l'exercice qui commence ce mois-là.
     */
    public static function moisDisponibles(): array
    {
        $mois = [];
        $cle = ExerciceParc::decaler(ExerciceParc::cle(), -6);
        for ($i = 0; $i < 10; $i++, $cle = ExerciceParc::decaler($cle, 1)) $mois[$cle] = self::libellePeriode($cle);
        return $mois;
    }

    /** « Octobre 2026 (05/10 au 04/11/2026) » */
    public static function libellePeriode(string $cle): string
    {
        return ExerciceParc::libelle($cle);
    }

    /** Exercice en cours. */
    public static function periodeCourante(): string
    {
        return ExerciceParc::cle();
    }

    /** Recalcule l'avancement à partir des résultats clés. */
    public function recalculerDepuisKR(): int
    {
        if (!Schema::hasTable('resultats_cles')) return $this->avancement ?? 0;
        $krs = $this->resultats;
        if ($krs->isEmpty()) return $this->avancement ?? 0;

        $sommePoids = $krs->sum('poids');
        if ($sommePoids <= 0) return 0;

        $total = 0;
        foreach ($krs as $kr) {
            $total += $kr->progression() * $kr->poids;
        }
        return (int) round($total / $sommePoids);
    }

    /** Synchronise l'avancement depuis les KR et enregistre l'historique. */
    public function syncAvancementKR(): void
    {
        $ancien = $this->avancement;
        $nouveau = $this->recalculerDepuisKR();

        if ($ancien !== $nouveau) {
            $this->update(['avancement' => $nouveau]);
            $this->snapshotHistorique('kr_sync');
        }

        if (Schema::hasColumn('objectifs', 'evaluation')) {
            $eval = $this->evaluerAuto();
            if ($this->evaluation !== $eval) {
                $this->update(['evaluation' => $eval]);
            }
        }
    }

    /** Enregistre un snapshot dans l'historique. */
    public function snapshotHistorique(string $source = 'manuel'): void
    {
        if (!Schema::hasTable('historique_avancement')) return;

        HistoriqueAvancement::updateOrCreate(
            ['objectif_id' => $this->id, 'date' => now()->toDateString()],
            ['avancement' => $this->avancement, 'source' => $source]
        );
    }

    /** Recalcule l'avancement basé sur le ratio de tâches terminées. */
    public function avancementDepuisTaches(): ?int
    {
        if (!Schema::hasTable('objectif_tache')) return null;
        $taches = $this->tachesLiees;
        if ($taches->isEmpty()) return null;
        $terminees = $taches->where('statut', 'termine')->count();
        return (int) round($terminees / $taches->count() * 100);
    }

    /** Évaluation automatique basée sur avancement + échéance. */
    public function evaluerAuto(): string
    {
        // Priorité au verdict indicateurs si KR présents
        $verdict = $this->verdictDepuisIndicateurs();
        if ($verdict !== 'en_cours') return $verdict;

        if ($this->avancement >= 100) return 'atteint';
        if ($this->date_echeance && $this->date_echeance->isPast()) {
            return $this->avancement >= 60 ? 'partiel' : 'non_atteint';
        }
        return 'en_cours';
    }

    /**
     * Verdict automatique basé sur la validation manuelle des indicateurs KPI.
     * - Tous validés atteint → 'atteint'
     * - Tous tranchés, certains non atteint → 'partiel' ou 'non_atteint'
     * - Indicateurs en attente → 'en_cours'
     */
    public function verdictDepuisIndicateurs(): string
    {
        if (!Schema::hasTable('resultats_cles')) return 'en_cours';
        $krs = $this->resultats;
        if ($krs->isEmpty()) return 'en_cours';

        $enAttente = $krs->whereNull('valide')->count();
        if ($enAttente > 0) return 'en_cours';

        $atteints = $krs->where('valide', true)->count();
        $total = $krs->count();

        if ($atteints === $total) return 'atteint';
        if ($atteints > 0) return 'partiel';
        return 'non_atteint';
    }

    /** Données pour l'anneau KPI SVG. Retourne les segments avec couleur et angle. */
    public function anneauKPI(): array
    {
        if (!Schema::hasTable('resultats_cles')) return [];
        $krs = $this->resultats;
        if ($krs->isEmpty()) return [];

        $sommePoids = $krs->sum('poids');
        if ($sommePoids <= 0) return [];

        $segments = [];
        foreach ($krs as $kr) {
            $ratio = $kr->poids / $sommePoids;
            $couleur = match (true) {
                is_null($kr->valide) => '#cbd5e1', // gris - en attente
                $kr->valide === true, $kr->valide == 1 => '#22c55e', // vert - atteint
                default => '#ef4444', // rouge - non atteint
            };
            $segments[] = [
                'ratio' => $ratio,
                'couleur' => $couleur,
                'libelle' => $kr->libelle,
                'statut' => is_null($kr->valide) ? 'en_attente' : ($kr->valide ? 'atteint' : 'non_atteint'),
            ];
        }
        return $segments;
    }

    /** Score pondéré d'un ensemble d'objectifs. */
    public static function scoreGroupe($objectifs): int
    {
        $sommePond = $objectifs->sum('ponderation');
        if ($sommePond <= 0) return 0;
        return (int) round($objectifs->sum(fn($o) => $o->avancement * $o->ponderation / 100));
    }

    /** Jours restants avant échéance. */
    public function joursRestants(): ?int
    {
        if (!$this->date_echeance) return null;
        return (int) max(0, now()->diffInDays($this->date_echeance, false));
    }

    /** Score agrégé des enfants pondéré. */
    public function scoreCascade(): int
    {
        $enfants = $this->enfants;
        if ($enfants->isEmpty()) return $this->avancement ?? 0;
        return self::scoreGroupe($enfants);
    }

    /** Retourne les alertes actives pour cet objectif. */
    public function alertes(): array
    {
        $alertes = [];
        $jours = $this->joursRestants();

        if ($jours !== null && $jours <= 7 && $jours > 0 && $this->avancement < 70) {
            $alertes[] = ['type' => 'echeance', 'msg' => "Échéance dans {$jours}j - avancement à {$this->avancement}%"];
        }

        if ($this->date_echeance && $this->date_echeance->isPast() && $this->avancement < 100) {
            $diff = (int) now()->diffInDays($this->date_echeance);
            $alertes[] = ['type' => 'retard', 'msg' => "En retard de {$diff}j - avancement {$this->avancement}%"];
        }

        if (Schema::hasTable('historique_avancement') && $this->avancement < 100 && $this->avancement > 0) {
            $dernier = $this->historique()->latest('date')->first();
            if ($dernier && $dernier->date->diffInDays(now()) >= 14) {
                $alertes[] = ['type' => 'stagnation', 'msg' => "Aucune progression depuis " . $dernier->date->diffInDays(now()) . "j"];
            }
        }

        return $alertes;
    }

    /** Badge priorité. */
    public function badgePriorite(): array
    {
        return match ($this->priorite ?? 'moyenne') {
            'haute' => ['class' => 'b-ko', 'label' => 'Haute'],
            'basse' => ['class' => 'b-att', 'label' => 'Basse'],
            default => ['class' => 'b-accent', 'label' => 'Moyenne'],
        };
    }
}
