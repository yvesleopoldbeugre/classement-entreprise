<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Derrière un reverse-proxy (Traefik) : faire confiance aux en-têtes
        // X-Forwarded-* pour générer des URLs https et des cookies sécurisés.
        // L'app n'étant joignable que par le proxy (réseau interne), '*' est sûr ici.
        $middleware->trustProxies(at: '*');

        // Comptage des visites de pages (statistiques admin) — terminable, sans latence.
        // AuthenticateSession : lie chaque session au hash du mot de passe et permet la
        // déconnexion des autres appareils (Auth::logoutOtherDevices).
        $middleware->web(append: [
            \App\Http\Middleware\EnregistrerVisite::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
