<?php

namespace App\Http\Resources;

use App\Models\RetourEntretien;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RetourEntretien */
class RetourEntretienResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entreprise_id' => $this->entreprise_id,
            'poste_vise' => $this->poste_vise,
            'date_entretien_mois' => $this->date_entretien_mois?->format('Y-m-d'),
            'nb_etapes' => $this->nb_etapes,
            'duree_processus_jours' => $this->duree_processus_jours,
            'questions_posees' => $this->questions_posees ?? [],
            'a_recu_reponse' => $this->a_recu_reponse,
            'delai_reponse_jours' => $this->delai_reponse_jours,
            'a_eu_offre' => $this->a_eu_offre,
            'ressenti_general' => $this->ressenti_general,
            'statut_moderation' => $this->statut_moderation?->value,

            'entreprise' => new EntrepriseResource($this->whenLoaded('entreprise')),
            'auteur' => new UserResource($this->whenLoaded('user')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
