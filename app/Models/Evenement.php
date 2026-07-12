<?php

namespace App\Models;

use App\Enums\TypeEvenement;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['type', 'user_id', 'sujet_type', 'sujet_id', 'url', 'visiteur_hash'])]
class Evenement extends Model
{
    /** Journal en append-only : pas de colonne updated_at. */
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => TypeEvenement::class,
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function sujet(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enregistre un événement. Point d'entrée unique utilisé par le middleware,
     * les observers et les contrôleurs.
     *
     * @param  array{user_id?:int|null, url?:string|null, visiteur_hash?:string|null}  $extra
     */
    public static function log(TypeEvenement $type, ?Model $sujet = null, array $extra = []): self
    {
        return static::create([
            'type' => $type,
            'user_id' => $extra['user_id'] ?? auth()->id(),
            'sujet_type' => $sujet?->getMorphClass(),
            'sujet_id' => $sujet?->getKey(),
            'url' => $extra['url'] ?? null,
            'visiteur_hash' => $extra['visiteur_hash'] ?? null,
        ]);
    }
}
