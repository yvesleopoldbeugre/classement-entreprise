<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Référentiel réel : entrées neutres + liste éditoriale « à éviter ».
        $this->call(EntrepriseReelleSeeder::class);

        // En développement seulement : un compte admin pour tester la modération.
        if (app()->environment('local')) {
            $admin = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'pseudo_public' => 'test_user',
            ]);
            $admin->forceFill(['is_admin' => true])->save();
        }
    }
}
