<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_l_import_prefill_le_nom_et_la_description(): void
    {
        Http::fake(['*' => Http::response(
            '<html><head><title>Acme SARL | Accueil</title>'
            .'<meta name="description" content="Leader du BTP en Côte d’Ivoire."></head></html>',
            200,
        )]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('entreprises.importer-site'), ['url' => 'https://acme.ci'])
            ->assertOk()
            ->assertJson([
                'nom' => 'Acme SARL',
                'commentaire' => 'Leader du BTP en Côte d’Ivoire.',
                'site_web' => 'https://acme.ci',
            ]);
    }

    public function test_un_site_injoignable_renvoie_une_erreur(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('entreprises.importer-site'), ['url' => 'https://acme.ci'])
            ->assertStatus(422);
    }
}
