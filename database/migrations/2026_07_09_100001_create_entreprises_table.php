<?php

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entreprises', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique(); // pour URLs propres et dédoublonnage
            $table->enum('secteur_activite', SecteurActivite::values());
            $table->string('adresse')->nullable();
            $table->string('commune')->nullable();
            $table->string('site_web')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('taille_estimee')->nullable(); // ex: "10-50"
            $table->year('date_creation')->nullable();
            $table->string('source_scraping')->nullable();
            $table->enum('statut', StatutEntreprise::values())->default(StatutEntreprise::AVerifier->value);

            // --- Colonnes de classement (dénormalisées, recalculées par ClassementService) ---
            $table->unsignedInteger('nb_avis_total')->default(0);
            $table->decimal('moy_ambiance', 3, 2)->nullable();
            $table->decimal('moy_management', 3, 2)->nullable();
            $table->decimal('moy_salaire', 3, 2)->nullable();
            $table->decimal('moy_evolution', 3, 2)->nullable();
            $table->decimal('note_globale', 3, 2)->nullable();     // moyenne simple des 4 dimensions
            $table->decimal('score_bayesien', 4, 3)->nullable();   // score utilisé pour le classement

            $table->timestamps();

            $table->index('score_bayesien'); // tri du classement
            $table->index('secteur_activite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entreprises');
    }
};
