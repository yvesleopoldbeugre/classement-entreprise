<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\StatutModeration;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\User;

/**
 * Flux « avis d'abord » : un invité rédige son avis, qui est gardé en session,
 * puis se connecte ou s'inscrit (mot de passe, lien magique ou SSO). Quel que
 * soit le moyen, l'avis mémorisé est alors enregistré pour ce compte.
 */
trait PublieAvisEnAttente
{
    /** Enregistre l'avis mémorisé en session ; retourne l'entreprise concernée, ou null. */
    protected function publierAvisEnAttente(User $user): ?Entreprise
    {
        $data = session()->pull('avis_en_attente');
        $entrepriseId = session()->pull('avis_en_attente_entreprise');

        if (empty($data) || empty($entrepriseId) || ! ($entreprise = Entreprise::find($entrepriseId))) {
            return null;
        }

        // Ne pas dupliquer si ce compte a déjà un avis sur cette entreprise.
        $dejaAvis = AvisEntreprise::where('entreprise_id', $entreprise->id)
            ->where('user_id', $user->id)
            ->exists();

        if (! $dejaAvis) {
            AvisEntreprise::create([
                ...$data,
                'user_id' => $user->id,
                'statut_moderation' => StatutModeration::parDefaut(),
            ]);
        }

        return $entreprise;
    }
}
