<?php

namespace App\Http\Middleware;

use App\Enums\TypeEvenement;
use App\Models\Evenement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnregistrerVisite
{
    /** Préfixes exclus du comptage (admin, santé, assets…). */
    private const EXCLUS = ['moderation', 'moderation/*', 'admin', 'admin/*', 'up', 'build/*'];

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Après envoi de la réponse (pas d'impact sur la latence) : on n'enregistre
     * qu'une vraie page HTML consultée (GET, 200, non-AJAX, hors zone admin).
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->estUneVisite($request, $response)) {
            return;
        }

        Evenement::log(TypeEvenement::Visite, null, [
            'url' => $request->path(),
            'visiteur_hash' => $this->visiteurHash($request),
        ]);
    }

    private function estUneVisite(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && ! $request->ajax()
            && ! $request->expectsJson()
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $request->is(...self::EXCLUS);
    }

    /** Empreinte anonyme du visiteur : aucune IP stockée en clair (RGPD). */
    private function visiteurHash(Request $request): string
    {
        $session = $request->hasSession() ? $request->session()->getId() : '';

        return hash('sha256', $request->ip().'|'.$session.'|'.config('app.key'));
    }
}
