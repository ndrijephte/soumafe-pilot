<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE types_reunion ADD COLUMN heure TIME NULL AFTER recurrence");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE types_reunion DROP COLUMN heure");
    }
};
