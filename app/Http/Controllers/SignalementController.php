<?php

namespace App\Http\Controllers;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Mission;
use App\Models\RetourEntretien;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SignalementController extends Controller
{
    /** Signale une contribution : la retire du public et l'envoie en modération. */
    public function signaler(Request $request, string $type, int $id): RedirectResponse
    {
        $contribution = $this->resoudre($type, $id);

        // On ne signale pas sa propre contribution.
        abort_if($contribution->user_id === $request->user()->id, 403);

        $contribution->update(['statut_moderation' => StatutModeration::Signale]);

        return back()->with('success', 'Merci, ce contenu a été signalé aux modérateurs.');
    }

    private function resoudre(string $type, int $id): Model
    {
        return match ($type) {
            'avis' => AvisEntreprise::findOrFail($id),
            'entretien' => RetourEntretien::findOrFail($id),
            'mission' => Mission::findOrFail($id),
        };
    }
}
