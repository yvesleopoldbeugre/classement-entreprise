<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->morphs('signalable'); // signalable_type + signalable_id (avis/entretien/mission)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('motif')->nullable();
            $table->timestamps();

            // Un utilisateur ne peut signaler qu'une fois le même contenu.
            $table->unique(['signalable_type', 'signalable_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
