<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            // Droit de réponse : déclaration publique officielle de l'entreprise.
            $table->text('reponse_entreprise')->nullable()->after('rang_a_eviter');
            $table->timestamp('reponse_entreprise_le')->nullable()->after('reponse_entreprise');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['reponse_entreprise', 'reponse_entreprise_le']);
        });
    }
};
