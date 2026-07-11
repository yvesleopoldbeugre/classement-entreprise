#!/bin/sh
set -e

cd /app

# Purge de tout cache compilé résiduel (ex. packages.php généré en dev avec des
# providers dev comme Pail) avant de (re)générer les caches de prod.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
      bootstrap/cache/config.php bootstrap/cache/routes-*.php bootstrap/cache/events.php

# Attente de MySQL.
echo "==> Attente de MySQL (${DB_HOST:-db}:${DB_PORT:-3306})..."
until php -r '
    $h = getenv("DB_HOST") ?: "db";
    $p = getenv("DB_PORT") ?: "3306";
    $u = getenv("DB_USERNAME") ?: "root";
    $pw = getenv("DB_PASSWORD") ?: "";
    try { new PDO("mysql:host=$h;port=$p", $u, $pw); exit(0); }
    catch (Throwable $e) { exit(1); }
' 2>/dev/null; do
    sleep 2
done
echo "==> MySQL prêt."

# storage/ est monté en volume (donnée d'instance) : on garantit l'arborescence
# et les permissions à chaque démarrage (le volume peut être vide au 1er lancement).
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public
chown -R www-data:www-data storage

# Découverte des packages (composer a été lancé avec --no-scripts) + migrations.
php artisan package:discover --ansi || true
php artisan migrate --force || true

# Caches de production.
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
