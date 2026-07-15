<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['visiteur_token', 'user_id', 'url', 'user_agent', 'ip_hash', 'derniere_activite'])]
class Presence extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['derniere_activite' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
