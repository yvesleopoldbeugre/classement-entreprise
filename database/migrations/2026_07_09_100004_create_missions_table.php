<?php

use App\Enums\StatutModeration;
use App\Enums\TypeMission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type_mission', TypeMission::values());
            $table->unsignedSmallInteger('duree_mois')->nullable();
            $table->string('fourchette_remuneration')->nullable(); // ex: "300k-500k FCFA"
            $table->boolean('paiement_a_temps')->nullable();
            $table->boolean('respect_contrat')->nullable();
            $table->text('commentaire')->nullable();
            $table->enum('statut_moderation', StatutModeration::values())->default(StatutModeration::EnAttente->value);
            $table->timestamps();

            $table->index(['entreprise_id', 'statut_moderation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
