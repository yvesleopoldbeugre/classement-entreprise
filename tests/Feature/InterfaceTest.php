<?php

namespace Tests\Feature;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rendu de la nouvelle interface : accueil, fiche, navigation, espace admin. */
class InterfaceTest extends TestCase
{
    use RefreshDatabase;

    private function classee(string $nom, float $score, int $nb = 5, array $attributs = []): Entreprise
    {
        $e = Entreprise::factory()->create(['nom' => $nom, 'statut' => StatutEntreprise::Verifiee, 'rang_a_eviter' => null, ...$attributs]);
        $e->forceFill(['nb_avis_total' => $nb, 'score_bayesien' => $score, 'note_globale' => $score])->save();

        return $e;
    }

    public function test_l_accueil_classe_par_score_avec_anneau_et_appreciation(): void
    {
        $this->classee('Moyenne SA', 3.1);
        $this->classee('Top SARL', 4.5);

        $this->get('/')
            ->assertOk()
            ->assertSee('Avant de postuler, vérifiez la boîte.')
            ->assertSeeInOrder(['Top SARL', 'Moyenne SA'])
            ->assertSee('Excellent')
            ->assertSee('Assez bien')
            ->assertSee('4,5');
    }

    public function test_l_accueil_montre_les_apercus_lateraux_seulement_sur_le_classement(): void
    {
        Entreprise::factory()->create(['nom' => 'Signalée SA', 'rang_a_eviter' => 1]);
        Entreprise::factory()->create(['nom' => 'Toute Neuve', 'statut' => StatutEntreprise::Verifiee, 'rang_a_eviter' => null]);

        $this->get('/')->assertSee('À mieux connaître')->assertSee('Signalée SA')->assertSee('Nouvelles, sans avis')->assertSee('Toute Neuve');
        $this->get('/?vue=nouvelles')->assertDontSee('Nouvelles, sans avis');
    }

    public function test_la_recherche_et_le_secteur_filtrent_la_liste(): void
    {
        $this->classee('Akwaba Tech', 4.0, attributs: ['secteur_activite' => SecteurActivite::Startup]);
        $this->classee('Lagune Télécom', 4.0, attributs: ['secteur_activite' => SecteurActivite::Telecom]);

        $this->get('/?q=akwaba')->assertSee('Akwaba Tech')->assertDontSee('Lagune Télécom');
        $this->get('/?secteur=telecom')->assertSee('Lagune Télécom')->assertDontSee('Akwaba Tech');
    }

    public function test_une_requete_ajax_ne_renvoie_que_la_liste(): void
    {
        $this->classee('Akwaba Tech', 4.0);

        $reponse = $this->get('/?q=akwaba', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $reponse->assertSee('Akwaba Tech');
        $this->assertStringNotContainsString('<html', $reponse->getContent());
    }

    public function test_la_liste_est_paginee_par_douze(): void
    {
        foreach (range(1, 13) as $i) {
            $this->classee('Entreprise '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 4.9 - $i / 100);
        }

        $this->get('/')->assertSee('Entreprise 12')->assertDontSee('Entreprise 13');
        $this->get('/?page=2')->assertSee('Entreprise 13');
    }

    public function test_la_fiche_presente_onglets_score_et_actions(): void
    {
        $e = $this->classee('Akwaba Tech', 4.4, 12);

        $this->get(route('entreprises.show', $e))
            ->assertOk()
            ->assertSee('data-onglets', false)
            ->assertSee('Avis (0)')
            ->assertSee('Entretiens (0)')
            ->assertSee('Missions (0)')
            ->assertSee('Fiabilité : 12 avis')
            ->assertSee('Vous avez travaillé chez Akwaba Tech ?')
            ->assertSee('Donner mon avis')
            ->assertSee('bottom-24', false); // bulle du chat décalée au-dessus de la barre d'action mobile
    }

    public function test_une_fiche_sans_assez_d_avis_n_affiche_pas_de_note(): void
    {
        $e = Entreprise::factory()->verifiee()->create(['rang_a_eviter' => null]);
        $e->forceFill(['nb_avis_total' => 1, 'score_bayesien' => 4.9])->save();

        $this->get(route('entreprises.show', $e))
            ->assertSee('Pas encore notée')
            ->assertSee('1 avis sur les 3 nécessaires')
            ->assertDontSee('au classement');
    }

    public function test_le_menu_mobile_s_adapte_a_l_etat_de_connexion(): void
    {
        $this->get('/')->assertSee('id="menu-mobile"', false)->assertSee('S’inscrire')->assertDontSee('Mon compte');

        $user = User::factory()->create(['pseudo_public' => 'Aya.K']);
        $this->actingAs($user)->get('/')->assertSee('Mon compte')->assertSee('Aya.K')->assertDontSee('Administration');

        $user->forceFill(['is_admin' => true])->save();
        $this->actingAs($user)->get('/')->assertSee('Administration');
    }

    public function test_l_espace_admin_a_sa_barre_laterale_et_pas_l_en_tete_public(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get(route('moderation.index'))
            ->assertOk()
            ->assertSee('Administration')
            ->assertSee(route('admin.stats.index'), false)
            ->assertSee(route('admin.live.index'), false)
            ->assertDontSee('id="menu-mobile"', false);

        $this->actingAs($admin)->get('/')->assertSee('id="menu-mobile"', false);
    }

    public function test_connexion_et_inscription_proposent_le_sso_en_grille(): void
    {
        config(['services.google.client_id' => 'id-test', 'services.google.client_secret' => 'secret-test']);

        foreach (['login', 'register'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee('Les avis qui comptent, avant de signer.')
                ->assertSee('Continuer avec')
                ->assertSee(route('social.redirect', 'google'), false);
        }

        $this->get(route('login'))->assertSee('Recevoir un lien de connexion par email');
    }
}
