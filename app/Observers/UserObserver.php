<?php

namespace App\Observers;

use App\Enums\TypeEvenement;
use App\Models\Evenement;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        // Couvre toutes les créations de compte : formulaire, SSO, admin:creer.
        Evenement::log(TypeEvenement::Inscription, $user, ['user_id' => $user->id]);
    }
}
