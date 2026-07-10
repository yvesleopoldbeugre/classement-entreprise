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
    /**
     * Enregistre un signalement (1 par utilisateur et par contenu). La contribution
     * n'est masquée (statut « signale ») qu'une fois le seuil de signalements atteint.
     */
    public function signaler(Request $request, string $type, int $id): RedirectResponse
    {
        $contribution = $this->resoudre($type, $id);

        // On ne signale pas sa propre contribution.
        abort_if($contribution->user_id === $request->user()->id, 403);

        $motif = trim((string) $request->input('motif'));

        $contribution->signalements()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['motif' => $motif !== '' ? mb_substr($motif, 0, 255) : null],
        );

        // Masquage automatique au-delà du seuil.
        $seuil = (int) config('moderation.seuil_signalements', 3);
        if ($contribution->statut_moderation === StatutModeration::Publie
            && $contribution->signalements()->count() >= $seuil) {
            $contribution->update(['statut_moderation' => StatutModeration::Signale]);
        }

        return back()->with('success', 'Merci, votre signalement a été pris en compte.');
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
