<?php

namespace App\Http\Controllers;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use App\Http\Requests\Entreprise\ProposerEntrepriseRequest;
use App\Models\Entreprise;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EntrepriseController extends Controller
{
    public function create(): View
    {
        return view('entreprises.creer', ['secteurs' => SecteurActivite::cases()]);
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
