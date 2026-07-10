<?php

namespace App\Http\Requests\RetourEntretien;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreRetourEntretienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * On stocke toujours le 1er du mois de l'entretien.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('date_entretien_mois')) {
            try {
                $this->merge([
                    'date_entretien_mois' => Carbon::parse($this->input('date_entretien_mois'))
                        ->startOfMonth()->toDateString(),
                ]);
            } catch (\Throwable) {
                // Laisse la validation 'date' rejeter une valeur invalide.
            }
        }

        // Depuis un formulaire web, les tags arrivent en texte séparé par virgules.
        if (is_string($this->input('questions_posees'))) {
            $tags = collect(explode(',', $this->input('questions_posees')))
                ->map(fn ($t) => trim($t))
                ->filter()
                ->values()
                ->all();

            $this->merge(['questions_posees' => $tags]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entreprise_id' => ['required', 'integer', Rule::exists('entreprises', 'id')],
            'poste_vise' => ['required', 'string', 'max:255'],
            'date_entretien_mois' => ['required', 'date', 'before_or_equal:today'],
            'nb_etapes' => ['nullable', 'integer', 'between:1,255'],
            'duree_processus_jours' => ['nullable', 'integer', 'between:0,65535'],
            'questions_posees' => ['nullable', 'array', 'max:30'],
            'questions_posees.*' => ['string', 'max:100'],
            'a_recu_reponse' => ['required', 'boolean'],
            'delai_reponse_jours' => ['nullable', 'integer', 'between:0,65535', 'required_if:a_recu_reponse,true'],
            'a_eu_offre' => ['required', 'boolean'],
            'ressenti_general' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
