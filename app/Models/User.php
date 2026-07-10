<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'pseudo_public', 'poste_actuel', 'linkedin_verifie', 'provider', 'provider_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'linkedin_verifie' => 'boolean',
            'is_admin' => 'boolean',
        ];
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
}
