<?php

namespace Tests\Feature;

use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatistiquesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_une_visite_de_page_est_enregistree(): void
    {
        $this->get('/')->assertOk();

        $this->assertDatabaseHas('evenements', ['type' => 'visite']);
    }

    public function test_les_pages_admin_ne_comptent_pas_comme_visite(): void
    {
        $this->actingAs($this->admin())->get(route('admin.stats.index'))->assertOk();

        $this->assertDatabaseMissing('evenements', ['type' => 'visite']);
    }

    public function test_le_depot_d_un_avis_enregistre_un_evenement(): void
    {
        $entreprise = Entreprise::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('contrib.avis.store', $entreprise), [
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => 4,
            'note_management' => 5,
            'note_salaire' => 3,
            'note_evolution' => 4,
            'statut_emploi' => 'ancien',
            'commentaire' => 'Bonne expérience globale.',
        ])->assertRedirect(route('entreprises.show', $entreprise));

        $this->assertDatabaseHas('evenements', [
            'type' => 'avis',
            'user_id' => $user->id,
            'sujet_type' => AvisEntreprise::class,
        ]);
    }

    public function test_l_inscription_enregistre_un_evenement(): void
    {
        $this->post('/inscription', [
            'name' => 'Awa Koné',
            'pseudo_public' => 'awa_stats',
            'email' => 'awa.stats@example.com',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertRedirect(route('classement.index'));

        $this->assertDatabaseHas('evenements', ['type' => 'inscription']);
    }

    public function test_la_page_stats_est_reservee_aux_admins(): void
    {
        // Invité → redirigé vers la connexion.
        $this->get(route('admin.stats.index'))->assertRedirect(route('login'));

        // Utilisateur non-admin → interdit.
        $this->actingAs(User::factory()->create())
            ->get(route('admin.stats.index'))->assertForbidden();
    }

    public function test_un_admin_voit_la_page_stats(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.stats.index'))
            ->assertOk()
            ->assertSee('Visiteurs uniques')
            ->assertSee('graphe-usage', false);
    }
}
