<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeEvenement extends Model
{
    protected $table = 'types_evenement';
    protected $fillable = ['code', 'libelle', 'couleur_fond', 'couleur_texte', 'systeme'];
    protected $casts = ['systeme' => 'boolean'];
}
