#!/bin/bash
set -e

# Créer le fichier .env si il n'existe pas
if [ ! -f .env ]; then
    cp .env.example .env
fi

# S'assurer que les permissions sont correctes au démarrage
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true
touch /var/www/html/storage/logs/laravel.log 2>/dev/null || true
chown www-data:www-data /var/www/html/storage/logs/laravel.log 2>/dev/null || true
chmod 664 /var/www/html/storage/logs/laravel.log 2>/dev/null || true

# Supprimer complètement TOUS les fichiers de cache AVANT toute opération
rm -rf /var/www/html/bootstrap/cache/*.php 2>/dev/null || true
rm -rf /var/www/html/storage/framework/cache/data/* 2>/dev/null || true
rm -rf /var/www/html/storage/framework/views/*.php 2>/dev/null || true

# Nettoyer le cache AVANT toute configuration (important!)
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Générer la clé d'application si elle n'existe pas
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    php artisan key:generate --force || true
fi

# Forcer PostgreSQL si DB_HOST est défini (production Render/Neon)
# IMPORTANT: Configurer PostgreSQL AVANT de créer le cache
# Vérifier DB_URL d'abord (prioritaire), puis DB_HOST
echo "=== Vérification des variables d'environnement ==="
echo "DB_URL: ${DB_URL:-non défini}"
echo "DB_HOST: ${DB_HOST:-non défini}"
echo "DB_CONNECTION: ${DB_CONNECTION:-non défini}"
echo "APP_ENV: ${APP_ENV:-non défini}"

# Vérifier DB_URL d'abord (prioritaire), puis DB_HOST
if [ -n "$DB_URL" ] || [ -n "$DB_HOST" ]; then
    if [ -n "$DB_URL" ]; then
        echo "=== Configuration PostgreSQL détectée via DB_URL ==="
        export DB_CONNECTION=pgsql
        export DB_URL="$DB_URL"
        
        # Forcer PostgreSQL dans .env (CRITIQUE: avant de créer le cache)
        if grep -q "DB_CONNECTION=" .env 2>/dev/null; then
            sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
        else
            echo "DB_CONNECTION=pgsql" >> .env
        fi
        
        # Ajouter DB_URL dans .env
        if grep -q "DB_URL=" .env 2>/dev/null; then
            sed -i "s|DB_URL=.*|DB_URL=$DB_URL|" .env
        else
            echo "DB_URL=$DB_URL" >> .env
        fi
        
        echo "DB_URL configuré dans .env"
    else
        echo "=== Configuration PostgreSQL détectée (DB_HOST=$DB_HOST) ==="
        
        # Exporter les variables d'environnement pour qu'elles soient disponibles pour PHP
        export DB_CONNECTION=pgsql
        export DB_HOST="$DB_HOST"
        [ -n "$DB_DATABASE" ] && export DB_DATABASE="$DB_DATABASE"
        [ -n "$DB_USERNAME" ] && export DB_USERNAME="$DB_USERNAME"
        [ -n "$DB_PASSWORD" ] && export DB_PASSWORD="$DB_PASSWORD"
        [ -n "$DB_PORT" ] && export DB_PORT="$DB_PORT"
        [ -n "$DB_SSLMODE" ] && export DB_SSLMODE="$DB_SSLMODE"
        
        # Forcer PostgreSQL dans .env (CRITIQUE: avant de créer le cache)
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
    fi
    
    # Forcer APP_ENV=production si pas défini
    if ! grep -q "APP_ENV=" .env 2>/dev/null; then
        echo "APP_ENV=production" >> .env
    else
        sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
    fi
    
    echo "Configuration PostgreSQL appliquée dans .env"
    echo "DB_CONNECTION=pgsql"
    echo "DB_HOST=$DB_HOST"
    echo "DB_DATABASE=$DB_DATABASE"
    echo "APP_ENV=production"
    
    # Vérifier que .env est correctement configuré
    echo "=== Vérification du fichier .env ==="
    grep -E "^(DB_CONNECTION|DB_HOST|APP_ENV)=" .env || echo "ERREUR: Variables non trouvées dans .env"
else
    echo "⚠️ ATTENTION: Aucune configuration PostgreSQL détectée !"
    echo "DB_URL et DB_HOST sont tous les deux vides"
    echo "L'application utilisera SQLite par défaut (peut causer des erreurs en production)"
    echo "Vérifiez que les variables d'environnement sont bien définies dans Render"
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

# S'assurer que les permissions sont correctes AVANT les migrations
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Exécuter les migrations (seulement si pas déjà fait)
echo "=== Exécution des migrations ==="
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo "✅ Migrations exécutées avec succès"
else
    echo "❌ ERREUR lors de l'exécution des migrations"
    echo "Vérification de la connexion à la base de données..."
    php artisan db:show 2>&1 || echo "Impossible de se connecter à la base de données"
    exit 1
fi

# Vérifier que la table sessions existe
echo "=== Vérification de la table sessions ==="
php artisan tinker --execute="echo Schema::hasTable('sessions') ? '✅ Table sessions existe' : '❌ Table sessions manquante';" 2>/dev/null || echo "Note: Impossible de vérifier via tinker"

# Optimiser l'application pour la production
if [ "$APP_ENV" = "production" ] || [ -n "$DB_HOST" ]; then
    # Nettoyer à nouveau le cache APRÈS configuration PostgreSQL
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
    
    # Supprimer à nouveau TOUS les fichiers de cache
    rm -rf /var/www/html/bootstrap/cache/*.php 2>/dev/null || true
    
    # Attendre un peu pour s'assurer que .env est bien écrit
    sleep 1
    
    # Vérifier que .env contient bien PostgreSQL
    if grep -q "DB_CONNECTION=pgsql" .env; then
        echo "✅ .env contient bien DB_CONNECTION=pgsql"
    else
        echo "❌ ERREUR: .env ne contient pas DB_CONNECTION=pgsql"
        cat .env | grep DB_CONNECTION || echo "DB_CONNECTION non trouvé dans .env"
    fi
    
    # Recréer le cache avec les nouvelles valeurs
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
    
    # Vérifier quelle base de données est utilisée APRÈS création du cache
    echo "=== Vérification de la configuration de la base de données ==="
    echo "Variables d'environnement disponibles :"
    env | grep -E "^(DB_|APP_ENV)" || echo "Aucune variable DB_ ou APP_ENV trouvée"
    
    echo "Contenu du .env :"
    grep -E "^(DB_CONNECTION|DB_URL|DB_HOST|APP_ENV)=" .env 2>/dev/null || echo "Variables non trouvées dans .env"
    
    php artisan tinker --execute="echo 'DB_URL (env): ' . (env('DB_URL') ? 'défini' : 'non défini') . PHP_EOL; echo 'DB_HOST (env): ' . (env('DB_HOST') ? 'défini' : 'non défini') . PHP_EOL; echo 'DB_CONNECTION (env): ' . env('DB_CONNECTION', 'non défini') . PHP_EOL; echo 'DB_CONNECTION (config): ' . config('database.default') . PHP_EOL; echo 'APP_ENV: ' . env('APP_ENV', 'non défini') . PHP_EOL;" 2>/dev/null || echo "Note: Impossible de vérifier la config via tinker"
    
    # Test de connexion PostgreSQL si disponible
    if [ -n "$DB_URL" ] || [ -n "$DB_HOST" ]; then
        echo "=== Test de connexion PostgreSQL ==="
        php artisan db:show 2>&1 | head -10 || echo "Note: db:show peut échouer, mais la connexion sera testée lors des migrations"
    fi
fi

# Démarrer Apache
exec apache2-foreground

