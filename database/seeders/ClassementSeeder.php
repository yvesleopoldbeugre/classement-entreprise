<?php

namespace Database\Seeders;

use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\Mission;
use App\Models\RetourEntretien;
use App\Models\User;
use App\Services\ClassementService;
use Illuminate\Database\Seeder;

class ClassementSeeder extends Seeder
{
    public function run(): void
    {
        // Pool d'utilisateurs qui laisseront des retours.
        $users = User::factory(60)->create();

        // Entreprises + leurs retours.
        Entreprise::factory(25)->create()->each(function (Entreprise $entreprise) use ($users) {
            // Nombre d'avis variable : certaines entreprises très notées, d'autres à peine.
            $nbAvis = fake()->numberBetween(0, 15);

            $users->random(min($nbAvis, $users->count()))->each(function (User $user) use ($entreprise) {
                AvisEntreprise::factory()->create([
                    'entreprise_id' => $entreprise->id,
                    'user_id' => $user->id,
                ]);
            });

            // Quelques retours d'entretien et missions (indépendants des avis).
            RetourEntretien::factory(fake()->numberBetween(0, 4))
                ->for($entreprise)
                ->recycle($users)
                ->create();

            Mission::factory(fake()->numberBetween(0, 3))
                ->for($entreprise)
                ->recycle($users)
                ->create();
        });

        // Calcul cohérent des scores (les model events sont mutés pendant le seeding).
        app(ClassementService::class)->recalculerTout();
    }
}
