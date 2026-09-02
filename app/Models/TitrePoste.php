<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TitrePoste extends Model
{
    protected $table = 'titres_poste';
    protected $fillable = ['libelle'];
}
