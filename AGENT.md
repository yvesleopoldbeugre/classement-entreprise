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

⚠️ **Piège** : le seeder (`DatabaseSeeder`) utilise `WithoutModelEvents` → l'observer **ne
se déclenche pas** pendant le seeding. `ClassementSeeder` appelle donc explicitement
`recalculerTout()` à la fin.

## 5. Couche HTTP

- **Requests** : `app/Http/Requests/{Entité}/Store*.php` + `Update*.php`. Règles de validation, enums via `Rule::enum`, unique (slug, 1 avis/user/entreprise). `RetourEntretien` normalise la date au 1er du mois dans `prepareForValidation()`.
- **Resources** : `app/Http/Resources/*Resource.php`. Enums sérialisés en `.value`, relations via `whenLoaded`. `EntrepriseResource` expose un bloc `classement`. `UserResource` masque `name`/`email` sauf pour le propriétaire.
- **Contrôleurs** : `app/Http/Controllers/Api/*Controller.php` (API resource controllers).
- **Routes** : `routes/api.php` — GET publics (classement/consultation) ; POST/PUT/DELETE sous `auth:sanctum`.

**Conventions de sécurité importantes** (respecter dans tout nouveau code) :
- `user_id` n'est **jamais** dans un Store request → toujours `= $request->user()->id` dans le contrôleur.
- `statut_moderation` n'est **jamais** soumis par l'utilisateur → forcé à `StatutModeration::EnAttente` à la création (modération a priori).
- Sur update/destroy des contributions : `abort_unless($model->user_id === $request->user()->id, 403)` (à remplacer par des **Policies** quand l'admin sera développé).

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
- **Deux classements distincts sur l'accueil** : (1) « à éviter » éditorial (ordonné, curé) ; (2) « communautaire » basé sur les vrais avis (`classable()`, ≥ 3 avis), vide au départ et qui se remplit avec les témoignages.
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
- **Ajout d'entreprise** : `EntrepriseController` (web), form `/proposer-entreprise` (auth). Un **utilisateur** propose → `statut = a_verifier` ; un **admin** (`can:moderer`) → `statut = verifiee` (le `statut` du formulaire est toujours écrasé). Le modérateur voit les entreprises `a_verifier` dans `/moderation` (section « Entreprises à vérifier ») → **Vérifier** (`PUT .../verifier`) ou **Supprimer** (`DELETE`). Badge « ⏳ En vérification » / « ✓ Vérifiée » sur la fiche. Couvert par `EntrepriseCreationTest`.
  - ⚠️ **Nom de route** : la route web de création s'appelle **`entreprises.proposer`** (pas `entreprises.store`) car ce dernier est déjà pris par l'`apiResource` (`routes/api.php`) — les noms de routes sont globaux, une collision fait résoudre `route()` vers la mauvaise route.
- **Droit de réponse** : colonnes `entreprises.reponse_entreprise` + `reponse_entreprise_le`. `ReponseEntrepriseController` (route `PUT /entreprises/{entreprise}/reponse`, `can:moderer`). Éditable par un admin sur la fiche ; affichée dans un bloc « Réponse de l'entreprise ». *(MVP : édition par admin faute de comptes « entreprise » ; évolution = comptes revendiqués/vérifiés.)*

⚠️ **`is_admin` n'est PAS dans `$fillable`** (anti-escalade de privilège). Pour promouvoir un
admin : `$u->is_admin = true; $u->save();` (pas `update([...])` qui l'ignore). Les factories
peuvent le passer via `->create(['is_admin' => true])`.

## 6. Docker — lancer le projet

```bash
docker compose up -d --build                       # build + démarre app, web, db
docker compose exec app php artisan migrate --seed # migrations + données de démo
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

## 8. Pistes / TODO connus

- **Modération** : back-office fait (§5d). Améliorations possibles : file par entreprise, historique des décisions, notifications aux contributeurs.
- **Policies** pour remplacer les `abort_unless` inline et l'`authorize()` (actuellement `true` pour Entreprise).
- **Front** : classement + fiche faits (§5c). Reste à faire : formulaires de contribution (avis/entretien/mission), auth (login/register), pages de modération.
- **Score composite** : intégrer éventuellement les signaux `missions` (paiement/contrat) et `retours_entretiens` (délais) au score, aujourd'hui purement basé sur les avis.
- **Conventions de style** : modèles en attributs PHP (`#[Fillable]`, `#[Hidden]`) — suivre l'existant, pas les propriétés `$fillable`.
