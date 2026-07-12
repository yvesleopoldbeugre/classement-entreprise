<?php

namespace App\Observers;

use App\Enums\TypeEvenement;
use App\Models\AvisEntreprise;
use App\Models\Entreprise;
use App\Models\Evenement;
use App\Services\ClassementService;

/**
 * Maintient à jour les scores dénormalisés de l'entreprise dès qu'un avis
 * est créé, modifié (ex: passage en statut « publie ») ou supprimé.
 */
class AvisEntrepriseObserver
{
    public function __construct(private readonly ClassementService $classement) {}

    public function created(AvisEntreprise $avis): void
    {
        Evenement::log(TypeEvenement::Avis, $avis, ['user_id' => $avis->user_id]);
    }

    public function saved(AvisEntreprise $avis): void
    {
        $this->recalculer($avis);
    }

    public function deleted(AvisEntreprise $avis): void
    {
        $this->recalculer($avis);
    }

    private function recalculer(AvisEntreprise $avis): void
    {
        // Si l'avis a changé d'entreprise, recalculer l'ancienne aussi.
        $ancienId = $avis->getOriginal('entreprise_id');
        if ($ancienId && $ancienId !== $avis->entreprise_id
            && ($ancienne = Entreprise::find($ancienId))) {
            $this->classement->recalculerEntreprise($ancienne);
        }

        if ($entreprise = $avis->entreprise()->first()) {
            $this->classement->recalculerEntreprise($entreprise);
        }
    }
}
