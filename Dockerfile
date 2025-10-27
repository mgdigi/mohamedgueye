# Étape 1: Installer Composer et dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

# Copier uniquement les fichiers nécessaires pour composer
COPY composer.json composer.lock ./

# Installer les dépendances PHP (sans dev)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Étape 2: Image finale
FROM php:8.3-fpm-alpine

# Installer extensions et outils nécessaires
RUN apk add --no-cache \
    postgresql-dev \
    bash \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html

# Copier le code de l'application
COPY . .

# Copier les dépendances PHP depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor

# Créer les répertoires nécessaires
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Passer à l'utilisateur laravel
USER laravel

# Exposer le port dynamique de Render
EXPOSE 8000

# Commande d'entrée pour Render
CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT"]
