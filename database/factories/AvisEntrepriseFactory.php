<?php

namespace Database\Factories;

use App\Enums\StatutEmploi;
use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvisEntreprise>
 */
class AvisEntrepriseFactory extends Factory
{
    protected $model = AvisEntreprise::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => Entreprise::factory(),
            'user_id' => User::factory(),
            'note_ambiance' => fake()->numberBetween(1, 5),
            'note_management' => fake()->numberBetween(1, 5),
            'note_salaire' => fake()->numberBetween(1, 5),
            'note_evolution' => fake()->numberBetween(1, 5),
            'commentaire' => fake()->optional()->paragraph(),
            'statut_emploi' => fake()->randomElement([StatutEmploi::Ancien, StatutEmploi::Actuel]),
            'statut_moderation' => StatutModeration::Publie,
        ];
    }

    public function enAttente(): static
    {
        return $this->state(fn () => ['statut_moderation' => StatutModeration::EnAttente]);
    }
}
