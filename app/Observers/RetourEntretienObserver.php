<?php

namespace App\Observers;

use App\Enums\TypeEvenement;
use App\Models\Evenement;
use App\Models\RetourEntretien;

class RetourEntretienObserver
{
    public function created(RetourEntretien $retour): void
    {
        Evenement::log(TypeEvenement::Entretien, $retour, ['user_id' => $retour->user_id]);
    }
}
