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

Réglages dans **`config/classement.php`** : `seuil_avis`, `min_avis_classement` (défaut 3,
seuil pour figurer au classement public via le scope `classable()`), `moyenne_defaut`.

**Recalcul** :
- `recalculerEntreprise($e)` — une entreprise, utilise le `C` courant.
- `recalculerTout()` — toutes les entreprises de façon cohérente. **À utiliser après un import/seed** ou périodiquement (car ajouter des avis fait bouger `C` pour tout le monde).

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

## 6. Docker — lancer le projet

```bash
docker compose up -d --build                       # build + démarre app, web, db
docker compose exec app php artisan migrate --seed # migrations + données de démo
# App / API → http://localhost:8088   (préfixe API : /api)
```

- Ports hôte configurables dans `.env` : `APP_PORT` (défaut **8088**), `DB_FORWARD_PORT` (**3307**), `VITE_PORT` (5173).
- MySQL accessible depuis l'hôte : `127.0.0.1:3307`, user `classement` / `secret`, base `classement_entreprise`.
- Vite/HMR (front) : `docker compose --profile dev up -d node`.
- Le point d'entrée `docker/php/entrypoint.sh` fait au démarrage : `composer install` (si besoin) → attente MySQL → `migrate`.

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
docker compose logs -f app                                 # logs applicatifs

# Recalculer tous les scores manuellement (ex. après import) :
docker compose exec app php artisan tinker --execute='app(App\Services\ClassementService::class)->recalculerTout();'
```

## 8. Pistes / TODO connus

- **Commande artisan `classement:recalculer`** planifiable (cron) pour garder `C` exact.
- **Modération** : back-office pour passer les contributions `en_attente` → `publie`.
- **Policies** pour remplacer les `abort_unless` inline et l'`authorize()` (actuellement `true` pour Entreprise).
- **Front / vues Blade** : page classement (top entreprises) + fiche entreprise (le back API est prêt).
- **Score composite** : intégrer éventuellement les signaux `missions` (paiement/contrat) et `retours_entretiens` (délais) au score, aujourd'hui purement basé sur les avis.
- **Conventions de style** : modèles en attributs PHP (`#[Fillable]`, `#[Hidden]`) — suivre l'existant, pas les propriétés `$fillable`.
