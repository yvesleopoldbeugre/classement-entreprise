<?php

namespace App\Models;

use App\Enums\StatutEmploi;
use App\Enums\StatutModeration;
use App\Models\Concerns\EstSignalable;
use App\Observers\AvisEntrepriseObserver;
use Database\Factories\AvisEntrepriseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AvisEntrepriseObserver::class])]
#[Fillable([
    'entreprise_id', 'user_id', 'note_ambiance', 'note_management',
    'note_salaire', 'note_evolution', 'commentaire', 'statut_emploi', 'statut_moderation',
])]
class AvisEntreprise extends Model
{
    /** @use HasFactory<AvisEntrepriseFactory> */
    use EstSignalable, HasFactory;

    protected $table = 'avis_entreprises';

    protected function casts(): array
    {
        return [
            'note_ambiance' => 'integer',
            'note_management' => 'integer',
            'note_salaire' => 'integer',
            'note_evolution' => 'integer',
            'statut_emploi' => StatutEmploi::class,
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

    /** Moyenne des 4 dimensions de cet avis (1-5). */
    public function noteMoyenne(): float
    {
        return ($this->note_ambiance + $this->note_management
            + $this->note_salaire + $this->note_evolution) / 4;
    }
}
