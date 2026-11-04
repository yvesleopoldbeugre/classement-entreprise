<?php

namespace Tests\Integration;

use App\Services\SiteWebInspecteur;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/** Lecture d'une page d'accueil d'entreprise (client HTTP simulé). */
class SiteWebInspecteurTest extends TestCase
{
    private function inspecter(string $html, int $code = 200): array
    {
        Http::fake(['*' => Http::response($html, $code)]);

        return (new SiteWebInspecteur)->inspecter('https://acme.ci');
    }

    public function test_le_nom_vient_d_og_site_name_en_priorite(): void
    {
        $infos = $this->inspecter('<title>Accueil | Autre</title><meta property="og:site_name" content="Acme SARL">');

        $this->assertSame('Acme SARL', $infos['nom']);
    }

    public function test_le_nom_retire_les_suffixes_du_titre(): void
    {
        $this->assertSame('Acme', $this->inspecter('<title>Acme | Accueil</title>')['nom']);
        $this->assertSame('Acme', $this->inspecter('<title>Acme - Site officiel</title>')['nom']);
        $this->assertSame('Acme', $this->inspecter('<title>Acme — Paiements</title>')['nom']);
    }

    public function test_la_description_accepte_les_apostrophes_et_entites(): void
    {
        $infos = $this->inspecter('<meta name="description" content="L&#039;ESN qui s\'engage à Abidjan">');

        $this->assertSame("L'ESN qui s'engage à Abidjan", $infos['description']);
    }

    public function test_la_description_se_replie_sur_og_description(): void
    {
        $this->assertSame('Banque digitale', $this->inspecter('<meta property="og:description" content="Banque digitale">')['description']);
    }

    public function test_l_ordre_des_attributs_meta_est_indifferent(): void
    {
        $this->assertSame('Ordre inversé', $this->inspecter('<meta content="Ordre inversé" name="description">')['description']);
    }

    public function test_une_page_vide_ne_donne_rien(): void
    {
        $this->assertSame(['nom' => null, 'description' => null, 'linkedin_url' => null], $this->inspecter('<html></html>'));
    }

    public function test_une_seule_page_linkedin_est_retenue_meme_citee_plusieurs_fois(): void
    {
        $html = '<a href="https://www.linkedin.com/company/acme-ci">A</a><a href="https://linkedin.com/company/ACME-CI">B</a>';

        $this->assertSame('https://www.linkedin.com/company/acme-ci', $this->inspecter($html)['linkedin_url']);
    }

    public function test_plusieurs_pages_linkedin_differentes_sont_ignorees(): void
    {
        $html = '<a href="https://www.linkedin.com/company/acme">A</a><a href="https://www.linkedin.com/company/client">B</a>';

        $this->assertNull($this->inspecter($html)['linkedin_url']);
    }

    public function test_un_code_d_erreur_leve_une_exception_lisible(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Le site est injoignable (code 403).');

        $this->inspecter('', 403);
    }

    public function test_un_site_inaccessible_leve_une_exception_lisible(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Impossible de contacter ce site.');

        (new SiteWebInspecteur)->inspecter('https://introuvable.ci');
    }

    public function test_la_requete_s_identifie_comme_robot(): void
    {
        $this->inspecter('<title>Acme</title>');

        Http::assertSent(fn ($requete) => str_contains($requete->header('User-Agent')[0] ?? '', 'NoteTaBoiteBot'));
    }
}
