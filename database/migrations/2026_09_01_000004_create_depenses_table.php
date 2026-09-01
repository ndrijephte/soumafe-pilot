<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $t) {
            $t->id();
            $t->date('date_depense');
            $t->string('type_depense', 100);
            $t->unsignedBigInteger('montant');
            $t->text('commentaire')->nullable();
            $t->string('cree_par', 100)->nullable();
            $t->timestamps();
        });

        // Seed types de dépense dans types_parc
        $types = [
            ['depense', 'salaire', 'Salaire'],
            ['depense', 'reparation', 'Réparation'],
            ['depense', 'fourniture', 'Fourniture'],
            ['depense', 'transport', 'Transport'],
            ['depense', 'facture_internet', 'Facture internet'],
            ['depense', 'facture_electricite', 'Facture électricité'],
            ['depense', 'facture_eau', "Facture d'eau"],
            ['depense', 'maintenance_climatisation', 'Maintenance climatisation'],
        ];

        if (Schema::hasTable('types_parc')) {
            foreach ($types as $i => [$cat, $code, $libelle]) {
                DB::table('types_parc')->insert([
                    'categorie' => $cat,
                    'code'      => $code,
                    'libelle'   => $libelle,
                    'actif'     => true,
                    'ordre'     => $i + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
        DB::table('types_parc')->where('categorie', 'depense')->delete();
    }
};
