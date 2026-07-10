<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReponseEntrepriseController extends Controller
{
    /** Droit de réponse : enregistre (ou efface) la réponse officielle de l'entreprise. */
    public function update(Request $request, Entreprise $entreprise): RedirectResponse
    {
        $valide = $request->validate([
            'reponse_entreprise' => ['nullable', 'string', 'max:5000'],
        ]);

        $texte = trim((string) ($valide['reponse_entreprise'] ?? ''));

        $entreprise->update([
            'reponse_entreprise' => $texte ?: null,
            'reponse_entreprise_le' => $texte ? now() : null,
        ]);

        return back()->with('success', $texte ? 'Réponse de l’entreprise enregistrée.' : 'Réponse supprimée.');
    }
}
