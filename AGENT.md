# AGENT.md — Classement des entreprises (Côte d'Ivoire)

Guide de connaissance du projet pour un agent (ou un dev) qui reprend le code.

## 1. Objectif du produit

Plateforme de **classement d'entreprises** basé sur les retours de personnes ayant
travaillé avec elles (salarié, stage, mission, freelance, entretien). But : rassurer
les futurs candidats/prestataires en montrant quelles entreprises sont les plus fiables.

Trois types de contributions alimentent une entreprise :

- **Avis** (`avis_entreprises`) — 4 notes 1-5 (ambiance, management, salaire, évolution). **C'est la seule source du score de classement.**
- **Retours d'entretien** (`retours_entretiens`) — process de recrutement (étapes, délais, offre). Informatif, **hors score** pour l'instant.
- **Missions** (`missions`) — interim/freelance/régie (paiement à temps, respect du contrat). Informatif, **hors score** pour l'instant.

## 2. Stack technique

| Élément | Détail |
|---|---|
| Framework | Laravel **13.19** |
| PHP | **8.4** |
| Base de données | **MySQL 8** (SQLite abandonné) |
| API auth | **Laravel Sanctum** (tokens Bearer) |
| Conteneurisation | Docker (PHP-FPM Alpine + Nginx + MySQL) |
| Front tooling | Vite + Tailwind v4 (service `node`, profil `dev`) |
| Formatage | **Pint** — lancer `./vendor/bin/pint` avant de committer |

## 2b. Façon de travailler (workflow de dev)

**Cycle pour toute modification** :
1. **Coder** en suivant les conventions du projet (voir plus bas).
2. **Formater** : `./vendor/bin/pint <fichiers>` (hors conteneur).
3. **Rebuild des assets** si un fichier Blade / JS / CSS a changé (sinon les classes Tailwind ou le JS ne sont pas à jour) :
   `docker compose run --rm --no-deps node npm run build`
4. **Migrer** si nouvelle migration : `docker compose exec app php artisan migrate --force`.
5. **Tester** (⚠️ les `-e` sont **obligatoires**, voir piège Docker) :
   `docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_HOST=127.0.0.1 app php artisan test`
6. **Vérifier en réel** via `curl` sur http://localhost:8088 (admin de dev : `test@example.com` / `password`). Pour les parcours authentifiés : récupérer le `_token` CSRF d'une page, poser un cookie jar, POST `/connexion`, puis appeler la route. ⚠️ éviter `curl -L -X POST` (curl refait un POST sur la redirection → 405 sur une route GET).
7. **Committer** : `git add -A` → **vérifier que `.env` n'est pas stagé** → message clair terminé par `Co-Authored-By: Claude…` → `git push origin main`. Les fichiers partagés (routes, `app.js`, la fiche, `ClassementService`…) étant très entrelacés, un **commit consolidé par lot** est acceptable plutôt que des commits qui ne compilent pas.

**Conventions de code** :
- Modèles en **attributs PHP** (`#[Fillable]`, `#[Hidden]`, `#[ObservedBy]`) — pas de propriétés `$fillable`. Enums PHP backed + `Rule::enum` dans les FormRequests. Casts d'enums/dates dans `casts()`.
- Vues : composants Blade anonymes (`<x-…>`) ; classes Tailwind **littérales** (pas d'interpolation).
- FR partout : `APP_LOCALE=fr` + `Carbon::setLocale('fr')`. Messages de validation traduits dans `lang/fr/validation.php` (avec la table `attributes` pour des noms de champs FR) ; le reste retombe sur l'anglais du vendor (`APP_FALLBACK_LOCALE=en`). ⚠️ changer `APP_LOCALE` nécessite `docker compose up -d app` (env_file).

**Sécurité (systématique, dans tout nouveau code)** :
- `user_id` = `auth()->id()` **côté contrôleur**, jamais depuis l'input.
- `statut_moderation` d'une nouvelle contribution = `StatutModeration::parDefaut()` (jamais l'input).
- `statut` d'une entreprise = forcé selon `can('moderer')` (jamais l'input).
- `is_admin` **hors `$fillable`** → assigner directement puis `save()`.
- Propriété d'un contenu : `abort_unless($m->user_id === $request->user()->id, 403)` sur update/destroy/signalement.

**Pièges transverses à connaître AVANT de coder** :
- **`.env` + Docker** : chargé au **démarrage du conteneur** via `env_file`. Toute modif de `.env` (flags `*_ENABLED`, ports, credentials) exige `docker compose up -d app` pour être prise en compte. Les tests contournent via les `-e`.
- **Noms de routes globaux** : web **et** api partagent l'espace de noms. Ne jamais réutiliser un nom déjà pris (ex. `entreprises.store` = `apiResource`) — `route('nom')` résout vers la **dernière** enregistrée (bug silencieux).
- **Tailwind v4** : uniquement des classes littérales dans le Blade.
- **Flash & confirmations** : messages flash (`success`/`warning`/`error`/`info`) → **toast SweetAlert** (via `#flash-message` + `app.js`) ; toute action destructive/importante = `<form data-confirm="…">` (confirmation SweetAlert, avec `data-confirm-select` optionnel pour un motif).
- **Boutons d'envoi** : le JS (`marquerEnvoi`) ajoute automatiquement un spinner + `disabled` pendant la soumission (formulaires classiques et confirmés).

## 3. Modèle de données

Migrations dans `database/migrations/2026_07_09_1000*` :

- `entreprises` — infos + **colonnes de score dénormalisées** (`nb_avis_total`, `moy_ambiance/management/salaire/evolution`, `note_globale`, `score_bayesien` indexé).
- `avis_entreprises` — unique `(entreprise_id, user_id)` → **1 avis par user/entreprise**.
- `retours_entretiens` — `date_entretien_mois` stockée **au 1er du mois**, `questions_posees` en JSON.
- `missions`.
- `users` — champs profil ajoutés : `pseudo_public` (unique), `poste_actuel`, `linkedin_verifie`.

Tous les statuts sont des **enums PHP backed** dans `app/Enums/` :
`SecteurActivite`, `StatutEntreprise`, `StatutEmploi`, `StatutModeration`, `TypeMission`.
Chaque enum a une méthode statique `values()` (utilisée dans les migrations) ; `SecteurActivite` a aussi `libelle()`.

## 4. Le classement — cœur métier

Fichier : **`app/Services/ClassementService.php`**.

Score = **moyenne bayésienne** (approche IMDb) pour éviter qu'une entreprise avec 1 seul
avis 5★ domine :

```
score = (v / (v + m)) * R  +  (m / (v + m)) * C
```

- `R` = note moyenne de l'entreprise (moyenne des 4 dimensions, avis **publiés**).
- `v` = nombre d'avis publiés de l'entreprise.
- `m` = seuil de confiance → `config('classement.seuil_avis')` (défaut **5**).
- `C` = note moyenne globale du site (tous avis publiés).

**Pondération des avis** (dans `statsAvis`) : R est une **moyenne pondérée**, chaque avis pesant `confiance(auteur) × récence`. Config `classement.ponderation` :
- `confiance` : LinkedIn vérifié (`1.0`) > email vérifié (`0.6`) > non vérifié (`0.3`) — s'appuie sur `users.linkedin_verifie` / `email_verified_at`.
- `recence_demi_vie_jours` (défaut 540 = 18 mois ; `0` désactive) : décroissance exponentielle `2^(-âge/demi_vie)`.
Couvert par `PonderationAvisTest`. C reste une moyenne simple (prior).

Réglages dans **`config/classement.php`** : `seuil_avis`, `min_avis_classement` (défaut 3,
seuil pour figurer au classement public via le scope `classable()`), `moyenne_defaut`.

**Recalcul** :
- `recalculerEntreprise($e)` — une entreprise, utilise le `C` courant.
- `recalculerTout(?callable $apres)` — toutes les entreprises de façon cohérente, retourne le nombre traité (callback optionnel de progression). **À utiliser après un import/seed** ou périodiquement (car ajouter des avis fait bouger `C` pour tout le monde).

**Commande** : `php artisan classement:recalculer` (option `--entreprise=<id|slug>` pour une seule). Planifiée **tous les jours à 03:00** dans `routes/console.php` (`Schedule::command(...)->dailyAt('03:00')->withoutOverlapping()`).

Le scheduler tourne en continu via le **service Docker `scheduler`** (voir §6) qui lance `php artisan schedule:work`. En prod hors Docker, prévoir à la place un cron système appelant `php artisan schedule:run` chaque minute.

**Observer** : `app/Observers/AvisEntrepriseObserver.php` (branché via `#[ObservedBy]` sur
le modèle `AvisEntreprise`) recalcule automatiquement l'entreprise à chaque
création/modification/suppression d'avis.

⚠️ **Piège** : `DatabaseSeeder` utilise `WithoutModelEvents` → l'observer **ne se déclenche
pas** pendant le seeding. `EntrepriseReelleSeeder` n'insère aucun avis (rien à recalculer) ;
tout futur seeder qui crée des avis devra appeler `recalculerTout()` explicitement à la fin.

## 5. Couche HTTP

- **Requests** : `app/Http/Requests/{Entité}/Store*.php` + `Update*.php`. Règles de validation, enums via `Rule::enum`, unique (slug, 1 avis/user/entreprise). `RetourEntretien` normalise la date au 1er du mois dans `prepareForValidation()`.
- **Resources** : `app/Http/Resources/*Resource.php`. Enums sérialisés en `.value`, relations via `whenLoaded`. `EntrepriseResource` expose un bloc `classement`. `UserResource` masque `name`/`email` sauf pour le propriétaire.
- **Contrôleurs** : `app/Http/Controllers/Api/*Controller.php` (API resource controllers).
- **Routes** : `routes/api.php` — GET publics (classement/consultation) ; POST/PUT/DELETE sous `auth:sanctum`.

**Conventions de sécurité importantes** (respecter dans tout nouveau code) :
- `user_id` n'est **jamais** dans un Store request → toujours `= $request->user()->id` dans le contrôleur.
- `statut_moderation` n'est **jamais** soumis par l'utilisateur → forcé via `StatutModeration::parDefaut()` (en attente si modération active, sinon publié directement).
- Sur update/destroy des contributions : `abort_unless($model->user_id === $request->user()->id, 403)` (à remplacer par des **Policies** à terme).

⚠️ **Piège de nommage** : `Route::apiResource('avis', ...)` génère le paramètre `{avi}`
(Laravel singularise `avis` → `avi`). Le contrôleur `AvisEntrepriseController` utilise donc
`$avi`. `retours-entretiens` a son paramètre forcé à `retoursEntretien` via `->parameters()`.

## 5b. Documentation API (OpenAPI / Scramble)

Package **`dedoc/scramble`** — génère la doc OpenAPI 3.1 **automatiquement** depuis les
contrôleurs, FormRequests (corps + validation) et Resources (schémas de réponse). Aucune
annotation à écrire.

- UI interactive : **`/docs/api`** (ex. http://localhost:8088/docs/api)
- Spec JSON : **`/docs/api.json`**
- Config : `config/scramble.php` (titre = `APP_NAME`, description personnalisée dans `info`).
- Auth Bearer déclarée dans `AppServiceProvider::boot()` via `Scramble::configure()->withDocumentTransformers(... $openApi->secure(SecurityScheme::http('bearer')))` → bouton **Authorize** dans l'UI. (Sécurité posée globalement ; les GET publics restent accessibles sans token.)
- Cache : `php artisan scramble:clear` après un changement de config/description ; `scramble:cache` pour préchauffer.

⚠️ En dehors de `local`, l'accès à la doc est protégé par le middleware `RestrictedDocsAccess`
(voir `config/scramble.php`).

## 4b. Référentiel & seeders

- **`EntrepriseReelleSeeder`** — insère le référentiel réel depuis `database/data/entreprises_fondateurs.php` (idempotent via `updateOrCreate` sur le slug). Entrées **neutres** : `statut = a_verifier`, `source_scraping = liste_fondateurs`, **aucun avis fabriqué**. S'exécute en prod **et** en dev.
- **Démo supprimée** : plus de seeder de fausses entreprises. `DatabaseSeeder` ne crée, en `local`, qu'un **admin de dev** (`test@example.com` / `password`) pour tester la modération.
- **Liste éditoriale « à éviter »** : colonne `entreprises.rang_a_eviter` (position 1..N, null = hors liste), scope `Entreprise::aEviter()`. Alimentée par l'ordre du fichier `entreprises_fondateurs.php`. Affichée en tête de l'accueil via `classement/partials/a_eviter` **avec disclaimer** (opinion communautaire, droit de réponse) — pas de notes chiffrées inventées.
- **3 vues sur l'accueil** (sélecteur `vue` dans le filtre, `ClassementController@index`) : (1) **`a_eviter`** (défaut) — liste éditoriale ordonnée ; (2) **`classement`** — entreprises **vérifiées** (`statut = verifiee`, hors `rang_a_eviter`) triées par score (`NULL` en bas), les sans-avis marquées **« Nouveau »** ; (3) **`nouvelles`** — vérifiées avec 0 avis. Les propositions non vérifiées (`a_verifier`) n'apparaissent nulle part publiquement. Une entreprise vérifiée n'est **jamais** dans les pires (filtre `rang_a_eviter` null).
- **Sortie automatique de « à éviter »** : dans `ClassementService::appliquerStats` (donc à chaque avis publié via l'observer), une entreprise voit son `rang_a_eviter` remis à `null` dès que sa **note moyenne** ≥ `config('classement.sortie_a_eviter.note_min')` (défaut 3.5) sur ≥ `avis_min` (défaut 5) avis publiés. La règle ne fait que **retirer** de la liste (jamais ajouter). Couvert par `SortieAEviterTest`.

**Collecte de données (à construire)** — plan validé : (1) commande `entreprises:importer` (CSV/JSON, upsert par slug, `a_verifier`) ; (2) `entreprises:importer-google` via **Google Places API** (clé `GOOGLE_MAPS_API_KEY`). **Pas de scraping LinkedIn/Google SERP** (CGU) : `linkedin_url` saisi manuellement à la vérification.

## 5c. Front (Blade + Tailwind v4)

Interface **server-rendered** (pas de SPA) :

- Routes web : `routes/web.php` → `ClassementController@index` (`/`, le classement) et `@show` (`/entreprises/{slug}`, la fiche).
- Vues : `resources/views/classement/index.blade.php`, `resources/views/entreprises/show.blade.php`.
- Composants Blade anonymes : `resources/views/components/` → `<x-layout>`, `<x-note-etoiles :note>`, `<x-jauge label valeur>`.
- Styles : **Tailwind v4** via `resources/css/app.css` (`@import 'tailwindcss'`), buildé par Vite.

⚠️ **Piège Tailwind v4** : les classes doivent apparaître **littéralement** dans le Blade —
pas de `bg-{{ $ton }}-50` (non détecté au scan). Utiliser un `match()` renvoyant la classe
complète (`'bg-emerald-50 text-emerald-700'`). Voir les vues pour le pattern.

**Partage de lien / SEO (OpenGraph + Twitter Card)** : centralisé dans `<x-layout>`
(`resources/views/components/layout.blade.php`). Props surchargeables par page :
`title`, `description`, `ogImage` (URL absolue), `ogType` (`website` par défaut, `article`
pour une fiche). Les URLs (`og:url`, `og:image`) sont absolues via `url()->current()` /
`asset()` → en prod, HTTPS + domaine grâce au `trustProxies` (Traefik). L'image par défaut
est `public/og-image.png` (1200×630, carte de marque). La fiche entreprise
(`entreprises/show.blade.php`) passe une `:description` dynamique (note, rang, secteur) et
`og-type="article"`. Pour régénérer l'image : `php scripts/make-og-image.php`
(GD, police système `Arial Unicode`, écrit `public/og-image.png`) — à relancer si le branding change.

**Build des assets** (obligatoire pour que les pages soient stylées) :
```bash
docker compose run --rm --no-deps node sh -c "npm install && npm run build"   # → public/build/
# ou en dev avec HMR :
docker compose --profile dev up -d node
```

Locale d'affichage : `Carbon::setLocale('fr')` dans `AppServiceProvider` (dates « janvier 2026 », « il y a 2 jours »).

**Responsive** : mobile-first Tailwind. La barre de nav a une version **bureau** (`hidden md:flex`) et un **menu hamburger mobile** (`[data-menu-toggle]` → toggle `#menu-mobile`, JS dans `app.js`) ; les liens sont factorisés dans `partials/nav-links`. Les grilles/formulaires passent en 1 colonne sous `sm`, les listes utilisent `min-w-0`/`truncate`, les modals `overflow-y-auto`. Point de vigilance : ne pas mettre `overflow-x-hidden` sur `<body>` (casserait le header `sticky`).

**Interactions JS** (`resources/js/app.js`, vanilla + **SweetAlert2**) :
- **Confirmation SweetAlert** : tout `<form data-confirm="…">` déclenche une boîte de confirmation avant envoi (attributs optionnels `data-confirm-title`, `data-confirm-button`, `data-confirm-icon`). Utilisé sur le formulaire de **déconnexion** (`partials/nav-links`). `form.submit()` post-confirmation ne redéclenche pas l'événement → pas de boucle. La CSS SweetAlert2 est importée dans `app.js` (émise en second fichier CSS, injecté par `@vite`).

- **Modals de contribution** : sur la fiche, les boutons `data-modal-open="avis|entretien|mission"` ouvrent des `<x-modal>` contenant les formulaires (partials `contributions/partials/*`, réutilisés aussi par les pages `create` en fallback no-JS). En cas d'erreur de validation, le POST redirige vers la fiche avec un marqueur `_form` ; le layout pose `data-open-modal` sur `<body>` et le JS **rouvre la bonne modal** avec les erreurs.
- **Filtre AJAX du classement** : le `#filtre-form` (recherche + secteur) recharge uniquement `#liste-classement` via `fetch` (header `X-Requested-With`), avec loader `#liste-loader`. Côté serveur, `ClassementController@index` renvoie le partial `classement/partials/liste` quand `$request->ajax()`. La pagination et le `popstate` (bouton précédent) passent aussi en AJAX ; l'URL est mise à jour via `history.pushState`.

## 5d. Auth, contributions & modération (web)

- **Auth** : session classique (`AuthController`), routes `/inscription`, `/connexion`, `/deconnexion`. Vues `resources/views/auth/`. `RegisterRequest`/`LoginRequest` dans `app/Http/Requests/Auth/`. Champs mot de passe = `<x-password-input>` avec bouton « voir/masquer » (JS `[data-toggle-password]`).
- **SSO (Socialite)** : `SocialiteController` + routes `/auth/{provider}/redirect|callback` (`google`, `github`, `facebook`, `linkedin` → driver `linkedin-openid`). Boutons sur l'inscription (`auth/partials/sso`). Colonnes `users.provider` / `provider_id` (+ `password` nullable pour les comptes SSO). Identifiants OAuth à renseigner dans `.env` (`{PROVIDER}_CLIENT_ID/SECRET`) ; sans config, le bouton redirige avec un message « non configuré ». Callback à déclarer chez le fournisseur : `{APP_URL}/auth/{provider}/callback`.
  - **Interrupteur global** : `SSO_ENABLED=false` (config `services.sso.enabled`) → les boutons **et** le séparateur disparaissent de l'inscription, et les routes SSO ne sont plus enregistrées (404). La vue référence `route('social.redirect')` uniquement sous le même `@if`, donc pas d'erreur quand c'est désactivé.
- **Contributions** : formulaires web sous `auth` → `ContributionController` (avis/entretien/mission), scopés à une entreprise (`/entreprises/{slug}/avis`…). **Réutilisent les mêmes `Store*Request` que l'API** (validation identique) ; sur erreur web → redirect back, sur `api/*` → JSON. À la création : `user_id = auth`, `statut_moderation = en_attente`.
- **Modération** : `ModerationController` sous `auth` + `can:moderer`. Le Gate `moderer` (défini dans `AppServiceProvider`) autorise si `user.is_admin`. Publier un avis déclenche l'observer → recalcul du score.
  - **Désactivable** : `MODERATION_ENABLED=false` (config `moderation.enabled`) → les contributions sont créées directement en `publie` au lieu de `en_attente`. Centralisé dans `StatutModeration::parDefaut()`, utilisé par les 6 `store` (web + API).
- **Créer un admin** : `php artisan admin:creer {email} [--name=] [--pseudo=] [--password=]` — crée le compte (ou promeut un utilisateur existant). `is_admin` n'est pas `fillable`, la commande l'assigne explicitement. Interactif si les options manquent.
- **Signalement (à seuil)** : table polymorphe `signalements` (trait `Concerns\EstSignalable` sur les 3 modèles, `morphMany`), **1 signalement par utilisateur et par contenu** (contrainte unique). `SignalementController` (`POST /signaler/{type}/{id}`, auth) crée un signalement ; la contribution n'est masquée (`signale` → file `/moderation`) **qu'au seuil** `config('moderation.seuil_signalements')` (défaut 3) — plus de masquage au premier signalement. On ne peut pas signaler sa propre contribution. Publier/retirer en modération **remet les signalements à zéro**. Le signalement demande un **motif** (select dans l'alerte SweetAlert → champ `motif`) ; la file de modération affiche le compteur (⚑ n) et les motifs distincts. Bouton `<x-signaler>`. Couvert par `SignalementReponseTest`.
  - **Confirmation générique avec select** : un `<form data-confirm data-confirm-select='{"v":"label"}' data-confirm-select-name="motif">` fait saisir une valeur dans l'alerte SweetAlert, injectée dans un champ caché avant l'envoi (voir `app.js`).
- **Ajout d'entreprise** : `EntrepriseController` (web), `ProposerEntrepriseRequest` (≠ `StoreEntrepriseRequest` de l'API : ajoute un **`commentaire_proposition` obligatoire**, min 10 car., stocké et affiché aux modérateurs). Formulaire en **modal** sur l'accueil (`<x-modal id="proposer">` + `entreprises/partials/proposer`, bouton `data-modal-open="proposer"`, réouverture via `_form=proposer`) ; page `/proposer-entreprise` conservée en fallback no-JS. Un **utilisateur** propose → `statut = a_verifier` + **redirigé vers le classement** (sa proposition n'est pas encore publique) ; un **admin** (`can:moderer`) → `statut = verifiee` + redirigé vers la fiche (le `statut` du formulaire est toujours écrasé). Le modérateur voit les `a_verifier` dans `/moderation` (section « Entreprises à vérifier », avec la justification) → **Vérifier** (`PUT .../verifier`) ou **Supprimer** (`DELETE`). Une fois **vérifiée**, l'entreprise apparaît dans la vue `classement`/`nouvelles` avec le badge « Nouveau » (cf. §4b). Badge « ⏳ En vérification » / « ✓ Vérifiée » sur la fiche. Couvert par `EntrepriseCreationTest` + `ClassementVueTest`.
  - ⚠️ **Nom de route** : la route web de création s'appelle **`entreprises.proposer`** (pas `entreprises.store`) car ce dernier est déjà pris par l'`apiResource` (`routes/api.php`) — les noms de routes sont globaux, une collision fait résoudre `route()` vers la mauvaise route.
- **Droit de réponse** : colonnes `entreprises.reponse_entreprise` + `reponse_entreprise_le`. `ReponseEntrepriseController` (route `PUT /entreprises/{entreprise}/reponse`, `can:moderer`). Éditable par un admin sur la fiche ; affichée dans un bloc « Réponse de l'entreprise ». *(MVP : édition par admin faute de comptes « entreprise » ; évolution = comptes revendiqués/vérifiés.)*

⚠️ **`is_admin` n'est PAS dans `$fillable`** (anti-escalade de privilège). Pour promouvoir un
admin : `$u->is_admin = true; $u->save();` (pas `update([...])` qui l'ignore). Les factories
peuvent le passer via `->create(['is_admin' => true])`.

## 6. Docker — lancer le projet

```bash
docker compose up -d --build                       # build + démarre app, web, db
docker compose exec app php artisan migrate --seed # migrations + 10 entreprises réelles (+ admin de dev en local)
# App / API → http://localhost:8088   (préfixe API : /api)
```

- Ports hôte configurables dans `.env` : `APP_PORT` (défaut **8088**), `DB_FORWARD_PORT` (**3307**), `VITE_PORT` (5173).
- MySQL accessible depuis l'hôte : `127.0.0.1:3307`, user `classement` / `secret`, base `classement_entreprise`.
- Vite/HMR (front) : `docker compose --profile dev up -d node`.
- Service `scheduler` : lance `schedule:work` en continu (recalcul nocturne des scores). Démarré automatiquement avec `docker compose up -d`.
- Le point d'entrée `docker/php/entrypoint.sh` fait au démarrage : `composer install` (si besoin) → attente MySQL → `migrate`.
- **Anti-502** : `app` a un healthcheck (php-fpm sur :9000 via `fsockopen`) et `web` attend `app: service_healthy`. Au redémarrage, Nginx ne démarre qu'une fois php-fpm prêt → plus de 502 transitoire (fini le souci de la veille du Mac).

⚠️ **Piège Docker** : Compose n'injecte pas `.env` dans l'environnement du conteneur (il ne
s'en sert que pour substituer les `${...}`). Le service `app` a donc `env_file: .env` — **ne
pas le retirer**, sinon le script `entrypoint` retombe sur `root`/mot de passe vide et boucle
sur « Attente de MySQL ».

Image basée sur **Alpine** (`php:8.4-fpm-alpine`) volontairement, pour l'empreinte disque.

## 6b. Production (déploiement depuis l'image)

- **Dev** = 2 conteneurs (nginx `web` + php-fpm `app`, code monté). **Prod** = **1 image unique FrankenPHP** (Caddy + PHP) qui sert HTTP + PHP + assets. Divergence assumée (prod plus simple à opérer).
- **`Dockerfile.prod`** (suivi) : multi-stage `assets` (node) → `vendor` (composer `--no-dev`) → `app` (`dunglas/frankenphp:php8.4-alpine`, `install-php-extensions`, `SERVER_NAME=:80`). Le **code + assets sont cuits** dans l'image ; **`storage/` en est exclu** (`.dockerignore`) et recréé au runtime par `entrypoint.prod.sh`.
- **`docker/php/entrypoint.prod.sh`** : attend MySQL → recrée l'arbo `storage` → `migrate --force` → `config/route/view:cache` → lance FrankenPHP.
- **`bootstrap/app.php`** : `trustProxies(at: '*')` (URLs https + cookies sécurisés derrière Traefik).
- **`docker-compose.prod.yml` + `.env.production`** : **gitignorés** (locaux). L'image est **buildée/poussée sur Docker Hub** (`IMAGE_APP`) et seulement **tirée** sur le VPS (`pull` + `up -d`, pas de build). Le service `app` porte les **labels Traefik** (Host `notetaboite.com`), rejoint le **réseau Traefik externe** (`TRAEFIK_NETWORK`), `storage_data` en volume, MySQL non exposé. Traefik/TLS sont gérés par un Traefik déjà déployé.
- ⚠️ **APP_KEY** : à générer (`php artisan key:generate --show`) et coller dans `.env.production` **avant** le 1er `up` (l'env vient de `env_file`, il n'y a pas de fichier `.env` dans l'image).

## 7. Commandes utiles

```bash
docker compose exec app php artisan tinker
docker compose exec app php artisan route:list --path=api
docker compose exec app php artisan migrate:fresh --seed   # reset complet + données
./vendor/bin/pint                                          # formatage (hors conteneur)

# Tests : le conteneur injecte APP_ENV=local & DB_CONNECTION=mysql via env_file, ce qui
# écrase phpunit.xml. Forcer l'env de test (sinon RefreshDatabase tourne sur la base MySQL !) :
docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_HOST=127.0.0.1 app php artisan test
docker compose logs -f app                                 # logs applicatifs

# Recalculer tous les scores manuellement (ex. après import) :
docker compose exec app php artisan tinker --execute='app(App\Services\ClassementService::class)->recalculerTout();'
```

## 8. État & pistes

**Fait** : front complet (classement, fiche, liste « à éviter » + toggle, responsive), auth session
+ SSO, contributions (modals + filtre AJAX), modération (contributions + entreprises + signalement
à seuil + droit de réponse), score bayésien **pondéré** (confiance + récence), sortie auto « à éviter »,
ajout/vérification d'entreprise, commande `admin:creer`, toasts SweetAlert, healthcheck anti-502.

**Reste / pistes** :
- **Policies** pour centraliser l'autorisation (remplacer les `abort_unless`/`can` inline).
- **Collecte de données** (§4b) : commandes `entreprises:importer` (CSV/JSON) et `entreprises:importer-google` (Places API).
- **Comptes « entreprise » self-service** : revendication + droit de réponse géré par l'entreprise (aujourd'hui MVP admin).
- **Notifications** aux contributeurs (avis publié/retiré), **pagination** de la modération, **édition** d'une entreprise par l'admin.
- **Score composite** : intégrer les signaux `missions`/`retours_entretiens` (aujourd'hui score = avis seuls).

## 9. Plan — Tableau de bord statistiques (admin)

**Objectif** : page admin (`can:moderer`) affichant l'usage de la plateforme — visites + actions
(inscriptions, avis, entretiens, missions, signalements, entreprises proposées/vérifiées, modération)
— avec des **KPI** sur une période choisie (7 / 30 / 90 j) et un **graphique d'évolution** (courbe par jour).

### 9.1 Modèle de données
Une seule table journal **`evenements`** (faible complexité, index sur `(type, created_at)`) :

| colonne | rôle |
|---|---|
| `type` | `TypeEvenement` (enum backé) : `visite`, `inscription`, `connexion`, `avis`, `entretien`, `mission`, `signalement`, `entreprise_proposee`, `entreprise_verifiee`, `moderation` |
| `user_id` | nullable (FK) — auteur si connecté |
| `sujet_type` / `sujet_id` | morph nullable — l'entité concernée (avis, entreprise…) |
| `url` | nullable — page visitée (pour `visite`) |
| `visiteur_hash` | nullable — `sha256(ip + session + APP_KEY)` : visiteur unique **sans stocker d'IP** (RGPD) |
| `created_at` | horodatage (séries temporelles) — **pas de `updated_at`** |

Enum `App\Enums\TypeEvenement` avec `libelle()` (convention §2b) ; modèle `App\Models\Evenement`
avec un helper statique `Evenement::log(TypeEvenement $type, ?Model $sujet = null, ?string $url = null)`.

### 9.2 Capture (où sont émis les événements)
- **Visites** : middleware `App\Http\Middleware\EnregistrerVisite` sur le groupe `web` — n'enregistre
  que les `GET` HTML non-AJAX (exclut assets, `/up`, `/moderation`, `/admin`). Calcule `visiteur_hash`.
- **Actions modèles** : **observers** (attribut `#[ObservedBy]`) sur `AvisEntreprise`, `RetourEntretien`,
  `Mission`, `Signalement`, `Entreprise` (event `created` → `entreprise_proposee` ou `entreprise_verifiee`
  selon `statut`).
- **Auth** : listeners sur `Illuminate\Auth\Events\Registered` (→ `inscription`) et `Login` (→ `connexion`).
- **Modération** : appels explicites à `Evenement::log()` dans `ModerationController` (publier/retirer/vérifier).

### 9.3 Agrégation, route & vue
- **Contrôleur** `App\Http\Controllers\Admin\StatistiqueController@index` :
  - KPI par type sur la période (`where('created_at', '>=', now()->subDays($jours))->count()`), + visiteurs uniques (`distinct visiteur_hash`) ;
  - **série temporelle** : `selectRaw('DATE(created_at) jour, type, COUNT(*) n')->groupBy('jour','type')`,
    puis **remplissage des jours manquants** côté PHP → tableau prêt pour le graphe.
- **Route** (nouveau groupe) : `Route::middleware(['auth','can:moderer'])->prefix('admin')->name('admin.')`
  → `GET /admin/statistiques` = `admin.stats.index` (param `?jours=30`).
- **Vue** `resources/views/admin/statistiques.blade.php` (`<x-layout>`) : cartes KPI (pattern Tailwind
  de `moderation/index`), sélecteur de période, `<canvas id="graphe-usage">`, données injectées en
  `<script type="application/json" id="stats-data">`.
- **Nav** : lien « Statistiques » dans `partials/nav-links.blade.php` sous `@can('moderer')`.

### 9.4 Graphique (Chart.js, self-hosted)
- `npm install chart.js` (CSP app OK, tout est buildé par Vite — pas de CDN).
- **Entrée Vite dédiée** `resources/js/stats.js` (déclarée dans `vite.config.js`) chargée **uniquement**
  sur la page stats via `@vite('resources/js/stats.js')` → garde le bundle principal léger.
- `stats.js` lit `#stats-data`, rend une **courbe multi-séries** (visites + actions/jour).

### 9.5 Étapes (ordre d'implémentation)
1. Migration `create_evenements_table` + enum `TypeEvenement` + modèle `Evenement` (+ `log()`).
2. Middleware `EnregistrerVisite` + enregistrement dans `bootstrap/app.php` (`$middleware->web(append: [...])`).
3. Observers (5 modèles) + listeners auth (register dans `AppServiceProvider`) + logs dans `ModerationController`.
4. `Admin\StatistiqueController` + route groupe `admin` + Gate déjà en place (`moderer`).
5. Vue `admin/statistiques.blade.php` + cartes KPI + sélecteur période + `<canvas>`.
6. `chart.js` + `resources/js/stats.js` + entrée `vite.config.js` + `@vite` dans la vue.
7. Lien nav « Statistiques ».
8. (option) Commande `stats:purger` (visites > N mois) + planif dans `routes/console.php` (scheduler déjà actif).
9. Tests feature : visite enregistrée par le middleware, action enregistrée par observer, page `admin.stats.index`
   protégée par `can:moderer`, forme de l'agrégation (jours remplis).

**Notes** : aucune IP en clair (hash seul) ; insertion 1 ligne/pageview suffisante à cette échelle
(passer en queue seulement si volumétrie forte) ; réutiliser le style cartes de `moderation/index`.
