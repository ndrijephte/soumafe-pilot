<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('depenses') || !Schema::hasTable('maintenances_preventives')) return;

        // La dépense d'une maintenance porte le véhicule de cette maintenance
        DB::table('depenses')
            ->join('maintenances_preventives', 'maintenances_preventives.depense_id', '=', 'depenses.id')
            ->where('depenses.type_depense', 'maintenance')
            ->where(fn($q) => $q->whereNull('depenses.vehicule_id')->orWhereColumn('depenses.vehicule_id', '!=', 'maintenances_preventives.vehicule_id'))
            ->update(['depenses.vehicule_id' => DB::raw('maintenances_preventives.vehicule_id')]);
    }

    public function down(): void
    {
    }
};
