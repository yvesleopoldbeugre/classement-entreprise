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

];
