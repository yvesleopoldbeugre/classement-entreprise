<?php

namespace Tests\Feature;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesCategorieTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_secteur_liste_les_entreprises_verifiees_du_secteur(): void
    {
        $ssii = Entreprise::factory()->verifiee()->create(['nom' => 'Acme ESN', 'secteur_activite' => SecteurActivite::Ssii]);
        Entreprise::factory()->verifiee()->create(['nom' => 'Beta Telecom', 'secteur_activite' => SecteurActivite::Telecom]);
        Entreprise::factory()->create(['nom' => 'Gamma Proposée', 'secteur_activite' => SecteurActivite::Ssii, 'statut' => StatutEntreprise::AVerifier]);

        $this->get('/secteur/ssii')
            ->assertOk()
            ->assertSee('Meilleures entreprises SSII / ESN en Côte d’Ivoire')
            ->assertSee($ssii->nom)
            ->assertDontSee('Beta Telecom')
            ->assertDontSee('Gamma Proposée')
            ->assertSee('content="index, follow"', false);
    }

    public function test_le_slug_de_secteur_accepte_les_tirets(): void
    {
        $this->get('/secteur/banque-dsi')->assertOk()->assertSee('content="noindex, follow"', false);
        $this->get('/secteur/inconnu')->assertNotFound();
    }

    public function test_la_page_commune(): void
    {
        $e = Entreprise::factory()->verifiee()->create(['nom' => 'Acme Cocody', 'commune' => 'Cocody']);
        Entreprise::factory()->verifiee()->create(['nom' => 'Beta Plateau', 'commune' => 'Plateau']);

        $this->get('/commune/cocody')
            ->assertOk()
            ->assertSee('Entreprises où travailler à Cocody')
            ->assertSee($e->nom)
            ->assertDontSee('Beta Plateau');
        $this->get('/commune/nulle-part')->assertNotFound();
    }

    public function test_le_sitemap_inclut_secteurs_et_communes(): void
    {
        Entreprise::factory()->verifiee()->create(['secteur_activite' => SecteurActivite::Startup, 'commune' => 'Marcory']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('classement.secteur', 'startup'), false)
            ->assertSee(route('classement.commune', 'marcory'), false)
            ->assertDontSee(route('classement.secteur', 'telecom'), false);
    }

    public function test_une_fiche_non_verifiee_est_en_noindex(): void
    {
        $proposee = Entreprise::factory()->create(['statut' => StatutEntreprise::AVerifier, 'rang_a_eviter' => null]);
        $verifiee = Entreprise::factory()->verifiee()->create(['description' => 'Intégrateur réseau.']);

        $this->get(route('entreprises.show', $proposee))->assertSee('content="noindex, follow"', false);
        $this->get(route('entreprises.show', $verifiee))
            ->assertSee('content="index, follow"', false)
            ->assertSee('Intégrateur réseau.')
            ->assertSee(route('classement.secteur', $verifiee->secteur_activite->slug()), false);
    }
}
