<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dates et durées affichées en français (ex. « il y a 2 jours », « janvier 2026 »).
        Carbon::setLocale('fr');

        // Accès à l'espace de modération réservé aux administrateurs.
        Gate::define('moderer', fn (User $user) => $user->is_admin);

        // Déclare l'authentification Bearer (Sanctum) dans la doc OpenAPI (Scramble).
        // Scramble applique automatiquement ce schéma aux routes protégées par
        // le middleware « auth:sanctum » ; les routes publiques restent ouvertes.
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer'));
        });
    }
}
