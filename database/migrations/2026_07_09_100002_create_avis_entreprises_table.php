<?php

use App\Enums\StatutEmploi;
use App\Enums\StatutModeration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_entreprises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('note_ambiance');   // 1-5
            $table->unsignedTinyInteger('note_management'); // 1-5
            $table->unsignedTinyInteger('note_salaire');    // 1-5
            $table->unsignedTinyInteger('note_evolution');  // 1-5
            $table->text('commentaire')->nullable();
            $table->enum('statut_emploi', StatutEmploi::values());
            $table->enum('statut_moderation', StatutModeration::values())->default(StatutModeration::EnAttente->value);
            $table->timestamps();

            $table->unique(['entreprise_id', 'user_id']); // 1 avis global par user/entreprise
            $table->index(['entreprise_id', 'statut_moderation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_entreprises');
    }
};
