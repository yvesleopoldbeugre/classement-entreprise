<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecuriteSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_compte_sans_mot_de_passe_peut_en_definir_un(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)
            ->from(route('compte.securite'))
            ->post(route('compte.securite.mot-de-passe'), [
                'password' => 'nouveau-mdp-123',
                'password_confirmation' => 'nouveau-mdp-123',
            ])
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('nouveau-mdp-123', $user->fresh()->password));
    }

    public function test_changer_le_mot_de_passe_exige_le_bon_mot_de_passe_actuel(): void
    {
        $user = User::factory()->create(['password' => 'ancien-mdp-123']);

        // Mauvais mot de passe actuel → erreur (bag « motDePasse »).
        $this->actingAs($user)
            ->from(route('compte.securite'))
            ->post(route('compte.securite.mot-de-passe'), [
                'current_password' => 'faux',
                'password' => 'nouveau-mdp-123',
                'password_confirmation' => 'nouveau-mdp-123',
            ])
            ->assertSessionHasErrors('current_password', null, 'motDePasse');

        // Bon mot de passe actuel → mise à jour.
        $this->actingAs($user)
            ->from(route('compte.securite'))
            ->post(route('compte.securite.mot-de-passe'), [
                'current_password' => 'ancien-mdp-123',
                'password' => 'nouveau-mdp-123',
                'password_confirmation' => 'nouveau-mdp-123',
            ])
            ->assertRedirect(route('compte.securite'));

        $this->assertTrue(Hash::check('nouveau-mdp-123', $user->fresh()->password));
    }

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
