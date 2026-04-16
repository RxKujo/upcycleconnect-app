# ============================================================
# Stage 1 — Node : compile les assets Vite/Vue/Tailwind
# ============================================================
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --frozen-lockfile

COPY . .
RUN npm run build

# ============================================================
# Stage 2 — Composer : installe les dépendances PHP sans dev
# ============================================================
FROM composer:2.7 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction

# ============================================================
# Stage 3 — PHP-FPM : runtime de l'application Laravel
# ============================================================
FROM php:8.2-fpm-alpine AS php-stage

WORKDIR /var/www/html

# Extensions PHP nécessaires à Laravel + dompdf + barcode
RUN apk add --no-cache \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        gd \
        opcache \
        xml \
        dom \
        bcmath

# Config OPcache optimisée pour la prod
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

# Copier les dépendances depuis les stages précédents
COPY --from=composer-builder /app/vendor ./vendor
COPY --from=node-builder /app/public/build ./public/build

# Copier le code source
COPY . .

# Permissions sur les dossiers Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]

# ============================================================
# Stage 4 — Nginx : sert les fichiers statiques + proxy FPM
# ============================================================
FROM nginx:1.27-alpine AS nginx-stage

# Config Nginx pour Laravel
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

# Fichiers statiques depuis les stages précédents
COPY --from=node-builder /app/public /var/www/html/public
COPY --from=php-stage /var/www/html/public /var/www/html/public

EXPOSE 80
