<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerificationCompteTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_lien_magique_verifie_l_email(): void
    {
        $token = 'jeton-verif';
        Cache::put('magic-login:'.$token, 'awa@example.com', now()->addMinutes(30));

        $this->get(route('magic.login', ['token' => $token]));

        $this->assertNotNull(User::where('email', 'awa@example.com')->value('email_verified_at'));
    }

    public function test_l_inscription_par_mot_de_passe_envoie_un_email_de_verification(): void
    {
        Notification::fake();

        $this->post('/inscription', ['email' => 'kof@example.com', 'password' => 'motdepasse123']);

        Notification::assertSentTo(
            User::where('email', 'kof@example.com')->firstOrFail(),
            VerifyEmail::class,
        );
    }

    public function test_le_lien_de_verification_marque_l_email_verifie(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('compte.securite'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_on_peut_renvoyer_l_email_de_verification(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_la_banniere_invite_a_verifier_l_email(): void
    {
        // Compte mot de passe non vérifié → bannière de vérification.
        $user = User::factory()->unverified()->create(['password' => 'motdepasse123']);
        $this->actingAs($user)->get('/')->assertSee('Vérifiez votre email');
    }

    public function test_la_page_securite_montre_l_email_non_verifie(): void
    {
        $user = User::factory()->unverified()->create(['password' => 'motdepasse123']);

        $this->actingAs($user)->get(route('compte.securite'))
            ->assertSee('Email non vérifié')
            ->assertSee('Renvoyer le lien');
    }

    public function test_la_page_securite_montre_l_email_verifie(): void
    {
        $user = User::factory()->create(['password' => 'motdepasse123']); // email_verified_at par défaut

        $this->actingAs($user)->get(route('compte.securite'))
            ->assertSee('Email vérifié')
            ->assertDontSee('Renvoyer le lien');
    }
}
