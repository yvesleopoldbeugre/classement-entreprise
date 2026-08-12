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

            $entreprise = Entreprise::firstOrNew(['slug' => $slug]);
            $nouveau = ! $entreprise->exists;

            // Champs du référentiel (rafraîchis à chaque seed).
            $entreprise->fill(array_merge($data, ['slug' => $slug]));

            // Champs gérés APRÈS coup (modération, sortie de « à éviter »…) : uniquement
            // à la création, pour ne pas écraser le travail admin à chaque déploiement.
            if ($nouveau) {
                $entreprise->secteur_activite ??= SecteurActivite::Autre->value;
                $entreprise->statut = StatutEntreprise::AVerifier->value;
                $entreprise->source_scraping = 'liste_fondateurs';
                $entreprise->rang_a_eviter = $index + 1;
            }

            $entreprise->save();
        }
    }
}
