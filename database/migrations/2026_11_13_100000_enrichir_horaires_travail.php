<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horaires de travail : chaque règle datée porte aussi
 * - des horaires propres à certains jours (ex. vendredi 08:00 → 16:00), les autres jours suivant l'horaire général ;
 * - une pause déjeuner, déduite de la durée journalière et de la durée de présence ;
 * - une tolérance (minutes) avant de compter un retard ou un départ anticipé ;
 * - les heures des traitements automatiques (contrôle de saisie, création des Daily Scrum).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('horaires_travail', function (Blueprint $t) {
            $t->json('horaires_jours')->nullable()->after('jours')->comment('Horaires propres à certains jours ISO : {"5": {"arrivee": "08:00", "depart": "16:00"}}');
            $t->time('pause_debut')->nullable()->after('horaires_jours');
            $t->time('pause_fin')->nullable()->after('pause_debut');
            $t->unsignedSmallInteger('tolerance_arrivee')->default(0)->after('pause_fin')->comment('Minutes tolérées après l\'heure de début');
            $t->unsignedSmallInteger('tolerance_depart')->default(0)->after('tolerance_arrivee')->comment('Minutes tolérées avant l\'heure de fin');
            $t->time('heure_controle_saisie')->default('08:00:00')->after('tolerance_depart');
            $t->time('heure_daily_auto')->default('07:00:00')->after('heure_controle_saisie');
        });
    }

    public function down(): void
    {
        Schema::table('horaires_travail', function (Blueprint $t) {
            $t->dropColumn(['horaires_jours', 'pause_debut', 'pause_fin', 'tolerance_arrivee', 'tolerance_depart', 'heure_controle_saisie', 'heure_daily_auto']);
        });
    }
};
