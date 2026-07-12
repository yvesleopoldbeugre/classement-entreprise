<?php

namespace App\Observers;

use App\Enums\TypeEvenement;
use App\Models\Evenement;
use App\Models\Signalement;

class SignalementObserver
{
    public function created(Signalement $signalement): void
    {
        Evenement::log(TypeEvenement::Signalement, $signalement, ['user_id' => $signalement->user_id]);
    }
}
