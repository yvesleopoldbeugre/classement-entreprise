<?php

namespace Tests\Feature;

use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_sitemap_liste_les_entreprises_publiques(): void
    {
        $entreprise = Entreprise::factory()->create(['statut' => StatutEntreprise::Verifiee]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee('<urlset', false);
        $response->assertSee(route('entreprises.show', $entreprise), false);
    }

    public function test_l_accueil_expose_le_schema_website(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('SearchAction', false);
    }

    public function test_la_fiche_expose_le_schema_avec_note_agregee(): void
    {
        $entreprise = Entreprise::factory()->create(['statut' => StatutEntreprise::Verifiee]);
        $entreprise->forceFill(['nb_avis_total' => 5, 'score_bayesien' => 4.2])->save();

        $this->get(route('entreprises.show', $entreprise))
            ->assertOk()
            ->assertSee('"@type":"AggregateRating"', false)
            ->assertSee('"ratingValue":"4.2"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_les_pages_publiques_sont_indexables(): void
    {
        $this->get('/')->assertSee('content="index, follow"', false);
    }

    public function test_les_recherches_sont_en_noindex(): void
    {
        $this->get('/?q=abidjan')->assertSee('content="noindex, follow"', false);
    }

    public function test_les_pages_privees_sont_en_noindex(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('admin.stats.index'))
            ->assertSee('content="noindex, nofollow"', false);
    }
}
