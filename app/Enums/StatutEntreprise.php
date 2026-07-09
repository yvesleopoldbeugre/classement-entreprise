<?php

namespace App\Enums;

enum StatutEntreprise: string
{
    case AVerifier = 'a_verifier';
    case Verifiee = 'verifiee';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
