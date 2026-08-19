<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeBlocage extends Model
{
    protected $table = 'types_blocage';
    protected $fillable = ['code', 'libelle', 'systeme'];
    protected $casts = ['systeme' => 'boolean'];
}
