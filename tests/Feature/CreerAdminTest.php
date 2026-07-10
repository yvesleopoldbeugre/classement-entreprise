<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreerAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_commande_cree_un_compte_admin(): void
    {
        $this->artisan('admin:creer', [
            'email' => 'boss@example.com',
            '--name' => 'Le Boss',
            '--pseudo' => 'le_boss',
            '--password' => 'motdepasse123',
        ])->assertExitCode(0);

        $user = User::where('email', 'boss@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->is_admin);
    }

    public function test_la_commande_promeut_un_utilisateur_existant(): void
    {
        $user = User::factory()->create(['email' => 'membre@example.com', 'is_admin' => false]);

        $this->artisan('admin:creer', ['email' => 'membre@example.com'])->assertExitCode(0);

        $this->assertTrue($user->fresh()->is_admin);
    }
}
