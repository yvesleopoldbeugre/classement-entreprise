<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
        // Inscription à faible friction : seuls email + mot de passe sont requis.
        // Le pseudo (et le nom) sont auto-générés s'ils ne sont pas fournis.
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'pseudo_public' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:users,pseudo_public'],
            'poste_actuel' => ['nullable', 'string', 'max:255'],
            'password' => ['required', Password::defaults()],
        ];
    }
}
