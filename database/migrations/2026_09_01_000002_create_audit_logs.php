<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            DB::statement("CREATE TABLE audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                user_name VARCHAR(100) NULL,
                action VARCHAR(30) NOT NULL,
                module VARCHAR(50) NOT NULL,
                cible VARCHAR(100) NULL,
                details TEXT NULL,
                ip_address VARCHAR(45) NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_module (module),
                INDEX idx_action (action),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
