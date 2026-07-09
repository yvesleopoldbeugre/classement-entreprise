<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pseudo_public')->unique()->nullable()->after('name');
            $table->string('poste_actuel')->nullable()->after('pseudo_public');
            $table->boolean('linkedin_verifie')->default(false)->after('poste_actuel');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['pseudo_public']);
            $table->dropColumn(['pseudo_public', 'poste_actuel', 'linkedin_verifie']);
        });
    }
};
