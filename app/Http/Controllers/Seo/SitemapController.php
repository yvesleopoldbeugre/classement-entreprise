<?php

namespace App\Http\Controllers\Seo;

use App\Enums\StatutEntreprise;
use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        // Régénéré au plus toutes les 6 h (invalidé aussi à chaque recalcul du classement).
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            // Entreprises publiquement affichées : liste éditoriale (rang_a_eviter) ou vérifiées.
            $entreprises = Entreprise::query()
                ->where(fn ($q) => $q
                    ->whereNotNull('rang_a_eviter')
                    ->orWhere('statut', StatutEntreprise::Verifiee))
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at']);

            return view('sitemap', ['entreprises' => $entreprises])->render();
        });

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
