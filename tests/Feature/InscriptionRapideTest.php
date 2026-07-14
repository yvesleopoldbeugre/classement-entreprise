<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscriptionRapideTest extends TestCase
{
    use RefreshDatabase;

    public function test_inscription_minimale_email_et_mot_de_passe(): void
    {
        $this->post('/inscription', [
            'email' => 'kouassi@example.com',
            'password' => 'motdepasse123',
        ])->assertRedirect(route('classement.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'kouassi@example.com']);
    }

    public function test_le_pseudo_est_auto_genere_depuis_l_email(): void
    {
        $this->post('/inscription', [
            'email' => 'kouassi@example.com',
            'password' => 'motdepasse123',
        ]);

        $user = User::where('email', 'kouassi@example.com')->firstOrFail();
        $this->assertSame('kouassi', $user->pseudo_public);
        $this->assertNotNull($user->name);
    }

    public function test_le_pseudo_auto_genere_reste_unique(): void
    {
        User::factory()->create(['pseudo_public' => 'kouassi']);

        $this->post('/inscription', [
            'email' => 'kouassi@example.com',
            'password' => 'motdepasse123',
        ]);

        $user = User::where('email', 'kouassi@example.com')->firstOrFail();
        $this->assertSame('kouassi_2', $user->pseudo_public);
    }

    public function test_inscription_ajax_renvoie_du_json(): void
    {
        $this->postJson('/inscription', [
            'email' => 'ajax@example.com',
            'password' => 'motdepasse123',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertAuthenticated();
    }
}
