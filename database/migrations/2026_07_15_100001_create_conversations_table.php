<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('visiteur_token', 64)->unique(); // une conversation par visiteur
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('statut')->default('ouverte'); // ouverte | fermee
            $table->boolean('humain_actif')->default(false); // un admin a pris la main → le bot se tait
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
