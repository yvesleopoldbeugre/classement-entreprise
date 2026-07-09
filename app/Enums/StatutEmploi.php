<?php

namespace App\Enums;

enum StatutEmploi: string
{
    case Ancien = 'ancien';
    case Actuel = 'actuel';
    case JamaisTravaille = 'jamais_travaille';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
