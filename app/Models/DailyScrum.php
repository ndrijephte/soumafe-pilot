<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class DailyScrum extends Model
{
    protected $fillable = ['sprint_id', 'numero_jour', 'date_reunion', 'heure_debut', 'heure_fin', 'roles', 'commentaire_general', 'observation', 'cree_par', 'publie_le', 'publie_par', 'soumis_le', 'soumis_par'];
    protected $casts = ['date_reunion' => 'date', 'roles' => 'array', 'publie_le' => 'datetime', 'soumis_le' => 'datetime'];

    /** Rôles tournants du Daily (codes alignés sur roles_reunion). */
    public const ROLES = ['facilitateur' => 'Facilitateur', 'scribe' => 'Scribe', 'gardien_temps' => 'Gardien du temps', 'observateur' => 'Observateur'];

    public function sprint(): BelongsTo { return $this->belongsTo(Sprint::class); }
    public function entrees(): HasMany { return $this->hasMany(DailyScrumEntree::class); }
    public function createur(): BelongsTo { return $this->belongsTo(User::class, 'cree_par'); }
    public function publiePar(): BelongsTo { return $this->belongsTo(User::class, 'publie_par'); }
    public function soumisPar(): BelongsTo { return $this->belongsTo(User::class, 'soumis_par'); }
    /** Rapport publié (approuvé par un pilote) : visible de tous, modifiable par un pilote uniquement. */
    public function estPublie(): bool { return $this->publie_le !== null; }
    /** Soumis à l'approbation d'un pilote : figé, pas encore diffusé. */
    public function estSoumis(): bool { return !$this->estPublie() && $this->soumis_le !== null; }
    public function statutPublication(): array
    {
        return $this->estPublie() ? ['Publié', 'p-ok'] : ($this->estSoumis() ? ['En attente de validation', 'p-cours'] : ['Brouillon', 'p-warn']);
    }

    /** Hors publication, le rapport n'est visible que de l'équipe du projet, des pilotes et des acteurs du Daily. */
    public function visiblePour(User $u): bool
    {
        if ($this->estPublie() || $u->estPilote()) return true;
        $this->loadMissing('sprint.projet');
        return $this->sprint->projet->estVisiblePour($u)
            || in_array($u->id, array_map('intval', array_values($this->roles ?? [])), true)
            || $this->entrees()->where('user_id', $u->id)->exists();
    }

    public function roleUserId(string $role): ?int
    {
        return isset($this->roles[$role]) ? (int) $this->roles[$role] : null;
    }

    /** Libellé d'onglet façon LEVRY : « DAILY 3 16-04-2026 ». */
    public function libelle(): string
    {
        return 'DAILY ' . $this->numero_jour . ' ' . $this->date_reunion->format('d-m-Y');
    }
}
