<?php

namespace App\Http\Resources;

use App\Models\AvisEntreprise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AvisEntreprise */
class AvisEntrepriseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entreprise_id' => $this->entreprise_id,
            'notes' => [
                'ambiance' => $this->note_ambiance,
                'management' => $this->note_management,
                'salaire' => $this->note_salaire,
                'evolution' => $this->note_evolution,
                'moyenne' => round($this->noteMoyenne(), 2),
            ],
            'commentaire' => $this->commentaire,
            'statut_emploi' => $this->statut_emploi?->value,
            'statut_moderation' => $this->statut_moderation?->value,

            'entreprise' => new EntrepriseResource($this->whenLoaded('entreprise')),
            'auteur' => new UserResource($this->whenLoaded('user')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
