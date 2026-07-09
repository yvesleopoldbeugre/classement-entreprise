<?php

namespace App\Http\Resources;

use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Entreprise */
class EntrepriseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'slug' => $this->slug,
            'secteur_activite' => $this->secteur_activite?->value,
            'secteur_libelle' => $this->secteur_activite?->libelle(),
            'adresse' => $this->adresse,
            'commune' => $this->commune,
            'site_web' => $this->site_web,
            'linkedin_url' => $this->linkedin_url,
            'taille_estimee' => $this->taille_estimee,
            'date_creation' => $this->date_creation,
            'statut' => $this->statut?->value,

            'classement' => [
                'nb_avis_total' => (int) $this->nb_avis_total,
                'note_globale' => $this->floatOrNull($this->note_globale),
                'score_bayesien' => $this->floatOrNull($this->score_bayesien),
                'moyennes' => [
                    'ambiance' => $this->floatOrNull($this->moy_ambiance),
                    'management' => $this->floatOrNull($this->moy_management),
                    'salaire' => $this->floatOrNull($this->moy_salaire),
                    'evolution' => $this->floatOrNull($this->moy_evolution),
                ],
            ],

            'avis' => AvisEntrepriseResource::collection($this->whenLoaded('avis')),
            'retours_entretiens' => RetourEntretienResource::collection($this->whenLoaded('retoursEntretiens')),
            'missions' => MissionResource::collection($this->whenLoaded('missions')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function floatOrNull($valeur): ?float
    {
        return $valeur !== null ? (float) $valeur : null;
    }
}
