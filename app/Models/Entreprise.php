<?php

namespace App\Models;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use Database\Factories\EntrepriseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'nom', 'slug', 'secteur_activite', 'adresse', 'commune', 'site_web',
    'linkedin_url', 'taille_estimee', 'date_creation', 'source_scraping', 'statut',
    'rang_a_eviter', 'reponse_entreprise', 'reponse_entreprise_le', 'commentaire_proposition',
])]
class Entreprise extends Model
{
    /** @use HasFactory<EntrepriseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'secteur_activite' => SecteurActivite::class,
            'statut' => StatutEntreprise::class,
            'nb_avis_total' => 'integer',
            'moy_ambiance' => 'decimal:2',
            'moy_management' => 'decimal:2',
            'moy_salaire' => 'decimal:2',
            'moy_evolution' => 'decimal:2',
            'note_globale' => 'decimal:2',
            'score_bayesien' => 'decimal:3',
            'rang_a_eviter' => 'integer',
            'reponse_entreprise_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Entreprise $entreprise) {
            if (blank($entreprise->slug) && filled($entreprise->nom)) {
                $entreprise->slug = static::slugUnique($entreprise->nom);
            }
        });
    }

    public static function slugUnique(string $nom): string
    {
        $base = Str::slug($nom);
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /** @return HasMany<AvisEntreprise, $this> */
    public function avis(): HasMany
    {
        return $this->hasMany(AvisEntreprise::class);
    }

    /** @return HasMany<RetourEntretien, $this> */
    public function retoursEntretiens(): HasMany
    {
        return $this->hasMany(RetourEntretien::class);
    }

    /** @return HasMany<Mission, $this> */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    /** Entreprises éligibles au classement public (assez d'avis publiés). */
    public function scopeClassable(Builder $query): Builder
    {
        return $query->where('nb_avis_total', '>=', config('classement.min_avis_classement'))
            ->whereNotNull('score_bayesien');
    }

    /** Tri du classement : meilleur score en premier. */
    public function scopeParClassement(Builder $query): Builder
    {
        return $query->orderByDesc('score_bayesien')->orderByDesc('nb_avis_total');
    }

    /** Liste éditoriale « à éviter » (sélection de la communauté), dans l'ordre. */
    public function scopeAEviter(Builder $query): Builder
    {
        return $query->whereNotNull('rang_a_eviter')->orderBy('rang_a_eviter');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
