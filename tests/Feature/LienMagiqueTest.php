<?php

namespace Tests\Feature;

use App\Mail\LienConnexion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LienMagiqueTest extends TestCase
{
    use RefreshDatabase;

    private const PREFIXE = 'magic-login:';

    public function test_la_demande_envoie_un_email_avec_le_lien(): void
    {
        Mail::fake();

        $this->postJson(route('magic.send'), ['email' => 'nouveau@example.com'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        Mail::assertSent(LienConnexion::class);
    }

    public function test_le_lien_cree_le_compte_et_connecte(): void
    {
        $token = 'jeton-de-test';
        Cache::put(self::PREFIXE.$token, 'nouveau@example.com', now()->addMinutes(30));

        $this->get(route('magic.login', ['token' => $token]))
            ->assertRedirect(route('classement.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'nouveau@example.com']);
    }

    public function test_le_lien_connecte_un_utilisateur_existant_sans_doublon(): void
    {
        $user = User::factory()->create(['email' => 'connu@example.com']);
        $token = 'jeton-connu';
        Cache::put(self::PREFIXE.$token, 'connu@example.com', now()->addMinutes(30));

        $this->get(route('magic.login', ['token' => $token]));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::where('email', 'connu@example.com')->count());
    }

    public function test_le_lien_est_a_usage_unique(): void
    {
        $token = 'jeton-unique';
        Cache::put(self::PREFIXE.$token, 'x@example.com', now()->addMinutes(30));

        $this->get(route('magic.login', ['token' => $token]));

        // Le token est consommé.
        $this->assertNull(Cache::get(self::PREFIXE.$token));
    }

    public function test_un_lien_invalide_est_refuse(): void
    {
        $this->get(route('magic.login', ['token' => 'inexistant']))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
