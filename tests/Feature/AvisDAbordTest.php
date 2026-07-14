<?php

namespace Tests\Feature;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisDAbordTest extends TestCase
{
    use RefreshDatabase;

    private function donneesAvis(Entreprise $entreprise): array
    {
        return [
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => 4,
            'note_management' => 3,
            'note_salaire' => 2,
            'note_evolution' => 4,
            'statut_emploi' => 'ancien',
            'commentaire' => 'Expérience mitigée.',
        ];
    }

    public function test_un_invite_peut_soumettre_un_avis_et_est_invite_a_creer_un_compte(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->post(route('contrib.avis.store', $entreprise), $this->donneesAvis($entreprise))
            ->assertRedirect(route('entreprises.show', $entreprise))
            ->assertSessionHas('avis_en_attente');

        // L'avis n'est pas encore publié : il attend la création du compte.
        $this->assertDatabaseCount('avis_entreprises', 0);
    }

    public function test_l_avis_en_attente_est_publie_a_la_creation_du_compte(): void
    {
        $entreprise = Entreprise::factory()->create();

        // 1. Invité : remplit et envoie l'avis.
        $this->post(route('contrib.avis.store', $entreprise), $this->donneesAvis($entreprise));

        // 2. Crée son compte (email + mot de passe) → l'avis est publié et on revient sur la fiche.
        $this->post('/inscription', ['email' => 'awa@example.com', 'password' => 'motdepasse123'])
            ->assertRedirect(route('entreprises.show', $entreprise));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('avis_entreprises', [
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => 4,
            'user_id' => User::where('email', 'awa@example.com')->value('id'),
        ]);
    }

    public function test_l_avis_en_attente_est_publie_a_la_connexion(): void
    {
        $entreprise = Entreprise::factory()->create();
        $user = User::factory()->create(['email' => 'connu@example.com', 'password' => 'motdepasse123']);

        $this->post(route('contrib.avis.store', $entreprise), $this->donneesAvis($entreprise));

        $this->post('/connexion', ['email' => 'connu@example.com', 'password' => 'motdepasse123'])
            ->assertRedirect(route('entreprises.show', $entreprise));

        $this->assertDatabaseHas('avis_entreprises', [
            'entreprise_id' => $entreprise->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_un_avis_invalide_ne_cree_rien(): void
    {
        $entreprise = Entreprise::factory()->create();

        $this->post(route('contrib.avis.store', $entreprise), [
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => 9, // hors bornes
        ])->assertSessionHasErrors('note_ambiance');

        $this->assertNull(session('avis_en_attente'));
        $this->assertDatabaseCount('avis_entreprises', 0);
    }
}
