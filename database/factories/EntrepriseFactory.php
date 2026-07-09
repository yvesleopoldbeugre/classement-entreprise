<?php

namespace Database\Factories;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Models\Entreprise;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Entreprise>
 */
class EntrepriseFactory extends Factory
{
    protected $model = Entreprise::class;

    public function definition(): array
    {
        $nom = fake()->unique()->company();

        return [
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 99999),
            'secteur_activite' => fake()->randomElement(SecteurActivite::cases()),
            'adresse' => fake()->streetAddress(),
            'commune' => fake()->randomElement([
                'Cocody', 'Plateau', 'Marcory', 'Yopougon', 'Treichville',
                'Abobo', 'Koumassi', 'Bingerville',
            ]),
            'site_web' => fake()->url(),
            'linkedin_url' => 'https://www.linkedin.com/company/'.fake()->slug(),
            'taille_estimee' => fake()->randomElement(['1-10', '10-50', '50-200', '200-500', '500+']),
            'date_creation' => fake()->year(),
            'source_scraping' => fake()->randomElement(['linkedin', 'manuel', 'annuaire', null]),
            'statut' => fake()->randomElement(StatutEntreprise::cases()),
        ];
    }

    public function verifiee(): static
    {
        return $this->state(fn () => ['statut' => StatutEntreprise::Verifiee]);
    }
}
