<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reprise de sûreté : tout compte déjà authentifié via un fournisseur
     * (users.provider/provider_id) obtient sa ligne comptes_lies → il apparaît
     * « connecté » dans la page Sécurité. Idempotent (insertOrIgnore + uniques).
     */
    public function up(): void
    {
        $slug = ['linkedin-openid' => 'linkedin'];

        DB::table('users')
            ->whereNotNull('provider')
            ->whereNotNull('provider_id')
            ->orderBy('id')
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
        // Reprise de données : rien à annuler.
    }
};
