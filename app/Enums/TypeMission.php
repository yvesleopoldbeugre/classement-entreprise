<?php

namespace App\Enums;

enum TypeMission: string
{
    case Interim = 'interim';
    case Freelance = 'freelance';
    case Regie = 'regie';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
