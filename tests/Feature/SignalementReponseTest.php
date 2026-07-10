<?php

namespace Tests\Feature;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignalementReponseTest extends TestCase
{
    use RefreshDatabase;

    private function avisPublie(Entreprise $entreprise, User $auteur): AvisEntreprise
    {
        return AvisEntreprise::factory()->create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $auteur->id,
            'statut_moderation' => StatutModeration::Publie,
        ]);
    }

    public function test_un_utilisateur_peut_signaler_un_avis(): void
    {
        $entreprise = Entreprise::factory()->create();
        $avis = $this->avisPublie($entreprise, User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post(route('signaler', ['avis', $avis->id]))
            ->assertRedirect();

        $this->assertSame(StatutModeration::Signale->value, $avis->fresh()->statut_moderation->value);
    }

    public function test_on_ne_peut_pas_signaler_son_propre_avis(): void
    {
        $entreprise = Entreprise::factory()->create();
        $auteur = User::factory()->create();
        $avis = $this->avisPublie($entreprise, $auteur);

        $this->actingAs($auteur)
            ->post(route('signaler', ['avis', $avis->id]))
            ->assertForbidden();

        $this->assertSame(StatutModeration::Publie->value, $avis->fresh()->statut_moderation->value);
    }

    public function test_un_admin_enregistre_le_droit_de_reponse(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $entreprise = Entreprise::factory()->create();

        $this->actingAs($admin)
            ->put(route('entreprises.reponse', $entreprise), [
                'reponse_entreprise' => 'Nous prenons ces retours au sérieux.',
            ])
            ->assertRedirect();

        $entreprise->refresh();
        $this->assertSame('Nous prenons ces retours au sérieux.', $entreprise->reponse_entreprise);
        $this->assertNotNull($entreprise->reponse_entreprise_le);
    }

    public function test_un_non_admin_ne_peut_pas_repondre(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->put(route('entreprises.reponse', $entreprise), ['reponse_entreprise' => 'x'])
            ->assertForbidden();
    }
}
