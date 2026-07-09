<?php

namespace App\Enums;

enum SecteurActivite: string
{
    case Ssii = 'ssii';
    case BanqueDsi = 'banque_dsi';
    case Startup = 'startup';
    case Telecom = 'telecom';
    case Integrateur = 'integrateur';
    case Autre = 'autre';

    public function libelle(): string
    {
        return match ($this) {
            self::Ssii => 'SSII / ESN',
            self::BanqueDsi => 'Banque / DSI',
            self::Startup => 'Startup',
            self::Telecom => 'Télécom',
            self::Integrateur => 'Intégrateur',
            self::Autre => 'Autre',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
