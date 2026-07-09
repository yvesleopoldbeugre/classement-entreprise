<?php

namespace App\Http\Requests\Entreprise;

use App\Enums\SecteurActivite;
use App\Enums\StatutEntreprise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEntrepriseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('entreprises', 'slug')->ignore($this->route('entreprise')),
            ],
            'secteur_activite' => ['sometimes', 'required', Rule::enum(SecteurActivite::class)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'commune' => ['nullable', 'string', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'taille_estimee' => ['nullable', 'string', 'max:50'],
            'date_creation' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:'.((int) date('Y') + 1)],
            'source_scraping' => ['nullable', 'string', 'max:255'],
            'statut' => ['sometimes', Rule::enum(StatutEntreprise::class)],
        ];
    }
}
