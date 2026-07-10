<?php

namespace App\Http\Requests\Entreprise;

use App\Enums\SecteurActivite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProposerEntrepriseRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:255'],
            'secteur_activite' => ['required', Rule::enum(SecteurActivite::class)],
            'commune' => ['nullable', 'string', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'taille_estimee' => ['nullable', 'string', 'max:50'],
            'commentaire_proposition' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['commentaire_proposition' => 'commentaire'];
    }

    public function messages(): array
    {
        return [
            'commentaire_proposition.required' => 'Merci d’expliquer pourquoi vous proposez cette entreprise.',
            'commentaire_proposition.min' => 'Votre explication est un peu courte (10 caractères minimum).',
        ];
    }
}
