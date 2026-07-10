<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            // Position dans la liste éditoriale « à éviter » (null = pas sur la liste).
            $table->unsignedTinyInteger('rang_a_eviter')->nullable()->after('score_bayesien');
            $table->index('rang_a_eviter');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropIndex(['rang_a_eviter']);
            $table->dropColumn('rang_a_eviter');
        });
    }
};
