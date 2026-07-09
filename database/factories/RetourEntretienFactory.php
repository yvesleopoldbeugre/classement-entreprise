<?php

namespace Database\Factories;

use App\Enums\StatutModeration;
use App\Models\Entreprise;
use App\Models\RetourEntretien;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RetourEntretien>
 */
class RetourEntretienFactory extends Factory
{
    protected $model = RetourEntretien::class;

    public function definition(): array
    {
        $aRecuReponse = fake()->boolean(70);

        return [
            'entreprise_id' => Entreprise::factory(),
            'user_id' => User::factory(),
            'poste_vise' => fake()->jobTitle(),
            'date_entretien_mois' => fake()->dateTimeBetween('-2 years', 'now')->modify('first day of this month'),
            'nb_etapes' => fake()->numberBetween(1, 5),
            'duree_processus_jours' => fake()->numberBetween(3, 90),
            'questions_posees' => fake()->randomElements(
                ['algorithmie', 'sql', 'système', 'comportemental', 'cas pratique', 'culture', 'salaire'],
                fake()->numberBetween(1, 4)
            ),
            'a_recu_reponse' => $aRecuReponse,
            'delai_reponse_jours' => $aRecuReponse ? fake()->numberBetween(1, 45) : null,
            'a_eu_offre' => $aRecuReponse && fake()->boolean(40),
            'ressenti_general' => fake()->optional()->sentence(),
            'statut_moderation' => StatutModeration::Publie,
        ];
    }
}
