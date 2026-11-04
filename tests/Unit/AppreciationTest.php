<?php

namespace Tests\Unit;

use App\Models\Entreprise;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Appréciation « bulletin » déduite du score (aucun accès base). */
class AppreciationTest extends TestCase
{
    private function entreprise(?float $score, int $nbAvis = 10): Entreprise
    {
        return (new Entreprise)->forceFill(['score_bayesien' => $score, 'nb_avis_total' => $nbAvis]);
    }

    /** @return array<string, array{float, string}> */
    public static function paliers(): array
    {
        return [
            'excellent (borne)' => [4.3, 'Excellent'],
            'juste sous excellent' => [4.299, 'Très bien'],
            'très bien (borne)' => [3.8, 'Très bien'],
            'bien (borne)' => [3.3, 'Bien'],
            'assez bien (borne)' => [2.8, 'Assez bien'],
            'passable (borne)' => [2.3, 'Passable'],
            'insuffisant' => [2.299, 'Insuffisant'],
            'minimum' => [1.0, 'Insuffisant'],
        ];
    }

    #[DataProvider('paliers')]
    public function test_le_score_donne_le_bon_palier(float $score, string $attendu): void
    {
        $this->assertSame($attendu, $this->entreprise($score)->appreciation());
    }

    public function test_pas_d_appreciation_sans_score(): void
    {
        $this->assertNull($this->entreprise(null)->appreciation());
    }

    public function test_pas_d_appreciation_sous_le_seuil_d_avis(): void
    {
        config(['classement.min_avis_classement' => 3]);

        $this->assertNull($this->entreprise(4.8, 2)->appreciation());
        $this->assertSame('Excellent', $this->entreprise(4.8, 3)->appreciation());
    }
}
