<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreerAdmin extends Command
{
    protected $signature = 'admin:creer
                            {email? : Email du compte}
                            {--name= : Nom complet}
                            {--pseudo= : Pseudo public}
                            {--password= : Mot de passe}';

    protected $description = 'Crée un compte administrateur, ou promeut un utilisateur existant en admin';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email de l’administrateur');

        // Utilisateur déjà présent → simple promotion.
        if ($user = User::where('email', $email)->first()) {
            $user->is_admin = true;
            $user->save();

            $this->components->info("« {$email} » est désormais administrateur.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: $this->ask('Nom complet');
        $pseudo = $this->option('pseudo') ?: Str::slug($this->ask('Pseudo public'), '_');
        $password = $this->option('password') ?: $this->secret('Mot de passe');

        $validation = Validator::make(
            compact('email', 'name', 'pseudo', 'password'),
            [
                'email' => ['required', 'email', 'unique:users,email'],
                'name' => ['required', 'string', 'max:255'],
                'pseudo' => ['required', 'string', 'max:255', 'unique:users,pseudo_public'],
                'password' => ['required', Password::min(8)],
            ],
        );

        if ($validation->fails()) {
            foreach ($validation->errors()->all() as $erreur) {
                $this->components->error($erreur);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'pseudo_public' => $pseudo,
            'password' => $password, // haché via le cast 'hashed'
        ]);
        $user->is_admin = true; // hors $fillable (anti-escalade) : assigné explicitement
        $user->save();

        $this->components->info("Compte administrateur créé : {$email} (pseudo « {$pseudo} »).");

        return self::SUCCESS;
    }
}
