<?php

namespace App\Http\Requests\Mission;

use App\Enums\TypeMission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entreprise_id' => ['required', 'integer', Rule::exists('entreprises', 'id')],
            'type_mission' => ['required', Rule::enum(TypeMission::class)],
            'duree_mois' => ['nullable', 'integer', 'between:0,65535'],
            'fourchette_remuneration' => ['nullable', 'string', 'max:100'],
            'paiement_a_temps' => ['nullable', 'boolean'],
            'respect_contrat' => ['nullable', 'boolean'],
            'commentaire' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
