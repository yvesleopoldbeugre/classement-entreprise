<?php

namespace Tests\Feature;

use App\Enums\StatutEmploi;
use App\Models\AvisEntreprise;
use App\Models\CompteLie;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Connexion et liaison par fournisseur (Google, LinkedIn, GitHub, Facebook).
 * Socialite est simulé : on vérifie tout le parcours côté application,
 * de la redirection au retour, sans appeler les vrais fournisseurs.
 */
class SsoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['google', 'github', 'facebook', 'linkedin-openid'] as $driver) {
            config(["services.{$driver}.client_id" => 'id-test', "services.{$driver}.client_secret" => 'secret-test']);
        }
    }

    /** Simule le retour d'un fournisseur avec ce profil (ou une erreur). */
    private function retourFournisseur(string $driver, array $profil = [], ?\Throwable $erreur = null): void
    {
        $oauth = Mockery::mock(SocialiteUser::class);
        $oauth->shouldReceive('getId')->andReturn($profil['id'] ?? 'EXT-1');
        $oauth->shouldReceive('getEmail')->andReturn(array_key_exists('email', $profil) ? $profil['email'] : 'awa@example.com');
        $oauth->shouldReceive('getName')->andReturn(array_key_exists('name', $profil) ? $profil['name'] : 'Awa Traoré');
        $oauth->shouldReceive('getNickname')->andReturn($profil['nickname'] ?? null);
        $oauth->shouldReceive('getAvatar')->andReturn($profil['avatar'] ?? null);

        Socialite::shouldReceive('driver')->with($driver)->andReturnUsing(function () use ($oauth, $erreur) {
            $fournisseur = Mockery::mock();
            $fournisseur->shouldReceive('redirectUrl')->andReturnSelf();
            $erreur
                ? $fournisseur->shouldReceive('user')->andThrow($erreur)
                : $fournisseur->shouldReceive('user')->andReturn($oauth);

            return $fournisseur;
        });
    }

    private function mettreUnAvisEnAttente(Entreprise $entreprise): void
    {
        $this->post(route('contrib.avis.store', $entreprise), [
            'entreprise_id' => $entreprise->id,
            'note_ambiance' => 4, 'note_management' => 4, 'note_salaire' => 3, 'note_evolution' => 5,
            'statut_emploi' => StatutEmploi::Actuel->value,
            'commentaire' => 'Rédigé avant de se connecter.',
        ])->assertSessionHas('avis_en_attente');
    }

    // ---------- Redirection vers le fournisseur ----------

    public function test_la_redirection_envoie_vers_le_fournisseur_avec_notre_url_de_retour(): void
    {
        $fournisseur = Mockery::mock();
        $fournisseur->shouldReceive('redirectUrl')->once()->with(route('social.callback', 'linkedin'))->andReturnSelf();
        $fournisseur->shouldReceive('redirect')->once()->andReturn(new RedirectResponse('https://www.linkedin.com/oauth/v2/authorization'));
        Socialite::shouldReceive('driver')->once()->with('linkedin-openid')->andReturn($fournisseur);

        $this->get(route('social.redirect', 'linkedin'))
            ->assertRedirect('https://www.linkedin.com/oauth/v2/authorization');
    }

    public function test_un_fournisseur_non_configure_renvoie_a_l_inscription(): void
    {
        config(['services.github.client_id' => null]);
        Socialite::shouldReceive('driver')->never();

        $this->get(route('social.redirect', 'github'))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('email');
    }

    public function test_un_fournisseur_inconnu_donne_404(): void
    {
        $this->get('/auth/twitter/redirect')->assertNotFound();
        $this->get('/auth/twitter/callback')->assertNotFound();
    }

    public function test_un_utilisateur_connecte_n_accede_pas_a_la_redirection_de_connexion(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('social.redirect', 'google'))
            ->assertRedirect();
        $this->assertAuthenticated();
    }

    public function test_seuls_les_fournisseurs_configures_sont_proposes(): void
    {
        config(['services.github.client_id' => null, 'services.facebook.client_secret' => null]);

        $this->get(route('login'))
            ->assertSee(route('social.redirect', 'google'), false)
            ->assertSee(route('social.redirect', 'linkedin'), false)
            ->assertDontSee(route('social.redirect', 'github'), false)
            ->assertDontSee(route('social.redirect', 'facebook'), false);
    }

    public function test_sans_fournisseur_configure_la_page_n_affiche_pas_le_bloc_sso(): void
    {
        foreach (['google', 'github', 'facebook', 'linkedin-openid'] as $driver) {
            config(["services.{$driver}.client_id" => null]);
        }

        $this->get(route('login'))->assertOk()->assertDontSee('Continuer avec');
        $this->get(route('register'))->assertOk()->assertDontSee('Continuer avec');
    }

    // ---------- Retour du fournisseur : connexion ----------

    public function test_le_retour_cree_le_compte_verifie_et_connecte(): void
    {
        $this->retourFournisseur('google', ['id' => 'G-1', 'email' => 'awa@example.com', 'name' => 'Awa Traoré', 'avatar' => 'https://exemple/avatar.png']);

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('classement.index'))
            ->assertSessionHas('success', 'Connecté via Google.');

        $user = User::where('email', 'awa@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertFalse((bool) $user->linkedin_verifie);
        $this->assertSame('awa_traore', $user->pseudo_public);
        $this->assertDatabaseHas('comptes_lies', ['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'G-1', 'avatar' => 'https://exemple/avatar.png']);
    }

    public function test_linkedin_marque_le_compte_comme_verifie(): void
    {
        $this->retourFournisseur('linkedin-openid', ['id' => 'L-1', 'email' => 'pro@example.com']);

        $this->get(route('social.callback', 'linkedin'))->assertRedirect(route('classement.index'));

        $user = User::where('email', 'pro@example.com')->firstOrFail();
        $this->assertTrue((bool) $user->linkedin_verifie);
        $this->assertDatabaseHas('comptes_lies', ['user_id' => $user->id, 'provider' => 'linkedin']);
    }

    public function test_un_compte_deja_lie_reconnecte_le_meme_utilisateur(): void
    {
        $user = User::factory()->create(['email' => 'ancien@example.com']);
        CompteLie::create(['user_id' => $user->id, 'provider' => 'github', 'provider_id' => 'GH-9']);
        // L'email renvoyé par le fournisseur a changé : c'est l'identifiant qui compte.
        $this->retourFournisseur('github', ['id' => 'GH-9', 'email' => 'nouvelle-adresse@example.com']);

        $this->get(route('social.callback', 'github'))->assertRedirect(route('classement.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_un_email_deja_inscrit_rattache_le_fournisseur_sans_doublon(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'awa@example.com', 'password' => 'motdepasse123']);
        $this->retourFournisseur('facebook', ['id' => 'FB-1', 'email' => 'awa@example.com']);

        $this->get(route('social.callback', 'facebook'))->assertRedirect(route('classement.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseHas('comptes_lies', ['user_id' => $user->id, 'provider' => 'facebook', 'provider_id' => 'FB-1']);
    }

    public function test_un_profil_sans_email_recoit_une_adresse_technique(): void
    {
        $this->retourFournisseur('github', ['id' => 'GH-42', 'email' => null, 'name' => null, 'nickname' => 'kouassi-dev']);

        $this->get(route('social.callback', 'github'))->assertRedirect(route('classement.index'));

        $user = User::where('email', 'github_GH-42@sso.local')->firstOrFail();
        $this->assertSame('kouassi_dev', $user->pseudo_public);
        $this->assertSame('kouassi-dev', $user->name);
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_le_pseudo_genere_reste_unique(): void
    {
        User::factory()->create(['pseudo_public' => 'awa_traore']);
        User::factory()->create(['pseudo_public' => 'awa_traore_2']);
        $this->retourFournisseur('google', ['id' => 'G-2', 'email' => 'awa2@example.com', 'name' => 'Awa Traoré']);

        $this->get(route('social.callback', 'google'));

        $this->assertSame('awa_traore_3', User::where('email', 'awa2@example.com')->value('pseudo_public'));
    }

    public function test_un_refus_ou_une_erreur_du_fournisseur_ramene_a_la_connexion(): void
    {
        $this->retourFournisseur('google', erreur: new RuntimeException('access_denied'));

        $this->get(route('social.callback', 'google').'?error=access_denied')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', 'Échec de la connexion via google. Réessayez.');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_un_retour_sur_un_fournisseur_non_configure_est_refuse(): void
    {
        config(['services.google.client_secret' => null]);
        Socialite::shouldReceive('driver')->never();

        $this->get(route('social.callback', 'google'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    // ---------- Flux « avis d'abord » ----------

    public function test_l_avis_redige_avant_le_sso_est_enregistre_apres_la_connexion(): void
    {
        $entreprise = Entreprise::factory()->verifiee()->create();
        $this->mettreUnAvisEnAttente($entreprise);
        $this->retourFournisseur('linkedin-openid', ['id' => 'L-7', 'email' => 'salarie@example.com']);

        $this->get(route('social.callback', 'linkedin'))
            ->assertRedirect(route('entreprises.show', $entreprise))
            ->assertSessionMissing('avis_en_attente');

        $avis = AvisEntreprise::firstOrFail();
        $this->assertSame(User::where('email', 'salarie@example.com')->value('id'), $avis->user_id);
        $this->assertSame($entreprise->id, $avis->entreprise_id);
        $this->assertSame('Rédigé avant de se connecter.', $avis->commentaire);
    }

    public function test_l_avis_n_est_pas_duplique_si_le_compte_a_deja_note_l_entreprise(): void
    {
        $entreprise = Entreprise::factory()->verifiee()->create();
        $user = User::factory()->create(['email' => 'salarie@example.com']);
        AvisEntreprise::factory()->create(['entreprise_id' => $entreprise->id, 'user_id' => $user->id]);
        $this->mettreUnAvisEnAttente($entreprise);
        $this->retourFournisseur('google', ['id' => 'G-8', 'email' => 'salarie@example.com']);

        $this->get(route('social.callback', 'google'))->assertRedirect(route('entreprises.show', $entreprise));

        $this->assertSame(1, AvisEntreprise::count());
    }

    public function test_l_avis_redige_avant_le_lien_magique_est_enregistre(): void
    {
        $entreprise = Entreprise::factory()->verifiee()->create();
        $this->mettreUnAvisEnAttente($entreprise);
        Cache::put('magic-login:jeton-test', 'magique@example.com', now()->addMinutes(30));

        $this->get(route('magic.login', 'jeton-test'))->assertRedirect(route('entreprises.show', $entreprise));

        $this->assertSame(User::where('email', 'magique@example.com')->value('id'), AvisEntreprise::firstOrFail()->user_id);
    }

    // ---------- Liaison depuis « Mon compte » ----------

    public function test_connecter_un_fournisseur_memorise_l_intention_de_liaison(): void
    {
        $fournisseur = Mockery::mock();
        $fournisseur->shouldReceive('redirectUrl')->andReturnSelf();
        $fournisseur->shouldReceive('redirect')->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($fournisseur);

        $this->actingAs(User::factory()->create())
            ->get(route('compte.comptes.connecter', 'google'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth')
            ->assertSessionHas('sso_intention', 'lier');
    }

    public function test_connecter_un_fournisseur_non_configure_affiche_une_erreur(): void
    {
        config(['services.facebook.client_id' => null]);

        $this->actingAs(User::factory()->create())
            ->get(route('compte.comptes.connecter', 'facebook'))
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('error');
    }

    public function test_la_liaison_de_linkedin_rend_le_compte_verifie_sans_changer_d_utilisateur(): void
    {
        $user = User::factory()->create(['linkedin_verifie' => false]);
        $this->retourFournisseur('linkedin-openid', ['id' => 'L-3', 'email' => 'autre-adresse@example.com']);

        $this->actingAs($user)->withSession(['sso_intention' => 'lier'])
            ->get(route('social.callback', 'linkedin'))
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($user);
        $this->assertTrue((bool) $user->fresh()->linkedin_verifie);
        $this->assertSame(1, User::count());
    }

    public function test_relier_un_fournisseur_deja_lie_au_meme_compte_ne_fait_rien(): void
    {
        $user = User::factory()->create();
        CompteLie::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'G-5']);
        $this->retourFournisseur('google', ['id' => 'G-5']);

        $this->actingAs($user)->withSession(['sso_intention' => 'lier'])
            ->get(route('social.callback', 'google'))
            ->assertSessionHas('info');

        $this->assertSame(1, CompteLie::count());
    }

    public function test_une_erreur_pendant_la_liaison_ramene_a_mon_compte(): void
    {
        $user = User::factory()->create();
        $this->retourFournisseur('google', erreur: new RuntimeException('invalid state'));

        $this->actingAs($user)->withSession(['sso_intention' => 'lier'])
            ->get(route('social.callback', 'google'))
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('error');

        $this->assertSame(0, CompteLie::count());
    }

    public function test_delier_un_fournisseur_absent_previent_l_utilisateur(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('compte.securite'))
            ->delete(route('compte.comptes.delier', 'linkedin'))
            ->assertRedirect(route('compte.securite'))
            ->assertSessionHas('info');
    }

    public function test_la_page_compte_liste_les_fournisseurs_et_leur_etat(): void
    {
        $user = User::factory()->create();
        CompteLie::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'G-6']);

        $this->actingAs($user)->get(route('compte.securite'))
            ->assertOk()
            ->assertSee('Comptes connectés')
            ->assertSee(route('compte.comptes.delier', 'google'), false)
            ->assertSee(route('compte.comptes.connecter', 'linkedin'), false);
    }
}
