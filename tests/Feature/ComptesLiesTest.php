<?php

namespace Tests\Feature;

use App\Models\CompteLie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class ComptesLiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.google.client_id' => 'id',
            'services.google.client_secret' => 'secret',
        ]);
    }

    private function fakeSocialite(string $id, ?string $email): void
    {
        $oauth = Mockery::mock(SocialiteUser::class);
        $oauth->shouldReceive('getId')->andReturn($id);
        $oauth->shouldReceive('getEmail')->andReturn($email);
        $oauth->shouldReceive('getName')->andReturn('Utilisateur Test');
        $oauth->shouldReceive('getNickname')->andReturn(null);
        $oauth->shouldReceive('getAvatar')->andReturn(null);

        Socialite::shouldReceive('driver')->with('google')->andReturnUsing(function () use ($oauth) {
            $p = Mockery::mock();
            $p->shouldReceive('redirectUrl')->andReturnSelf();
            $p->shouldReceive('user')->andReturn($oauth);

            return $p;
        });
    }

    public function test_la_connexion_sso_cree_un_compte_et_un_lien(): void
    {
        $this->fakeSocialite('EXT-1', 'nouveau@example.com');

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('classement.index'));

        $this->assertAuthenticated();
        $user = User::where('email', 'nouveau@example.com')->firstOrFail();
        $this->assertDatabaseHas('comptes_lies', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'EXT-1',
        ]);
    }

    public function test_un_utilisateur_connecte_peut_lier_un_compte(): void
    {
        $user = User::factory()->create();
        $this->fakeSocialite('EXT-2', 'moi@example.com');

        $this->actingAs($user)
            ->withSession(['sso_intention' => 'lier'])
            ->get(route('social.callback', 'google'))
            ->assertRedirect(route('compte.securite'));

        $this->assertDatabaseHas('comptes_lies', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => 'EXT-2',
        ]);
    }

    public function test_lier_un_compte_deja_pris_est_refuse(): void
    {
        $autre = User::factory()->create();
        CompteLie::create(['user_id' => $autre->id, 'provider' => 'google', 'provider_id' => 'EXT-3']);

        $user = User::factory()->create();
        $this->fakeSocialite('EXT-3', 'moi@example.com');

        $this->actingAs($user)
            ->withSession(['sso_intention' => 'lier'])
            ->get(route('social.callback', 'google'))
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('comptes_lies', ['user_id' => $user->id, 'provider' => 'google']);
    }

    public function test_delier_le_seul_moyen_de_connexion_est_refuse(): void
    {
        $user = User::factory()->create(['password' => null]); // pas de mot de passe
        CompteLie::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'EXT-4']);

        $this->actingAs($user)
            ->from(route('compte.securite'))
            ->delete(route('compte.comptes.delier', 'google'))
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('comptes_lies', ['user_id' => $user->id, 'provider' => 'google']);
    }

    public function test_delier_fonctionne_si_un_mot_de_passe_existe(): void
    {
        $user = User::factory()->create(['password' => 'motdepasse123']);
        CompteLie::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'EXT-5']);

        $this->actingAs($user)
            ->from(route('compte.securite'))
            ->delete(route('compte.comptes.delier', 'google'))
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('comptes_lies', ['user_id' => $user->id, 'provider' => 'google']);
    }
}
