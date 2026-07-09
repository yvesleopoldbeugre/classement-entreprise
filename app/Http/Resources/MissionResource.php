<?php

namespace App\Http\Resources;

use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Mission */
class MissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entreprise_id' => $this->entreprise_id,
            'type_mission' => $this->type_mission?->value,
            'duree_mois' => $this->duree_mois,
            'fourchette_remuneration' => $this->fourchette_remuneration,
            'paiement_a_temps' => $this->paiement_a_temps,
            'respect_contrat' => $this->respect_contrat,
            'commentaire' => $this->commentaire,
            'statut_moderation' => $this->statut_moderation?->value,

            'entreprise' => new EntrepriseResource($this->whenLoaded('entreprise')),
            'auteur' => new UserResource($this->whenLoaded('user')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
