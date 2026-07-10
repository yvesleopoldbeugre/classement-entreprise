<?php

/*
|--------------------------------------------------------------------------
| Entreprises réelles — liste initiale (fondateurs)
|--------------------------------------------------------------------------
| Entrées NEUTRES insérées en statut « a_verifier », SANS avis fabriqué.
| Le classement doit émerger de vrais avis modérés (protection juridique +
| crédibilité). `secteur_activite` est mis à « autre » par défaut : à préciser
| lors de la vérification admin (ainsi que commune, site_web, linkedin_url).
|
| Chaque ligne peut surcharger les valeurs par défaut du seeder si on connaît
| déjà le secteur/la commune, ex : ['nom' => 'X', 'secteur_activite' => 'ssii'].
*/

return [
    ['nom' => 'Worldev'],
    ['nom' => 'Neurones Technologiques'],
    ['nom' => 'Madata'],
    ['nom' => 'DDMA'],
    ['nom' => 'Plurielles Entreprise'],
    ['nom' => 'Agnexe technologie'],
    ['nom' => 'Halltech-Africa'],
    ['nom' => 'Ebenyx technologies'],
    ['nom' => 'Wagsystem'],
    ['nom' => 'Nexora'],
];
