<?php

namespace App\Models\Concerns;

use App\Models\Signalement;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Rend un modèle signalable (avis, retour d'entretien, mission).
 */
trait EstSignalable
{
    /** @return MorphMany<Signalement, $this> */
    public function signalements(): MorphMany
    {
        return $this->morphMany(Signalement::class, 'signalable');
    }
}
