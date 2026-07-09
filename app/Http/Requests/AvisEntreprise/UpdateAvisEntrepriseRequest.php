<?php

namespace App\Http\Requests\AvisEntreprise;

use App\Enums\StatutEmploi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvisEntrepriseRequest extends FormRequest
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
            // L'entreprise cible d'un avis n'est pas modifiable après coup.
            'note_ambiance' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'note_management' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'note_salaire' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'note_evolution' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'commentaire' => ['nullable', 'string', 'max:5000'],
            'statut_emploi' => ['sometimes', 'required', Rule::enum(StatutEmploi::class)],
        ];
    }
}
