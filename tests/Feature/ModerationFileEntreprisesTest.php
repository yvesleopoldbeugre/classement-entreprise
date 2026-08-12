<?php

namespace Tests\Feature;

use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationFileEntreprisesTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_entrees_editoriales_a_eviter_ne_sont_pas_dans_la_file_de_moderation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Proposition d'un utilisateur → doit apparaître dans la file.
        $proposee = Entreprise::factory()->create([
            'statut' => StatutEntreprise::AVerifier,
            'rang_a_eviter' => null,
        ]);

        // Entrée éditoriale « à éviter » (publique) → ne doit PAS encombrer la file.
        $editoriale = Entreprise::factory()->create([
            'statut' => StatutEntreprise::AVerifier,
            'rang_a_eviter' => 3,
        ]);

        $response = $this->actingAs($admin)->get(route('moderation.index'));

        $response->assertOk();
        $entreprises = $response->viewData('entreprises');

        $this->assertTrue($entreprises->contains('id', $proposee->id));
        $this->assertFalse($entreprises->contains('id', $editoriale->id));
    }
}
