<?php

return [

    // Message d'accueil envoyé automatiquement à l'ouverture du chat.
    'greeting' => 'Bonjour 👋 Je suis l’assistant de Note ta boîte. Je peux vous aider à noter une entreprise, '
        .'créer un compte ou comprendre le fonctionnement. Posez votre question !',

    // Réponse quand aucune règle ne correspond → la question est transmise à l'équipe.
    'fallback' => 'Bonne question ! Je transmets ça à notre équipe, qui vous répondra ici même. '
        .'En attendant, n’hésitez pas à explorer le classement 🙂',

    /*
    | Base de connaissances : la 1ʳᵉ règle dont un mot-clé (sans accent, minuscule)
    | apparaît dans le message gagne. Les plus spécifiques d'abord.
    */
    'regles' => [
        [
            'mots' => ['gratuit', 'prix', 'payer', 'payant', 'cout', 'combien'],
            'reponse' => 'C’est 100 % gratuit, pour lire comme pour publier vos avis. 🙌',
        ],
        [
            'mots' => ['anonym', 'confidential', 'mon nom', 'vie privee'],
            'reponse' => 'Vos avis sont anonymes : seul votre pseudo public est affiché, jamais votre identité.',
        ],
        [
            'mots' => ['donner mon avis', 'noter', 'laisser un avis', 'ecrire un avis', 'comment ca marche', 'comment faire'],
            'reponse' => 'Pour noter une entreprise : cherchez-la, ouvrez sa fiche puis cliquez « Donner mon avis ». '
                .'Pas besoin de compte pour commencer — il est créé au moment d’envoyer. ✍️',
        ],
        [
            'mots' => ['proposer', 'ajouter une entreprise', 'entreprise absente', 'pas dans la liste', 'introuvable'],
            'reponse' => 'Votre entreprise n’est pas listée ? Cliquez « + Proposer une entreprise » : elle sera ajoutée après vérification.',
        ],
        [
            'mots' => ['lien magique', 'sans mot de passe', 'mot de passe oublie', 'reinitialiser'],
            'reponse' => 'Vous pouvez vous connecter sans mot de passe : saisissez votre email, vous recevez un lien de connexion. 🔑',
        ],
        [
            'mots' => ['creer un compte', 'inscription', 'inscrire', 's inscrire', 'compte'],
            'reponse' => 'Créer un compte prend 30 s : juste un email + un mot de passe (ou un lien magique, sans mot de passe).',
        ],
        [
            'mots' => ['moderation', 'publie', 'valide', 'apparait pas', 'valider mon avis', 'combien de temps'],
            'reponse' => 'Chaque avis passe par une modération avant d’être publié, pour garder le classement fiable.',
        ],
        [
            'mots' => ['fiable', 'confiance', 'faux avis', 'note calcul', 'score', 'ponderation'],
            'reponse' => 'La note est une moyenne pondérée : les avis de comptes vérifiés (email, LinkedIn) et récents pèsent davantage.',
        ],
        [
            'mots' => ['supprimer', 'rectifier', 'droit de reponse', 'entreprise concernee', 'diffamation', 'reclamation'],
            'reponse' => 'Une entreprise concernée peut demander un droit de réponse ou une rectification — écrivez-nous ici, on s’en occupe.',
        ],
        [
            'mots' => ['bonjour', 'salut', 'bonsoir', 'coucou', 'hello', 'yo '],
            'reponse' => 'Bonjour 🙂 Comment puis-je vous aider ? (noter une entreprise, créer un compte, etc.)',
        ],
        [
            'mots' => ['merci', 'super', 'parfait', 'top', 'genial'],
            'reponse' => 'Avec plaisir ! 😊 Autre chose ?',
        ],
    ],

    // Mots déclenchant directement l'escalade vers un humain (pas de réponse auto).
    'escalade' => ['humain', 'quelqu un', 'conseiller', 'parler a', 'agent', 'reel', 'vrai personne'],

    // Purge des conversations inactives (jours).
    'purge_jours' => (int) env('CHAT_PURGE_JOURS', 90),

    // Fenêtre de présence « en ligne » (secondes).
    'presence_ttl' => (int) env('PRESENCE_TTL', 90),
];
