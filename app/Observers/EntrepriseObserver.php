<?php

namespace App\Observers;

use App\Enums\StatutEntreprise;
use App\Enums\TypeEvenement;
use App\Models\Entreprise;
use App\Models\Evenement;

class EntrepriseObserver
{
    public function created(Entreprise $entreprise): void
    {
        // Créée directement vérifiée (admin) vs proposée par un utilisateur (à vérifier).
        $type = $entreprise->statut === StatutEntreprise::Verifiee
            ? TypeEvenement::EntrepriseVerifiee
            : TypeEvenement::EntrepriseProposee;

        Evenement::log($type, $entreprise);
    }
}
