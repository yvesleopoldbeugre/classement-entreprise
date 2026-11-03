<?php

namespace App\Http\Controllers\Seo;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Http\Controllers\ClassementController;
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
            $entreprises = Entreprise::publique()
                ->orderByDesc('updated_at')
                ->get(['slug', 'updated_at']);

            // Pages d'atterrissage : secteurs et communes ayant au moins une entreprise classable.
            $avecEntreprises = Entreprise::query()
                ->where('statut', StatutEntreprise::Verifiee)
                ->whereNull('rang_a_eviter');
            $secteurs = collect(SecteurActivite::cases())
                ->filter(fn (SecteurActivite $s) => (clone $avecEntreprises)->where('secteur_activite', $s)->exists());
            $communes = ClassementController::communes()
                ->filter(fn (string $c) => (clone $avecEntreprises)->where('commune', $c)->exists());

            return view('sitemap', [
                'entreprises' => $entreprises,
                'secteurs' => $secteurs,
                'communes' => $communes,
            ])->render();
        });

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
