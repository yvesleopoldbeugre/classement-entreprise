<?php

namespace App\Http\Controllers;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Http\Requests\Entreprise\ProposerEntrepriseRequest;
use App\Models\Entreprise;
use App\Services\SiteWebInspecteur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class EntrepriseController extends Controller
{
    public function create(): View
    {
        return view('entreprises.creer', ['secteurs' => SecteurActivite::cases()]);
    }

    /** Pré-remplissage : récupère le nom + la description depuis le site web (best-effort). */
    public function depuisSite(Request $request, SiteWebInspecteur $inspecteur): JsonResponse
    {
        $request->validate(['url' => ['required', 'url', 'max:255']]);
        $url = $request->string('url')->toString();

        try {
            $infos = $inspecteur->inspecter($url);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'nom' => $infos['nom'],
            'commentaire' => $infos['description'],
            'site_web' => $url,
        ]);
    }

    public function store(ProposerEntrepriseRequest $request): RedirectResponse
    {
        // Un admin ajoute une entreprise directement vérifiée ; un utilisateur
        // la propose en attente de vérification. Le statut n'est jamais pris
        // depuis le formulaire (on l'écrase).
        $estAdmin = $request->user()->can('moderer');

        $entreprise = Entreprise::create([
            ...$request->validated(),
            'statut' => $estAdmin ? StatutEntreprise::Verifiee : StatutEntreprise::AVerifier,
            'source_scraping' => 'utilisateur',
        ]);

        // Admin → fiche (elle est publiée) ; utilisateur → retour au classement
        // (sa proposition est en attente de vérification, pas encore publique).
        if ($estAdmin) {
            return redirect()->route('entreprises.show', $entreprise)
                ->with('success', 'Entreprise ajoutée et vérifiée.');
        }

        return redirect()->route('classement.index')
            ->with('success', 'Merci ! Votre proposition sera vérifiée par un modérateur avant d’apparaître.');
    }
}
