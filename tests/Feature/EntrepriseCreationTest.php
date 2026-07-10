<?php

namespace Tests\Feature;

use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntrepriseCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_propose_une_entreprise_en_verification(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('entreprises.proposer'), [
                'nom' => 'Nouvelle Boîte SARL',
                'secteur_activite' => 'ssii',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('entreprises', [
            'nom' => 'Nouvelle Boîte SARL',
            'statut' => StatutEntreprise::AVerifier->value,
            'source_scraping' => 'utilisateur',
        ]);
    }

    public function test_un_admin_ajoute_une_entreprise_directement_verifiee(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('entreprises.proposer'), [
                'nom' => 'Admin Corp',
                'secteur_activite' => 'startup',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('entreprises', [
            'nom' => 'Admin Corp',
            'statut' => StatutEntreprise::Verifiee->value,
        ]);
    }

    public function test_un_utilisateur_ne_peut_pas_forcer_le_statut_verifie(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('entreprises.proposer'), [
                'nom' => 'Tentative Corp',
                'secteur_activite' => 'autre',
                'statut' => 'verifiee', // doit être ignoré
            ])
            ->assertRedirect();

        $this->assertSame(
            StatutEntreprise::AVerifier->value,
            Entreprise::where('nom', 'Tentative Corp')->first()->statut->value,
        );
    }

    public function test_un_visiteur_est_redirige_vers_la_connexion(): void
    {
        $this->post(route('entreprises.proposer'), ['nom' => 'X', 'secteur_activite' => 'autre'])
            ->assertRedirect(route('login'));
    }

    public function test_un_admin_verifie_une_entreprise_proposee(): void
    {
        $entreprise = Entreprise::factory()->create(['statut' => StatutEntreprise::AVerifier]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->put(route('moderation.entreprise.verifier', $entreprise))
            ->assertRedirect();

        $this->assertSame(StatutEntreprise::Verifiee->value, $entreprise->fresh()->statut->value);
    }

    public function test_un_non_admin_ne_peut_pas_verifier(): void
    {
        $entreprise = Entreprise::factory()->create(['statut' => StatutEntreprise::AVerifier]);

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->put(route('moderation.entreprise.verifier', $entreprise))
            ->assertForbidden();
    }
}
