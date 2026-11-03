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

    /** Segment d'URL de la page secteur (ex. « banque-dsi »). */
    public function slug(): string
    {
        return str_replace('_', '-', $this->value);
    }

    public static function depuisSlug(string $slug): ?self
    {
        return self::tryFrom(str_replace('-', '_', $slug));
    }

    /** Valeur d'enum depuis une saisie libre (valeur, slug ou libellé), sinon null. */
    public static function depuisSaisie(?string $saisie): ?self
    {
        $saisie = mb_strtolower(trim((string) $saisie));

        foreach (self::cases() as $cas) {
            if (in_array($saisie, [$cas->value, $cas->slug(), mb_strtolower($cas->libelle())], true)) {
                return $cas;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
