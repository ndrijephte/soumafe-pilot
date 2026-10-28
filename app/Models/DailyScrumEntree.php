<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyScrumEntree extends Model
{
    protected $table = 'daily_scrum_entrees';
    protected $fillable = ['daily_scrum_id', 'user_id', 'taches_hier', 'taches_jour', 'duree_restante', 'defis'];

    public function dailyScrum(): BelongsTo { return $this->belongsTo(DailyScrum::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
