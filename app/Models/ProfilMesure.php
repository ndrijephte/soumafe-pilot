<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilMesure extends Model
{
    protected $table = 'profils_mesure';
    protected $fillable = ['code', 'libelle', 'systeme'];
    protected $casts = ['systeme' => 'boolean'];
}
