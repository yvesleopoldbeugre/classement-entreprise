<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            // Présentation publique (import CSV ou meta description du site web).
            $table->text('description')->nullable()->after('nom');
            // Dernière tentative d'enrichissement depuis le site (évite de re-tenter à chaque passage).
            $table->timestamp('enrichi_le')->nullable()->after('source_scraping');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['description', 'enrichi_le']);
        });
    }
};
