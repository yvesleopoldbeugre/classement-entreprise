#!/bin/sh
set -e

cd /var/www

# Installe les dépendances PHP si le dossier vendor est absent (premier démarrage).
if [ ! -f vendor/autoload.php ]; then
    echo "==> Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Génère une APP_KEY si elle manque.
if ! grep -q '^APP_KEY=base64' .env 2>/dev/null; then
    echo "==> Génération de l'APP_KEY..."
    php artisan key:generate --force || true
fi

# Attente de la disponibilité de MySQL avant de migrer.
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
echo "==> MySQL est prêt."

# Permissions d'écriture pour Laravel.
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

# Migrations (non bloquant si déjà à jour).
php artisan migrate --force || true

exec "$@"
