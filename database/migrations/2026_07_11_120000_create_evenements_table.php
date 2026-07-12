<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // App\Enums\TypeEvenement
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('sujet'); // sujet_type + sujet_id (entreprise, avis…)
            $table->string('url')->nullable();          // page visitée (type=visite)
            $table->string('visiteur_hash', 64)->nullable(); // sha256(ip+session+APP_KEY) — pas d'IP en clair
            $table->timestamp('created_at')->nullable();

            // Séries temporelles par type + comptage des visiteurs uniques.
            $table->index(['type', 'created_at']);
            $table->index('visiteur_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
