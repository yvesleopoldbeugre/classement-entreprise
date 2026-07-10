<?php

namespace Tests\Feature;

use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassementVueTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_entreprise_verifiee_sans_avis_apparait_dans_le_classement_et_les_nouvelles(): void
    {
        Entreprise::factory()->create([
            'nom' => 'Vérifiée Sans Avis',
            'statut' => StatutEntreprise::Verifiee,
        ]);

        // Vue classement : présente, avec la mention « Nouveau ».
        $this->get('/?vue=classement')->assertOk()->assertSee('Vérifiée Sans Avis')->assertSee('Nouveau');

        // Vue nouvelles : présente.
        $this->get('/?vue=nouvelles')->assertOk()->assertSee('Vérifiée Sans Avis');

        // Vue par défaut (à éviter) : absente (elle n'est pas dans les pires).
        $this->get('/')->assertOk()->assertDontSee('Vérifiée Sans Avis');
    }

    public function test_une_proposition_non_verifiee_n_apparait_pas_dans_le_classement(): void
    {
        Entreprise::factory()->create([
            'nom' => 'En Attente SARL',
            'statut' => StatutEntreprise::AVerifier,
        ]);

        $this->get('/?vue=classement')->assertOk()->assertDontSee('En Attente SARL');
        $this->get('/?vue=nouvelles')->assertOk()->assertDontSee('En Attente SARL');
    }
}
