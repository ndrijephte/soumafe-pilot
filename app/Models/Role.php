<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['code', 'libelle', 'systeme'];
    protected $casts = ['systeme' => 'boolean'];
}
