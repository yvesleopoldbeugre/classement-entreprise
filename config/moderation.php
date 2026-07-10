<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modération a priori
    |--------------------------------------------------------------------------
    | À true (défaut) : les contributions (avis, entretiens, missions) partent
    | en « en_attente » et doivent être publiées par un modérateur.
    | À false : elles sont publiées immédiatement (auto-publication).
    */
    'enabled' => env('MODERATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Seuil de signalements
    |--------------------------------------------------------------------------
    | Nombre de signalements distincts (1 par utilisateur) à partir duquel une
    | contribution est automatiquement masquée (statut « signale ») et envoyée
    | en modération. En-dessous, elle reste publique mais visible des modérateurs.
    */
    'seuil_signalements' => (int) env('MODERATION_SEUIL_SIGNALEMENTS', 3),

];
