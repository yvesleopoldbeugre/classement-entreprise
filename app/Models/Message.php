<?php

namespace App\Models;

use App\Enums\Expediteur;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'expediteur', 'admin_id', 'corps', 'lu_at'])]
class Message extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'expediteur' => Expediteur::class,
            'lu_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
