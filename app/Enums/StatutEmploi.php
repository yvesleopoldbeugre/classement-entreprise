<?php

namespace App\Enums;

enum StatutEmploi: string
{
    case Ancien = 'ancien';
    case Actuel = 'actuel';
    case JamaisTravaille = 'jamais_travaille';

    public function libelle(): string
    {
        return match ($this) {
            self::Ancien => 'Ancien employé',
            self::Actuel => 'Employé actuel',
            self::JamaisTravaille => 'N’y a jamais travaillé',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
