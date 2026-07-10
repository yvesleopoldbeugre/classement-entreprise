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

    /*
    |--------------------------------------------------------------------------
    | Sortie automatique de la liste « à éviter »
    |--------------------------------------------------------------------------
    | Une entreprise quitte la zone « à éviter » (rang_a_eviter remis à null)
    | dès que sa note moyenne (les étoiles) atteint `note_min` sur `avis_min`
    | avis publiés. Le seuil d'avis évite qu'un ou deux avis flatteurs suffisent.
    */
    'sortie_a_eviter' => [
        'note_min' => (float) env('CLASSEMENT_SORTIE_NOTE', 3.5),
        'avis_min' => (int) env('CLASSEMENT_SORTIE_AVIS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pondération des avis (confiance du contributeur + récence)
    |--------------------------------------------------------------------------
    | La note d'une entreprise est une moyenne PONDÉRÉE : chaque avis pèse
    | poids = confiance × récence.
    |  - confiance : selon le niveau de vérification de l'auteur.
    |  - récence : décroissance exponentielle (demi-vie en jours ; 0 = désactivé).
    */
    'ponderation' => [
        'confiance' => [
            'linkedin' => (float) env('CLASSEMENT_POIDS_LINKEDIN', 1.0),
            'email' => (float) env('CLASSEMENT_POIDS_EMAIL', 0.6),
            'defaut' => (float) env('CLASSEMENT_POIDS_DEFAUT', 0.3),
        ],
        'recence_demi_vie_jours' => (int) env('CLASSEMENT_RECENCE_DEMI_VIE', 540),
    ],
];
