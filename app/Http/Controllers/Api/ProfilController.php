<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfilRequest;
use App\Http\Resources\UserResource;

class ProfilController extends Controller
{
    public function update(UpdateProfilRequest $request): UserResource
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user);
    }
}
