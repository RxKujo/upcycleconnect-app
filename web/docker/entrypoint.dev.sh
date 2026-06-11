#!/bin/bash
set -e

echo "==> Préparation des dossiers (montés sur volume rapide)..."
# vendor, storage/framework et bootstrap/cache sont des volumes Docker (FS conteneur
# rapide) et non le bind-mount : on s'assure que la structure attendue existe.
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

echo "==> Installation des dépendances Composer..."
# On n'exécute composer install que si les dépendances sont absentes ou ont changé,
# pour accélérer les redémarrages de conteneur.
if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
    composer install --no-interaction
else
    echo "    vendor/ à jour, composer install ignoré."
fi

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
# Plusieurs workers : "php artisan serve" est sinon mono-thread, ce qui sérialise
# toutes les requêtes (un appel API bloquant fige alors toute la page).
# --no-reload est requis pour qu'artisan serve respecte PHP_CLI_SERVER_WORKERS.
# (OPcache en revalidate_freq=0 prend les modifs de code en compte sans reload.)
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-8}"
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload
