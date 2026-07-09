<?php

namespace App\Http\Requests\AvisEntreprise;

use App\Enums\StatutEmploi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvisEntrepriseRequest extends FormRequest
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
            'entreprise_id' => [
                'required', 'integer', Rule::exists('entreprises', 'id'),
                // 1 seul avis global par utilisateur et par entreprise.
                Rule::unique('avis_entreprises', 'entreprise_id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'note_ambiance' => ['required', 'integer', 'between:1,5'],
            'note_management' => ['required', 'integer', 'between:1,5'],
            'note_salaire' => ['required', 'integer', 'between:1,5'],
            'note_evolution' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['nullable', 'string', 'max:5000'],
            'statut_emploi' => ['required', Rule::enum(StatutEmploi::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'entreprise_id.unique' => 'Vous avez déjà publié un avis pour cette entreprise.',
        ];
    }
}
