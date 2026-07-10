<?php

namespace App\Enums;

enum TypeMission: string
{
    case Interim = 'interim';
    case Freelance = 'freelance';
    case Regie = 'regie';

    public function libelle(): string
    {
        return match ($this) {
            self::Interim => 'Intérim',
            self::Freelance => 'Freelance',
            self::Regie => 'Régie',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
