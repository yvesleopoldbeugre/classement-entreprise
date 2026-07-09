<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seuil bayésien (m)
    |--------------------------------------------------------------------------
    | Nombre "de confiance" d'avis. Tant qu'une entreprise a moins de m avis,
    | son score est tiré vers la moyenne globale du site (C). Plus m est grand,
    | plus on exige d'avis avant de faire confiance à la note d'une entreprise.
    */
    'seuil_avis' => (int) env('CLASSEMENT_SEUIL_AVIS', 5),

    /*
    |--------------------------------------------------------------------------
    | Moyenne globale par défaut (C de repli)
    |--------------------------------------------------------------------------
    | Utilisée quand le site n'a encore aucun avis publié pour calculer une
    | vraie moyenne globale. Sur une échelle 1-5, 3.0 est un point neutre.
    */
    'moyenne_defaut' => (float) env('CLASSEMENT_MOYENNE_DEFAUT', 3.0),

    /*
    |--------------------------------------------------------------------------
    | Nombre minimum d'avis pour figurer dans le classement public
    |--------------------------------------------------------------------------
    | En-dessous, l'entreprise reste consultable mais n'est pas "classée".
    */
    'min_avis_classement' => (int) env('CLASSEMENT_MIN_AVIS', 3),
];
