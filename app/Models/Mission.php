<?php

namespace App\Models;

use App\Enums\StatutModeration;
use App\Enums\TypeMission;
use App\Models\Concerns\EstSignalable;
use Database\Factories\MissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'entreprise_id', 'user_id', 'type_mission', 'duree_mois', 'fourchette_remuneration',
    'paiement_a_temps', 'respect_contrat', 'commentaire', 'statut_moderation',
])]
class Mission extends Model
{
    /** @use HasFactory<MissionFactory> */
    use EstSignalable, HasFactory;

    protected function casts(): array
    {
        return [
            'type_mission' => TypeMission::class,
            'duree_mois' => 'integer',
            'paiement_a_temps' => 'boolean',
            'respect_contrat' => 'boolean',
            'statut_moderation' => StatutModeration::class,
        ];
    }

    /** @return BelongsTo<Entreprise, $this> */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublie(Builder $query): Builder
    {
        return $query->where('statut_moderation', StatutModeration::Publie);
    }
}
