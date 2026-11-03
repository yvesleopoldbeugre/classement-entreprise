<?php

namespace Tests\Feature;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportReferentielTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $contenu): string
    {
        $chemin = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($chemin, $contenu);

        return $chemin;
    }

    public function test_l_import_cree_des_fiches_verifiees(): void
    {
        $fichier = $this->csv("\xEF\xBB\xBFnom;secteur;site_web;commune\nAcme Tech;SSII / ESN;acme.ci;Cocody\nBeta Bank;banque_dsi;;Plateau\n");

        $this->artisan('entreprises:importer', ['fichier' => $fichier, '--source' => 'artci'])->assertSuccessful();

        $acme = Entreprise::where('slug', 'acme-tech')->firstOrFail();
        $this->assertSame(SecteurActivite::Ssii, $acme->secteur_activite);
        $this->assertSame('https://acme.ci', $acme->site_web);
        $this->assertSame(StatutEntreprise::Verifiee, $acme->statut);
        $this->assertSame('artci', $acme->source_scraping);
        $this->assertSame(SecteurActivite::BanqueDsi, Entreprise::where('slug', 'beta-bank')->value('secteur_activite'));
    }

    public function test_l_import_est_idempotent_et_ne_fait_que_completer(): void
    {
        $existante = Entreprise::factory()->create([
            'nom' => 'Acme Tech', 'slug' => 'acme-tech', 'commune' => 'Marcory',
            'site_web' => null, 'statut' => StatutEntreprise::AVerifier, 'rang_a_eviter' => 2,
        ]);
        $fichier = $this->csv("nom,site_web,commune\nAcme Tech,https://acme.ci,Cocody\n");

        $this->artisan('entreprises:importer', ['fichier' => $fichier])->assertSuccessful();
        $this->artisan('entreprises:importer', ['fichier' => $fichier])->assertSuccessful();

        $existante->refresh();
        $this->assertSame(1, Entreprise::count());
        $this->assertSame('Marcory', $existante->commune);          // jamais écrasé
        $this->assertSame('https://acme.ci', $existante->site_web); // complété
        $this->assertSame(StatutEntreprise::AVerifier, $existante->statut);
        $this->assertSame(2, $existante->rang_a_eviter);
    }

    public function test_l_import_dedoublonne_par_domaine(): void
    {
        Entreprise::factory()->create(['nom' => 'Acme', 'slug' => 'acme', 'site_web' => 'https://www.acme.ci/']);
        $fichier = $this->csv("nom,site_web\nAcme Côte d'Ivoire,http://acme.ci\n");

        $this->artisan('entreprises:importer', ['fichier' => $fichier])->assertSuccessful();

        $this->assertSame(1, Entreprise::count());
    }

    public function test_le_dry_run_n_ecrit_rien(): void
    {
        $fichier = $this->csv("nom\nAcme Tech\n");

        $this->artisan('entreprises:importer', ['fichier' => $fichier, '--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, Entreprise::count());
    }

    public function test_l_enrichissement_complete_depuis_le_site(): void
    {
        Http::fake(['*' => Http::response(
            '<html><head><meta name="description" content="ESN basée à Abidjan.">'
            .'</head><body><a href="https://www.linkedin.com/company/acme-ci">LinkedIn</a></body></html>',
        )]);
        $entreprise = Entreprise::factory()->create(['site_web' => 'https://acme.ci', 'linkedin_url' => null, 'description' => null]);

        $this->artisan('entreprises:enrichir', ['--pause' => 0])->assertSuccessful();

        $entreprise->refresh();
        $this->assertSame('ESN basée à Abidjan.', $entreprise->description);
        $this->assertSame('https://www.linkedin.com/company/acme-ci', $entreprise->linkedin_url);
        $this->assertNotNull($entreprise->enrichi_le);
    }

    public function test_l_enrichissement_garde_les_apostrophes_et_ignore_un_linkedin_ambigu(): void
    {
        Http::fake(['*' => Http::response(
            '<meta name="description" content="Crédit jusqu\'à 500 000 FCFA.">'
            .'<a href="https://www.linkedin.com/company/client-a">A</a><a href="https://www.linkedin.com/company/client-b">B</a>',
        )]);
        $entreprise = Entreprise::factory()->create(['site_web' => 'https://acme.ci', 'linkedin_url' => null, 'description' => null]);

        $this->artisan('entreprises:enrichir', ['--pause' => 0])->assertSuccessful();

        $entreprise->refresh();
        $this->assertSame('Crédit jusqu\'à 500 000 FCFA.', $entreprise->description);
        $this->assertNull($entreprise->linkedin_url);
    }

    public function test_un_site_en_erreur_est_horodate_sans_bloquer(): void
    {
        Http::fake(['*' => Http::response('', 500)]);
        $entreprise = Entreprise::factory()->create(['site_web' => 'https://acme.ci', 'description' => null]);

        $this->artisan('entreprises:enrichir', ['--pause' => 0])->assertSuccessful();

        $this->assertNull($entreprise->refresh()->description);
        $this->assertNotNull($entreprise->enrichi_le);
        // Déjà tenté : pas de nouvelle requête au passage suivant.
        $this->artisan('entreprises:enrichir', ['--pause' => 0])->expectsOutputToContain('Aucune fiche à enrichir');
    }
}
