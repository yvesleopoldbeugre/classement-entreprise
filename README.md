# Note ta boîte — Classement des entreprises (Côte d'Ivoire)

Plateforme communautaire qui **note les entreprises de Côte d'Ivoire** à partir des retours de
celles et ceux qui y travaillent (salariés, stagiaires, freelances, candidats), pour aider les
futurs candidats à choisir **en connaissance de cause**.

🌐 Production : **[notetaboite.com](https://notetaboite.com)**

---

## Fonctionnalités clés

- **Classement fiable** : score **bayésien** (façon IMDb) + **moyenne pondérée** des avis selon la
  **confiance du contributeur** (LinkedIn vérifié > email vérifié > non vérifié) et la **récence**.
- **Liste éditoriale** « à mieux connaître avant de s'y aventurer » + sortie automatique quand la note remonte.
- **Contributions** : avis, retours d'entretien, missions — modérés avant publication ; signalement à seuil ; droit de réponse.
- **Avis d'abord** : un visiteur peut rédiger son avis **sans compte**, le compte est créé à l'envoi.
- **Inscription sans friction** : email + mot de passe, ou **lien magique** (sans mot de passe) ; SSO (Google/LinkedIn/GitHub/Facebook).
- **Vérification d'email** (relève le poids des avis) + badges de confiance.
- **Espace admin** (`can:moderer`) : modération, **statistiques d'usage** (visites/actions, graphes),
  **utilisateurs**, et **visiteurs en direct + chat** (widget visiteur + **bot FAQ** + reprise humaine).
- **SEO** : sitemap, données structurées JSON-LD (`AggregateRating`…), OpenGraph, titres ciblés.

## Stack

Laravel 13 · PHP 8.4 · MySQL 8 · Tailwind v4 + Vite · Sanctum (API) · Socialite (SSO) ·
Scramble (doc OpenAPI) · **FrankenPHP** (image de production) · Traefik (reverse-proxy).

## Démarrage (dev, Docker)

```bash
cp .env.example .env            # puis renseigner les variables
docker compose up -d            # app (php-fpm) + web (nginx) + db (mysql)
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# Assets front (obligatoire pour le style) :
docker compose run --rm --no-deps node sh -c "npm install && npm run build"
# ou en dev avec HMR :  docker compose --profile dev up -d node
```

App : http://localhost:8088 · API : `/api` · Doc API : `/docs/api`

Créer un compte admin :

```bash
docker compose exec app php artisan admin:creer
```

## Tests

> ⚠️ Le conteneur injecte `DB_CONNECTION=mysql` via `env_file` (écrase `phpunit.xml`). Forcer l'env de test :

```bash
docker compose exec -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_HOST=127.0.0.1 app php artisan test
```

Style : `./vendor/bin/pint`

## Production (image unique FrankenPHP)

```bash
# Build & push (hors VPS)
docker build -f Dockerfile.prod --target app -t yvesleopold98/classement-entreprise:X.Y.Z .
docker push yvesleopold98/classement-entreprise:X.Y.Z

# Déploiement (sur le VPS, avec docker-compose.prod.yml + .env.production)
./deploy.sh X.Y.Z     # pull → up → migrate → seed → optimize
```

La configuration de prod (`docker-compose.prod.yml`, `.env.production`) est **gitignorée**
(secrets hors dépôt et hors image).

## Documentation

L'architecture complète, les conventions et le fonctionnement détaillé de chaque brique sont
documentés dans **[AGENT.md](AGENT.md)** (modèle de données, classement, auth, stats, chat, SEO,
déploiement, etc.).
