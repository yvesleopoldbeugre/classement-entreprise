<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfilRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'pseudo_public' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('users', 'pseudo_public')->ignore($this->user()?->id),
            ],
            'poste_actuel' => ['nullable', 'string', 'max:255'],
            // linkedin_verifie n'est pas modifiable par l'utilisateur (réservé à la vérification).
        ];
    }
}
