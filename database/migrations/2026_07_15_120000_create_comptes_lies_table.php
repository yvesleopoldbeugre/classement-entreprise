<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comptes_lies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // slug : google | github | facebook | linkedin
            $table->string('provider_id');
            $table->string('email')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['provider', 'provider_id']); // un compte social = un seul utilisateur
            $table->unique(['user_id', 'provider']);      // un fournisseur par utilisateur
        });

        // Reprise des liaisons existantes (users.provider/provider_id → comptes_lies).
        $slug = ['linkedin-openid' => 'linkedin'];
        DB::table('users')->whereNotNull('provider')->whereNotNull('provider_id')->orderBy('id')
            ->each(function ($u) use ($slug) {
                DB::table('comptes_lies')->insertOrIgnore([
                    'user_id' => $u->id,
                    'provider' => $slug[$u->provider] ?? $u->provider,
                    'provider_id' => $u->provider_id,
                    'email' => $u->email,
                    'created_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes_lies');
    }
};
