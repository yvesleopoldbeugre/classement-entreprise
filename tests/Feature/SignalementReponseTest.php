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

    private function signaler(User $user, AvisEntreprise $avis): void
    {
        $this->actingAs($user)->post(route('signaler', ['avis', $avis->id]))->assertRedirect();
    }

    public function test_un_signalement_sous_le_seuil_enregistre_sans_masquer(): void
    {
        config(['moderation.seuil_signalements' => 3]);
        $avis = $this->avisPublie(Entreprise::factory()->create(), User::factory()->create());

        $this->signaler(User::factory()->create(), $avis);

        $this->assertSame(1, $avis->signalements()->count());
        $this->assertSame(StatutModeration::Publie->value, $avis->fresh()->statut_moderation->value);
    }

    public function test_au_seuil_l_avis_passe_en_signale(): void
    {
        config(['moderation.seuil_signalements' => 3]);
        $avis = $this->avisPublie(Entreprise::factory()->create(), User::factory()->create());

        foreach (range(1, 3) as $ignore) {
            $this->signaler(User::factory()->create(), $avis);
        }

        $this->assertSame(StatutModeration::Signale->value, $avis->fresh()->statut_moderation->value);
    }

    public function test_un_meme_utilisateur_ne_compte_qu_une_fois(): void
    {
        config(['moderation.seuil_signalements' => 3]);
        $avis = $this->avisPublie(Entreprise::factory()->create(), User::factory()->create());
        $rapporteur = User::factory()->create();

        $this->signaler($rapporteur, $avis);
        $this->signaler($rapporteur, $avis);

        $this->assertSame(1, $avis->signalements()->count());
        $this->assertSame(StatutModeration::Publie->value, $avis->fresh()->statut_moderation->value);
    }

    public function test_le_motif_du_signalement_est_enregistre(): void
    {
        $avis = $this->avisPublie(Entreprise::factory()->create(), User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->post(route('signaler', ['avis', $avis->id]), ['motif' => 'Spam ou publicité'])
            ->assertRedirect();

        $this->assertDatabaseHas('signalements', [
            'signalable_type' => AvisEntreprise::class,
            'signalable_id' => $avis->id,
            'motif' => 'Spam ou publicité',
        ]);
    }

    public function test_on_ne_peut_pas_signaler_son_propre_avis(): void
    {
        $auteur = User::factory()->create();
        $avis = $this->avisPublie(Entreprise::factory()->create(), $auteur);

        $this->actingAs($auteur)
            ->post(route('signaler', ['avis', $avis->id]))
            ->assertForbidden();

        $this->assertSame(0, $avis->signalements()->count());
    }

    public function test_publier_en_moderation_remet_les_signalements_a_zero(): void
    {
        config(['moderation.seuil_signalements' => 1]);
        $avis = $this->avisPublie(Entreprise::factory()->create(), User::factory()->create());
        $this->signaler(User::factory()->create(), $avis); // seuil 1 → passe en signale

        $this->assertSame(StatutModeration::Signale->value, $avis->fresh()->statut_moderation->value);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->post(route('moderation.publier', ['avis', $avis->id]))->assertRedirect();

        $this->assertSame(StatutModeration::Publie->value, $avis->fresh()->statut_moderation->value);
        $this->assertSame(0, $avis->signalements()->count());
    }

    public function test_un_avis_signale_sous_le_seuil_apparait_en_moderation(): void
    {
        config(['moderation.seuil_signalements' => 3]);
        $entreprise = Entreprise::factory()->create(['nom' => 'Entreprise Signalée SARL']);
        $avis = $this->avisPublie($entreprise, User::factory()->create());
        $this->signaler(User::factory()->create(), $avis); // 1 signalement : reste publie

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('moderation.index'))
            ->assertOk()
            ->assertSee('Entreprise Signalée SARL');
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
