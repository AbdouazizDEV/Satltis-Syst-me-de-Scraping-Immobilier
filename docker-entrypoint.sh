#!/bin/bash
set -e

# Créer le fichier .env si il n'existe pas
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Générer la clé d'application si elle n'existe pas
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force || true
fi

# Créer le fichier SQLite si nécessaire
if [ "$DB_CONNECTION" = "sqlite" ] && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chmod 664 database/database.sqlite
fi

# Exécuter les migrations (seulement si pas déjà fait)
php artisan migrate --force || true

# Optimiser l'application pour la production
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Démarrer Apache
exec apache2-foreground

