<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Identité publique : on expose le pseudo, pas le nom réel.
            'pseudo_public' => $this->pseudo_public,
            'poste_actuel' => $this->poste_actuel,
            'linkedin_verifie' => (bool) $this->linkedin_verifie,

            // Champs privés : seulement pour l'utilisateur lui-même.
            'name' => $this->when(
                $request->user()?->id === $this->id,
                $this->name,
            ),
            'email' => $this->when(
                $request->user()?->id === $this->id,
                $this->email,
            ),

            'created_at' => $this->created_at,
        ];
    }
}
