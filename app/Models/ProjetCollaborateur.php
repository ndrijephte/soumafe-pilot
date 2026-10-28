<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjetCollaborateur extends Model
{
    protected $table = 'projet_collaborateurs';
    protected $fillable = ['projet_id', 'user_id', 'role_projet', 'date_ajout', 'date_retrait', 'actif'];
    protected $casts = ['date_ajout' => 'date', 'date_retrait' => 'date', 'actif' => 'boolean'];

    public const ROLES = [
        'responsable'   => 'Responsable projet',
        'product_owner' => 'Product Owner',
        'scrum_master'  => 'Scrum Master',
        'developpeur'   => 'Développeur',
        'testeur'       => 'Testeur',
        'designer'      => 'Designer',
        'autre'         => 'Autre',
    ];

    public function projet(): BelongsTo { return $this->belongsTo(Projet::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function roleLibelle(): string { return self::ROLES[$this->role_projet] ?? ucfirst(str_replace('_', ' ', $this->role_projet)); }
}
