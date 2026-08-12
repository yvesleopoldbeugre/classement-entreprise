<?php

namespace Tests\Feature;

use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use Database\Seeders\EntrepriseReelleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedReferentielTest extends TestCase
{
    use RefreshDatabase;

    public function test_reseeder_preserve_le_statut_et_la_sortie_de_a_eviter(): void
    {
        (new EntrepriseReelleSeeder)->run();

        $entreprise = Entreprise::where('slug', 'worldev')->firstOrFail();
        $this->assertSame(StatutEntreprise::AVerifier, $entreprise->statut);

        // Un admin la vérifie ; elle sort de la liste « à éviter ».
        $entreprise->update(['statut' => StatutEntreprise::Verifiee, 'rang_a_eviter' => null]);

        // Re-seed (comme au déploiement suivant) → ne doit RIEN réinitialiser.
        (new EntrepriseReelleSeeder)->run();

        $entreprise->refresh();
        $this->assertSame(StatutEntreprise::Verifiee, $entreprise->statut);
        $this->assertNull($entreprise->rang_a_eviter);

        // Et pas de doublon.
        $this->assertSame(1, Entreprise::where('slug', 'worldev')->count());
    }
}
