<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table) {
            $table->id();
            $table->string('visiteur_token', 64)->unique(); // uuid client (localStorage)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('ip_hash', 64)->nullable(); // pas d'IP en clair (RGPD)
            $table->timestamp('derniere_activite')->nullable()->index();
            $table->timestamp('created_at')->nullable(); // « présent depuis »
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
