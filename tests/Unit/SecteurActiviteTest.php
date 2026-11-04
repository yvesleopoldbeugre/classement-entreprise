<?php

namespace Tests\Unit;

use App\Enums\SecteurActivite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SecteurActiviteTest extends TestCase
{
    public function test_le_slug_remplace_les_soulignes_par_des_tirets(): void
    {
        $this->assertSame('banque-dsi', SecteurActivite::BanqueDsi->slug());
        $this->assertSame('ssii', SecteurActivite::Ssii->slug());
    }

    public function test_chaque_slug_redonne_son_secteur(): void
    {
        foreach (SecteurActivite::cases() as $secteur) {
            $this->assertSame($secteur, SecteurActivite::depuisSlug($secteur->slug()));
        }
    }

    public function test_un_slug_inconnu_donne_null(): void
    {
        $this->assertNull(SecteurActivite::depuisSlug('boulangerie'));
    }

    /** @return array<string, array{?string, ?SecteurActivite}> */
    public static function saisies(): array
    {
        return [
            'valeur' => ['banque_dsi', SecteurActivite::BanqueDsi],
            'slug' => ['banque-dsi', SecteurActivite::BanqueDsi],
            'libellé' => ['SSII / ESN', SecteurActivite::Ssii],
            'casse et espaces' => ['  Télécom ', SecteurActivite::Telecom],
            'inconnu' => ['agriculture', null],
            'vide' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('saisies')]
    public function test_depuis_saisie_reconnait_valeur_slug_et_libelle(?string $saisie, ?SecteurActivite $attendu): void
    {
        $this->assertSame($attendu, SecteurActivite::depuisSaisie($saisie));
    }

    public function test_values_liste_toutes_les_valeurs_d_enum(): void
    {
        $this->assertSame(['ssii', 'banque_dsi', 'startup', 'telecom', 'integrateur', 'autre'], SecteurActivite::values());
    }

    public function test_chaque_secteur_a_un_libelle(): void
    {
        foreach (SecteurActivite::cases() as $secteur) {
            $this->assertNotSame('', $secteur->libelle());
        }
    }
}
