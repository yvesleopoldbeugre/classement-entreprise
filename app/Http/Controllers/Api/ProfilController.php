<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfilRequest;
use App\Http\Resources\UserResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('Profil', weight: 5)]
class ProfilController extends Controller
{
    /**
     * Afficher son profil
     *
     * Retourne l'utilisateur authentifié.
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Mettre à jour son profil
     *
     * Modifie le profil public de l'utilisateur authentifié
     * (pseudo, poste actuel).
     */
    public function update(UpdateProfilRequest $request): UserResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user);
    }
}
