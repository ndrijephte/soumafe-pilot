<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documents du parc automobile : pièces jointes de toute opération (véhicule, chauffeur, panne, maintenance,
 * dépense, pénalité, recette, recouvrement, emprunt, prêt, autre ressource), pour la traçabilité de la gestion du parc.
 * Les documents à validité (assurance, visite technique, permis…) portent une date d'expiration suivie par des alertes.
 * Un document renouvelé reste dans l'historique (remplace_id) ; une suppression est un archivage motivé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('documents')) return;
        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->string('documentable_type', 40);
            $t->unsignedBigInteger('documentable_id');
            $t->string('categorie', 40);
            $t->string('libelle', 200);
            $t->string('reference', 100)->nullable();
            $t->date('date_document')->nullable();
            $t->date('date_expiration')->nullable();
            $t->string('fichier', 255);
            $t->string('nom_original', 255);
            $t->string('mime', 120)->nullable();
            $t->unsignedInteger('taille')->default(0);
            $t->foreignId('remplace_id')->nullable()->constrained('documents')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->foreignId('depose_par')->nullable()->constrained('users')->nullOnDelete();
            $t->string('alerte_envoyee', 10)->nullable();
            $t->foreignId('supprime_par')->nullable()->constrained('users')->nullOnDelete();
            $t->string('motif_suppression', 500)->nullable();
            $t->softDeletes();
            $t->timestamps();
            $t->index(['documentable_type', 'documentable_id']);
            $t->index('date_expiration');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
