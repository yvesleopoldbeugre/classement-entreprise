<?php

namespace App\Enums;

enum StatutModeration: string
{
    case EnAttente = 'en_attente';
    case Publie = 'publie';
    case Signale = 'signale';
    case Retire = 'retire';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
