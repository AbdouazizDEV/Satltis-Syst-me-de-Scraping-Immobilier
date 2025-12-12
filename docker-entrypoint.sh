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

# Forcer PostgreSQL si DB_CONNECTION n'est pas défini (production Render)
if ! grep -q "DB_CONNECTION=" .env 2>/dev/null || [ -z "$DB_CONNECTION" ]; then
    if [ -n "$DB_HOST" ]; then
        # Si DB_HOST est défini (via Render), utiliser PostgreSQL
        sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env || echo "DB_CONNECTION=pgsql" >> .env
    fi
fi

# Créer le fichier SQLite seulement si explicitement demandé (développement local)
if [ "$DB_CONNECTION" = "sqlite" ] && [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
    chmod 664 database/database.sqlite
    chown www-data:www-data database/database.sqlite
fi

# S'assurer que le fichier SQLite existe et a les bonnes permissions (si utilisé)
if [ "$DB_CONNECTION" = "sqlite" ] && [ -f database/database.sqlite ]; then
    chmod 664 database/database.sqlite
    chown www-data:www-data database/database.sqlite
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

