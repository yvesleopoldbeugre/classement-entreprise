<?php

namespace App\Http\Controllers;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Mission;
use App\Models\RetourEntretien;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ModerationController extends Controller
{
    /** Statuts qui demandent une action du modérateur. */
    private const A_TRAITER = [StatutModeration::EnAttente, StatutModeration::Signale];

    public function index(): View
    {
        return view('moderation.index', [
            'avis' => AvisEntreprise::whereIn('statut_moderation', self::A_TRAITER)
                ->withCount('signalements')->with(['user', 'entreprise'])->latest()->get(),
            'entretiens' => RetourEntretien::whereIn('statut_moderation', self::A_TRAITER)
                ->withCount('signalements')->with(['user', 'entreprise'])->latest()->get(),
            'missions' => Mission::whereIn('statut_moderation', self::A_TRAITER)
                ->withCount('signalements')->with(['user', 'entreprise'])->latest()->get(),
        ]);
    }

    public function publier(string $type, int $id): RedirectResponse
    {
        $contribution = $this->resoudre($type, $id);
        $contribution->update(['statut_moderation' => StatutModeration::Publie]);
        $contribution->signalements()->delete(); // décision prise : on repart de zéro

        return back()->with('success', 'Contribution publiée.');
    }

    public function retirer(string $type, int $id): RedirectResponse
    {
        $contribution = $this->resoudre($type, $id);
        $contribution->update(['statut_moderation' => StatutModeration::Retire]);
        $contribution->signalements()->delete();

        return back()->with('success', 'Contribution retirée.');
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
