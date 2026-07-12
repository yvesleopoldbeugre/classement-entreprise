<?php

namespace App\Models;

use App\Enums\StatutModeration;
use App\Models\Concerns\EstSignalable;
use App\Observers\RetourEntretienObserver;
use Database\Factories\RetourEntretienFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([RetourEntretienObserver::class])]
#[Fillable([
    'entreprise_id', 'user_id', 'poste_vise', 'date_entretien_mois', 'nb_etapes',
    'duree_processus_jours', 'questions_posees', 'a_recu_reponse', 'delai_reponse_jours',
    'a_eu_offre', 'ressenti_general', 'statut_moderation',
])]
class RetourEntretien extends Model
{
    /** @use HasFactory<RetourEntretienFactory> */
    use EstSignalable, HasFactory;

    protected $table = 'retours_entretiens';

    protected function casts(): array
    {
        return [
            'date_entretien_mois' => 'date',
            'nb_etapes' => 'integer',
            'duree_processus_jours' => 'integer',
            'questions_posees' => 'array',
            'a_recu_reponse' => 'boolean',
            'delai_reponse_jours' => 'integer',
            'a_eu_offre' => 'boolean',
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
