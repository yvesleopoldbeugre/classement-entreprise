<?php

use App\Enums\StatutModeration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retours_entretiens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('poste_vise');
            $table->date('date_entretien_mois'); // stocker toujours le 1er du mois
            $table->unsignedTinyInteger('nb_etapes')->nullable();
            $table->unsignedSmallInteger('duree_processus_jours')->nullable();
            $table->json('questions_posees')->nullable(); // tags libres
            $table->boolean('a_recu_reponse')->default(false);
            $table->unsignedSmallInteger('delai_reponse_jours')->nullable();
            $table->boolean('a_eu_offre')->default(false);
            $table->text('ressenti_general')->nullable();
            $table->enum('statut_moderation', StatutModeration::values())->default(StatutModeration::EnAttente->value);
            $table->timestamps();

            $table->index(['entreprise_id', 'statut_moderation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retours_entretiens');
    }
};
