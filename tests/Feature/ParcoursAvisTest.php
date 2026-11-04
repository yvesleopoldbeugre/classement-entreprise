<?php

namespace Tests\Feature;

use App\Enums\StatutEmploi;
use App\Enums\StatutEntreprise;
use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Parcours complet : un salarié note, l'admin publie, la note apparaît partout. */
class ParcoursAvisTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    private function notes(int $n): array
    {
        return ['note_ambiance' => $n, 'note_management' => $n, 'note_salaire' => $n, 'note_evolution' => $n];
    }

    public function test_le_formulaire_propose_des_pastilles_de_1_a_5_et_les_situations(): void
    {
        $entreprise = Entreprise::factory()->verifiee()->create();

        $page = $this->get(route('entreprises.show', $entreprise))->assertOk();

        foreach (['note_ambiance', 'note_management', 'note_salaire', 'note_evolution'] as $champ) {
            for ($n = 1; $n <= 5; $n++) {
                $page->assertSee('name="'.$champ.'" value="'.$n.'"', false);
            }
        }
        foreach (StatutEmploi::cases() as $statut) {
            $page->assertSee('name="statut_emploi" value="'.$statut->value.'"', false);
        }
    }

    public function test_de_l_avis_a_la_publication_puis_au_classement(): void
    {
        config(['classement.min_avis_classement' => 1, 'moderation.enabled' => true]);
        $entreprise = Entreprise::factory()->verifiee()->create(['nom' => 'Akwaba Tech', 'rang_a_eviter' => null]);
        $salarie = User::factory()->create(['pseudo_public' => 'Kouassi_dev']);

        // 1. Le salarié envoie son avis : il part en modération, rien n'est encore public.
        $this->actingAs($salarie)
            ->post(route('contrib.avis.store', $entreprise), [
                ...$this->notes(5),
                'entreprise_id' => $entreprise->id,
                'statut_emploi' => StatutEmploi::Actuel->value,
                'commentaire' => 'Équipe soudée et primes versées à l’heure.',
            ])
            ->assertRedirect();

        $avis = AvisEntreprise::firstOrFail();
        $this->assertSame(StatutModeration::EnAttente, $avis->statut_moderation);
        $this->get(route('entreprises.show', $entreprise))->assertDontSee('Équipe soudée');

        // 2. L'admin le voit dans la file de modération puis le publie.
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('moderation.index'))->assertOk()->assertSee('Équipe soudée');
        $this->actingAs($admin)->post(route('moderation.publier', ['avis', $avis->id]))->assertRedirect();

        // 3. La fiche affiche l'avis, la note et le rang ; l'accueil classe l'entreprise.
        auth()->logout();
        $this->get(route('entreprises.show', $entreprise))
            ->assertOk()
            ->assertSee('Équipe soudée')
            ->assertSee('Kouassi_dev')
            ->assertSee('5,0 / 5')
            ->assertSee('1er sur 1 entreprises')
            ->assertSee('Avis (1)');

        $this->get('/')
            ->assertOk()
            ->assertSee('Akwaba Tech')
            ->assertDontSee('Pas encore notée');
    }

    public function test_un_avis_incomplet_est_refuse_avec_les_erreurs(): void
    {
        $entreprise = Entreprise::factory()->verifiee()->create();

        $this->actingAs(User::factory()->create())
            ->from(route('entreprises.show', $entreprise))
            ->post(route('contrib.avis.store', $entreprise), ['entreprise_id' => $entreprise->id, 'note_ambiance' => 4])
            ->assertRedirect(route('entreprises.show', $entreprise))
            ->assertSessionHasErrors(['note_management', 'note_salaire', 'note_evolution', 'statut_emploi']);

        $this->assertSame(0, AvisEntreprise::count());
    }

    public function test_une_note_hors_echelle_est_refusee(): void
    {
        $entreprise = Entreprise::factory()->verifiee()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('contrib.avis.store', $entreprise), [
                ...$this->notes(6), 'entreprise_id' => $entreprise->id, 'statut_emploi' => StatutEmploi::Actuel->value,
            ])
            ->assertSessionHasErrors(['note_ambiance']);
    }

    public function test_une_fiche_non_verifiee_reste_consultable_mais_hors_listes(): void
    {
        $proposee = Entreprise::factory()->create(['nom' => 'Proposée SARL', 'statut' => StatutEntreprise::AVerifier, 'rang_a_eviter' => null]);

        $this->get(route('entreprises.show', $proposee))->assertOk()->assertSee('En cours de vérification');
        $this->get('/')->assertDontSee('Proposée SARL');
    }
}
