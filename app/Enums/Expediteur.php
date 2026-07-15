<?php

namespace App\Enums;

enum Expediteur: string
{
    case Visiteur = 'visiteur';
    case Bot = 'bot';
    case Admin = 'admin';

    public function libelle(): string
    {
        return match ($this) {
            self::Visiteur => 'Visiteur',
            self::Bot => 'Assistant',
            self::Admin => 'Équipe',
        };
    }
}
