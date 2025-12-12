#!/bin/bash
set -e

# Créer le fichier .env si il n'existe pas
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Nettoyer le cache AVANT toute configuration (important!)
php artisan config:clear || true
php artisan cache:clear || true

# Générer la clé d'application si elle n'existe pas
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force || true
fi

# Forcer PostgreSQL si DB_HOST est défini (production Render/Neon)
if [ -n "$DB_HOST" ]; then
    echo "Configuration PostgreSQL détectée (DB_HOST=$DB_HOST)"
    
    # Forcer PostgreSQL dans .env
    if grep -q "DB_CONNECTION=" .env 2>/dev/null; then
        sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
    else
        echo "DB_CONNECTION=pgsql" >> .env
    fi
    
    # S'assurer que les variables PostgreSQL sont définies dans .env
    [ -n "$DB_HOST" ] && (grep -q "DB_HOST=" .env && sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|" .env || echo "DB_HOST=$DB_HOST" >> .env)
    [ -n "$DB_DATABASE" ] && (grep -q "DB_DATABASE=" .env && sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env || echo "DB_DATABASE=$DB_DATABASE" >> .env)
    [ -n "$DB_USERNAME" ] && (grep -q "DB_USERNAME=" .env && sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env || echo "DB_USERNAME=$DB_USERNAME" >> .env)
    [ -n "$DB_PASSWORD" ] && (grep -q "DB_PASSWORD=" .env && sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env || echo "DB_PASSWORD=$DB_PASSWORD" >> .env)
    [ -n "$DB_PORT" ] && (grep -q "DB_PORT=" .env && sed -i "s|DB_PORT=.*|DB_PORT=$DB_PORT|" .env || echo "DB_PORT=$DB_PORT" >> .env)
    [ -n "$DB_SSLMODE" ] && (grep -q "DB_SSLMODE=" .env && sed -i "s|DB_SSLMODE=.*|DB_SSLMODE=$DB_SSLMODE|" .env || echo "DB_SSLMODE=$DB_SSLMODE" >> .env)
    
    echo "Configuration PostgreSQL appliquée dans .env"
else
    echo "Aucune configuration PostgreSQL détectée, utilisation de SQLite par défaut"
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
    # Recréer le cache avec les nouvelles valeurs
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Démarrer Apache
exec apache2-foreground

