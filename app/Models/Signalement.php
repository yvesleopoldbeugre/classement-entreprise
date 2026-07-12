<?php

namespace App\Models;

use App\Observers\SignalementObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([SignalementObserver::class])]
#[Fillable(['signalable_type', 'signalable_id', 'user_id', 'motif'])]
class Signalement extends Model
{
    /** @return MorphTo<Model, $this> */
    public function signalable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
