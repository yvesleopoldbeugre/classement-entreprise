<?php

namespace App\Observers;

use App\Enums\TypeEvenement;
use App\Models\Evenement;
use App\Models\Mission;

class MissionObserver
{
    public function created(Mission $mission): void
    {
        Evenement::log(TypeEvenement::Mission, $mission, ['user_id' => $mission->user_id]);
    }
}
