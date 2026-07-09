<?php

namespace Database\Factories;

use App\Enums\StatutModeration;
use App\Enums\TypeMission;
use App\Models\Entreprise;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mission>
 */
class MissionFactory extends Factory
{
    protected $model = Mission::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => Entreprise::factory(),
            'user_id' => User::factory(),
            'type_mission' => fake()->randomElement(TypeMission::cases()),
            'duree_mois' => fake()->numberBetween(1, 24),
            'fourchette_remuneration' => fake()->randomElement([
                '150k-300k FCFA', '300k-500k FCFA', '500k-800k FCFA', '800k-1.2M FCFA',
            ]),
            'paiement_a_temps' => fake()->boolean(75),
            'respect_contrat' => fake()->boolean(80),
            'commentaire' => fake()->optional()->paragraph(),
            'statut_moderation' => StatutModeration::Publie,
        ];
    }
}
