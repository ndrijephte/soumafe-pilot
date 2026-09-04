<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // 1. Types de réunions (daily scrum, retro, démo, etc.)
        if (!Schema::hasTable('types_reunion')) {
            DB::statement("CREATE TABLE types_reunion (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(30) NOT NULL UNIQUE,
                libelle VARCHAR(100) NOT NULL,
                recurrence VARCHAR(20) NOT NULL DEFAULT 'quotidien',
                actif BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Données par défaut
            DB::table('types_reunion')->insert([
                ['code' => 'daily_scrum', 'libelle' => 'Daily Scrum', 'recurrence' => 'quotidien', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'retrospective', 'libelle' => 'Rétrospective', 'recurrence' => 'hebdomadaire', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'sprint_review', 'libelle' => 'Sprint Review / Démo', 'recurrence' => 'hebdomadaire', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'planification', 'libelle' => 'Planification sprint', 'recurrence' => 'hebdomadaire', 'actif' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // 2. Rôles par type de réunion (facilitateur, scribe, etc.)
        if (!Schema::hasTable('roles_reunion')) {
            DB::statement("CREATE TABLE roles_reunion (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type_reunion_id BIGINT UNSIGNED NOT NULL,
                code VARCHAR(40) NOT NULL,
                libelle VARCHAR(80) NOT NULL,
                ordre INT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (type_reunion_id) REFERENCES types_reunion(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Rôles par défaut pour daily scrum
            $dailyId = DB::table('types_reunion')->where('code', 'daily_scrum')->value('id');
            if ($dailyId) {
                DB::table('roles_reunion')->insert([
                    ['type_reunion_id' => $dailyId, 'code' => 'facilitateur', 'libelle' => 'Facilitateur', 'ordre' => 1, 'created_at' => now(), 'updated_at' => now()],
                    ['type_reunion_id' => $dailyId, 'code' => 'scribe', 'libelle' => 'Scribe', 'ordre' => 2, 'created_at' => now(), 'updated_at' => now()],
                    ['type_reunion_id' => $dailyId, 'code' => 'gardien_temps', 'libelle' => 'Gardien du temps', 'ordre' => 3, 'created_at' => now(), 'updated_at' => now()],
                    ['type_reunion_id' => $dailyId, 'code' => 'observateur', 'libelle' => 'Observateur', 'ordre' => 4, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
        }

        // 3. Participants au planning (pool des membres)
        if (!Schema::hasTable('participants_reunion')) {
            DB::statement("CREATE TABLE participants_reunion (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type_reunion_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                actif BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (type_reunion_id) REFERENCES types_reunion(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY uq_part (type_reunion_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }

        // 4. Affectations (planning généré)
        if (!Schema::hasTable('affectations_reunion')) {
            DB::statement("CREATE TABLE affectations_reunion (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                type_reunion_id BIGINT UNSIGNED NOT NULL,
                role_reunion_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                date_reunion DATE NOT NULL,
                statut VARCHAR(20) NOT NULL DEFAULT 'prevu',
                remplacant_id BIGINT UNSIGNED NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                FOREIGN KEY (type_reunion_id) REFERENCES types_reunion(id) ON DELETE CASCADE,
                FOREIGN KEY (role_reunion_id) REFERENCES roles_reunion(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (remplacant_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affectations_reunion');
        Schema::dropIfExists('participants_reunion');
        Schema::dropIfExists('roles_reunion');
        Schema::dropIfExists('types_reunion');
    }
};
