<?php

namespace Database\Seeders;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Insère les entreprises réelles du référentiel (idempotent, sans avis fabriqué).
 * À exécuter en production comme en dev. Les entrées arrivent en « a_verifier ».
 */
class EntrepriseReelleSeeder extends Seeder
{
    public function run(): void
    {
        $entreprises = require database_path('data/entreprises_fondateurs.php');

        foreach ($entreprises as $index => $data) {
            $slug = Str::slug($data['nom']);

            Entreprise::updateOrCreate(
                ['slug' => $slug],
                array_merge([
                    'secteur_activite' => SecteurActivite::Autre->value,
                    'statut' => StatutEntreprise::AVerifier->value,
                    'source_scraping' => 'liste_fondateurs',
                    // Ordre de la liste éditoriale « à éviter » (position dans le fichier).
                    'rang_a_eviter' => $index + 1,
                ], $data, ['slug' => $slug]),
            );
        }
    }
}
