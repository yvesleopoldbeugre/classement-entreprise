<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy([UserObserver::class])]
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

    /** @return HasMany<Evenement, $this> */
    public function evenements(): HasMany
    {
        return $this->hasMany(Evenement::class);
    }

    /** Génère un pseudo public unique à partir d'une base (nom ou email). */
    public static function pseudoUnique(?string $base): string
    {
        $base = Str::slug((string) ($base ?: 'membre'), '_') ?: 'membre';

        $pseudo = $base;
        $i = 2;
        while (static::where('pseudo_public', $pseudo)->exists()) {
            $pseudo = $base.'_'.$i;
            $i++;
        }

        return $pseudo;
    }
}
