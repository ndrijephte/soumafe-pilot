<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications_app') && !Schema::hasColumn('notifications_app', 'lien')) {
            DB::statement("ALTER TABLE notifications_app ADD COLUMN lien VARCHAR(500) NULL AFTER contenu");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('notifications_app', 'lien')) {
            DB::statement("ALTER TABLE notifications_app DROP COLUMN lien");
        }
    }
};
