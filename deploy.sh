#!/usr/bin/env bash
#
# Déploiement de Classement Entreprises CI (prod, image unique FrankenPHP).
# À lancer SUR LE VPS, dans le dossier contenant docker-compose.prod.yml + .env.production.
#
# Étapes : vérifs → pull → up -d → attente santé → migrate → seed (idempotent)
#          → optimize:clear + optimize → prune → statut.
#
# Usage :
#   ./deploy.sh            # déploie l'image définie dans .env.production
#   ./deploy.sh 1.1.0      # déploie le tag 1.1.0 (surcharge IMAGE_APP)
#
set -euo pipefail

COMPOSE_FILE="docker-compose.prod.yml"
ENV_FILE=".env.production"
CONTAINER_APP="classement_app_prod"

cd "$(dirname "$0")"

log()  { printf '\033[1;34m▶\033[0m %s\n' "$*"; }
ok()   { printf '\033[1;32m✔\033[0m %s\n' "$*"; }
die()  { printf '\033[1;31m✖\033[0m %s\n' "$*" >&2; exit 1; }

dc() { docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" "$@"; }

# --- Pré-requis ---
command -v docker >/dev/null           || die "docker introuvable."
docker compose version >/dev/null 2>&1 || die "'docker compose' (v2) requis."
[ -f "$COMPOSE_FILE" ]                  || die "$COMPOSE_FILE introuvable."
[ -f "$ENV_FILE" ]                      || die "$ENV_FILE introuvable (à créer à partir de .env.example)."

# APP_KEY obligatoire (pas de fichier .env dans l'image → doit venir de l'env_file).
grep -qE '^APP_KEY=base64:' "$ENV_FILE" \
    || die "APP_KEY vide/invalide dans $ENV_FILE — génère-la : php artisan key:generate --show"

# Tag optionnel en argument → surcharge IMAGE_APP (le tag après le dernier ':').
if [ "${1:-}" != "" ]; then
    base="$(grep -E '^IMAGE_APP=' "$ENV_FILE" | head -1 | cut -d= -f2- | sed 's/:[^:]*$//')"
    [ -n "$base" ] || die "IMAGE_APP absent de $ENV_FILE."
    export IMAGE_APP="${base}:${1}"
    log "Image ciblée : ${IMAGE_APP}"
fi

# Réseau Traefik externe présent ?
net="$(grep -E '^TRAEFIK_NETWORK=' "$ENV_FILE" | head -1 | cut -d= -f2-)"; net="${net:-traefik}"
docker network inspect "$net" >/dev/null 2>&1 \
    || die "Réseau Traefik « $net » introuvable (TRAEFIK_NETWORK). Vérifie ton Traefik."

# --- Déploiement ---
log "Récupération de l'image…"
dc pull

log "Démarrage / mise à jour des services…"
dc up -d --remove-orphans

# --- Attente de la santé de l'app (migrations + caches se font dans l'entrypoint) ---
log "Attente que l'app soit healthy…"
for i in $(seq 1 40); do
    h="$(docker inspect -f '{{.State.Health.Status}}' "$CONTAINER_APP" 2>/dev/null || echo starting)"
    case "$h" in
        healthy)   ok "app healthy"; break ;;
        unhealthy) dc logs --tail=50 app; die "app unhealthy — voir les logs ci-dessus." ;;
    esac
    [ "$i" -eq 40 ] && { dc logs --tail=50 app; die "app pas healthy après 120 s."; }
    sleep 3
done

# --- Post-déploiement : migrations, seed, caches ---
# (l'entrypoint le fait déjà au boot ; on le refait ici de façon explicite + seed)
log "Migrations…"
dc exec -T app php artisan migrate --force

# Seed idempotent : DatabaseSeeder n'appelle que EntrepriseReelleSeeder en prod
# (upsert des entreprises du référentiel, sans données de démo).
log "Seed du référentiel (idempotent)…"
dc exec -T app php artisan db:seed --force

log "Nettoyage puis reconstruction des caches…"
dc exec -T app php artisan optimize:clear
dc exec -T app php artisan optimize

# --- Nettoyage des images orphelines ---
docker image prune -f >/dev/null 2>&1 || true

echo
log "État des services :"
dc ps
ok "Déploiement terminé."
