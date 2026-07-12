<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecuriteSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_login_est_bloque_apres_cinq_echecs(): void
    {
        User::factory()->create(['email' => 'cible@example.com', 'password' => 'bon-mot-de-passe']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/connexion', ['email' => 'cible@example.com', 'password' => 'faux'])
                ->assertSessionHasErrors('email');
        }

        // 6e tentative : bloquée, même avec le bon mot de passe.
        $this->post('/connexion', ['email' => 'cible@example.com', 'password' => 'bon-mot-de-passe'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString(
            'Trop de tentatives',
            session('errors')->first('email'),
        );
    }

    public function test_la_page_securite_est_reservee_aux_connectes(): void
    {
        $this->get(route('compte.securite'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('compte.securite'))
            ->assertOk()
            ->assertSee('Sécurité du compte');
    }

    public function test_la_deconnexion_des_autres_exige_le_bon_mot_de_passe(): void
    {
        $user = User::factory()->create(['password' => 'bon-mot-de-passe']);

        $this->actingAs($user)
            ->post(route('compte.securite.deconnecter-autres'), ['password' => 'faux'])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->from(route('compte.securite'))
            ->post(route('compte.securite.deconnecter-autres'), ['password' => 'bon-mot-de-passe'])
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('success');
    }
}
