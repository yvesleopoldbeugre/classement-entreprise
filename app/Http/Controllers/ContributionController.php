<?php

namespace App\Http\Controllers;

use App\Enums\StatutEmploi;
use App\Enums\StatutModeration;
use App\Enums\TypeMission;
use App\Http\Requests\AvisEntreprise\StoreAvisEntrepriseRequest;
use App\Http\Requests\Mission\StoreMissionRequest;
use App\Http\Requests\RetourEntretien\StoreRetourEntretienRequest;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\Mission;
use App\Models\RetourEntretien;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContributionController extends Controller
{
    public function avisCreate(Entreprise $entreprise): View
    {
        return view('contributions.avis', [
            'entreprise' => $entreprise,
            'statutsEmploi' => StatutEmploi::cases(),
        ]);
    }

    public function avisStore(StoreAvisEntrepriseRequest $request, Entreprise $entreprise): RedirectResponse
    {
        // Flux « avis d'abord » : invité → on mémorise l'avis validé et on ouvre la
        // création de compte ; l'avis est publié après connexion (voir AuthController).
        if ($request->user() === null) {
            $request->session()->put('avis_en_attente', $request->validated());
            $request->session()->put('avis_en_attente_entreprise', $entreprise->id);

            return redirect()->route('entreprises.show', $entreprise)
                ->with('info', 'Dernière étape : créez votre compte pour publier votre avis.');
        }

        AvisEntreprise::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'statut_moderation' => StatutModeration::parDefaut(),
        ]);

        return redirect()->route('entreprises.show', $entreprise)
            ->with('success', 'Merci ! Votre avis sera publié après modération.');
    }

    public function entretienCreate(Entreprise $entreprise): View
    {
        return view('contributions.entretien', ['entreprise' => $entreprise]);
    }

    public function entretienStore(StoreRetourEntretienRequest $request, Entreprise $entreprise): RedirectResponse
    {
        RetourEntretien::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'statut_moderation' => StatutModeration::parDefaut(),
        ]);

        return redirect()->route('entreprises.show', $entreprise)
            ->with('success', 'Merci ! Votre retour d’entretien sera publié après modération.');
    }

    public function missionCreate(Entreprise $entreprise): View
    {
        return view('contributions.mission', [
            'entreprise' => $entreprise,
            'typesMission' => TypeMission::cases(),
        ]);
    }

    public function missionStore(StoreMissionRequest $request, Entreprise $entreprise): RedirectResponse
    {
        Mission::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'statut_moderation' => StatutModeration::parDefaut(),
        ]);

        return redirect()->route('entreprises.show', $entreprise)
            ->with('success', 'Merci ! Votre mission sera publiée après modération.');
    }
}
