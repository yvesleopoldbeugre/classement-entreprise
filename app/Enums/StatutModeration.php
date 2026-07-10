<?php

namespace App\Enums;

enum StatutModeration: string
{
    case EnAttente = 'en_attente';
    case Publie = 'publie';
    case Signale = 'signale';
    case Retire = 'retire';

    /**
     * Statut d'une nouvelle contribution : « en_attente » si la modération est
     * active (défaut), « publie » (auto-publication) si elle est désactivée.
     */
    public static function parDefaut(): self
    {
        return config('moderation.enabled', true) ? self::EnAttente : self::Publie;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
