<?php

namespace Tests\Feature;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContributionModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inscription_cree_le_compte_et_authentifie(): void
    {
        $response = $this->post('/inscription', [
            'name' => 'Awa Koné',
            'pseudo_public' => 'awa_k',
            'email' => 'awa@example.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ]);

        $response->assertRedirect(route('classement.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'awa@example.com', 'pseudo_public' => 'awa_k']);
    }

    public function test_un_utilisateur_authentifie_depose_un_avis_en_attente(): void
    {
        $entreprise = Entreprise::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('contrib.avis.store', $entreprise), [
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => 4,
            'note_management' => 5,
            'note_salaire' => 3,
            'note_evolution' => 4,
            'statut_emploi' => 'ancien',
            'commentaire' => 'Bonne expérience globale.',
        ]);

        $response->assertRedirect(route('entreprises.show', $entreprise));
        $this->assertDatabaseHas('avis_entreprises', [
            'entreprise_id' => $entreprise->id,
            'user_id' => $user->id,
            'statut_moderation' => StatutModeration::EnAttente->value,
        ]);
    }

    public function test_moderation_desactivee_publie_immediatement(): void
    {
        config(['moderation.enabled' => false]);

        $entreprise = Entreprise::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('contrib.avis.store', $entreprise), [
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => 4,
            'note_management' => 4,
            'note_salaire' => 4,
            'note_evolution' => 4,
            'statut_emploi' => 'ancien',
        ])->assertRedirect();

        $this->assertDatabaseHas('avis_entreprises', [
            'entreprise_id' => $entreprise->id,
            'user_id' => $user->id,
            'statut_moderation' => StatutModeration::Publie->value,
        ]);
    }

    public function test_un_visiteur_ne_peut_pas_deposer_un_avis(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->post(route('contrib.avis.store', $entreprise), [])
            ->assertRedirect(route('login'));
    }

    public function test_la_moderation_est_interdite_aux_non_admins(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('moderation.index'))->assertForbidden();
    }

    public function test_un_admin_publie_un_avis_et_le_score_est_recalcule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $entreprise = Entreprise::factory()->create();
        $avis = AvisEntreprise::factory()->enAttente()->create(['entreprise_id' => $entreprise->id]);

        $this->assertNull($entreprise->fresh()->score_bayesien);

        $this->actingAs($admin)
            ->post(route('moderation.publier', ['avis', $avis->id]))
            ->assertRedirect();

        $this->assertSame(StatutModeration::Publie->value, $avis->fresh()->statut_moderation->value);
        // L'observer a recalculé le score dénormalisé de l'entreprise.
        $this->assertSame(1, $entreprise->fresh()->nb_avis_total);
        $this->assertNotNull($entreprise->fresh()->score_bayesien);
    }
}
