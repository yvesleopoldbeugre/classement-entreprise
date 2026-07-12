<?php

namespace Tests\Feature;

use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UtilisateursAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_la_liste_est_reservee_aux_admins(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_l_admin_voit_la_liste_des_utilisateurs(): void
    {
        $membre = User::factory()->create(['pseudo_public' => 'kouassi_dev']);
        $entreprise = Entreprise::factory()->create();
        AvisEntreprise::factory()->create([
            'user_id' => $membre->id,
            'entreprise_id' => $entreprise->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('kouassi_dev')
            ->assertSee('graphe-comparatif', false);
    }

    public function test_la_fiche_montre_les_actions_de_l_utilisateur(): void
    {
        $membre = User::factory()->create(['pseudo_public' => 'awa_prod']);
        $entreprise = Entreprise::factory()->create(['nom' => 'Neurones Technologiques']);
        AvisEntreprise::factory()->create([
            'user_id' => $membre->id,
            'entreprise_id' => $entreprise->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.users.show', $membre))
            ->assertOk()
            ->assertSee('awa_prod')
            ->assertSee('Dernières actions')
            ->assertSee('Neurones Technologiques') // sujet de l'action « Avis »
            ->assertSee('graphe-utilisateur', false);
    }

    public function test_la_fiche_est_reservee_aux_admins(): void
    {
        $membre = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.users.show', $membre))->assertForbidden();
    }
}
