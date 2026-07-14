<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        // Email de vérification en français (relève le poids des avis du contributeur).
        VerifyEmail::toMailUsing(fn ($notifiable, string $url) => (new MailMessage)
            ->subject('Vérifiez votre email · Note ta boîte')
            ->greeting('Bonjour !')
            ->line('Confirmez votre adresse email pour que vos avis pèsent davantage dans le classement.')
            ->action('Vérifier mon email', $url)
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.'));

        // Déclare l'authentification Bearer (Sanctum) dans la doc OpenAPI (Scramble).
        // Scramble applique automatiquement ce schéma aux routes protégées par
        // le middleware « auth:sanctum » ; les routes publiques restent ouvertes.
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer'));
        });
    }
}
