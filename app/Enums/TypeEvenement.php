<?php

namespace App\Enums;

enum TypeEvenement: string
{
    case Visite = 'visite';
    case Inscription = 'inscription';
    case Connexion = 'connexion';
    case Avis = 'avis';
    case Entretien = 'entretien';
    case Mission = 'mission';
    case Signalement = 'signalement';
    case EntrepriseProposee = 'entreprise_proposee';
    case EntrepriseVerifiee = 'entreprise_verifiee';
    case Moderation = 'moderation';

    public function libelle(): string
    {
        return match ($this) {
            self::Visite => 'Visites',
            self::Inscription => 'Inscriptions',
            self::Connexion => 'Connexions',
            self::Avis => 'Avis',
            self::Entretien => 'Retours d’entretien',
            self::Mission => 'Missions',
            self::Signalement => 'Signalements',
            self::EntrepriseProposee => 'Entreprises proposées',
            self::EntrepriseVerifiee => 'Entreprises vérifiées',
            self::Moderation => 'Actions de modération',
        };
    }

    /** Une visite de page (pour distinguer trafic vs contributions dans les graphes). */
    public function estVisite(): bool
    {
        return $this === self::Visite;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
