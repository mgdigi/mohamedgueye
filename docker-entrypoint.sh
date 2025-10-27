#!/bin/sh

# Nettoyer le cache avant de démarrer
echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

# Recacher la configuration après migration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache

echo "Starting Laravel application..."
exec "$@"