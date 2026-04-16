#!/bin/sh
set -e

echo "==> Attente de la base de données..."
until php -r "
    try {
        new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}',
            '${DB_USERNAME}',
            '${DB_PASSWORD}'
        );
        echo 'ok';
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null | grep -q "ok"; do
    echo "    MySQL non disponible, retry dans 2s..."
    sleep 2
done
echo "==> Base de données prête."

echo "==> Migrations..."
php artisan migrate --force

echo "==> Symlink storage..."
php artisan storage:link --quiet || true

echo "==> Cache config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Démarrage PHP-FPM..."
exec "$@"
