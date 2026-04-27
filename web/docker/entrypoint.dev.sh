#!/bin/bash
set -e

echo "==> Installation des dépendances Composer..."
composer install --no-interaction

echo "==> Génération de APP_KEY si manquante..."
if grep -q "^APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=" .env 2>/dev/null; then
    php artisan key:generate
fi

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
php artisan migrate --no-interaction

echo "==> Symlink storage..."
php artisan storage:link --quiet || true

echo "==> Démarrage du serveur Laravel sur 0.0.0.0:8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
