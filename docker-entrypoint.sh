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
# Ne pas utiliser cache:clear car la table cache n'existe peut-être pas encore
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
# Ne pas exécuter cache:clear ici - la table cache sera créée par les migrations

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
        
        # Extraire les informations de DB_URL et les mettre dans les variables individuelles
        # Format: postgresql://username:password@host:port/database?sslmode=require
        DB_URL_CLEAN=$(echo "$DB_URL" | sed 's|postgresql://||' | sed 's|postgres://||')
        DB_USER_PASS=$(echo "$DB_URL_CLEAN" | cut -d'@' -f1)
        DB_HOST_PORT_DB=$(echo "$DB_URL_CLEAN" | cut -d'@' -f2)
        
        DB_USERNAME=$(echo "$DB_USER_PASS" | cut -d':' -f1)
        DB_PASSWORD=$(echo "$DB_USER_PASS" | cut -d':' -f2)
        DB_HOST_PORT=$(echo "$DB_HOST_PORT_DB" | cut -d'/' -f1)
        DB_DATABASE=$(echo "$DB_HOST_PORT_DB" | cut -d'/' -f2 | cut -d'?' -f1)
        DB_PORT=$(echo "$DB_HOST_PORT" | cut -d':' -f2)
        DB_HOST=$(echo "$DB_HOST_PORT" | cut -d':' -f1)
        
        # Extraire sslmode si présent
        if echo "$DB_URL" | grep -q "sslmode="; then
            DB_SSLMODE=$(echo "$DB_URL" | sed 's/.*sslmode=\([^&]*\).*/\1/')
        else
            DB_SSLMODE="require"
        fi
        
        # Si le port n'est pas dans l'URL, utiliser le port par défaut
        if [ "$DB_PORT" = "$DB_HOST" ]; then
            DB_PORT="5432"
        fi
        
        echo "Informations extraites de DB_URL:"
        echo "  DB_HOST: $DB_HOST"
        echo "  DB_PORT: $DB_PORT"
        echo "  DB_DATABASE: $DB_DATABASE"
        echo "  DB_USERNAME: $DB_USERNAME"
        echo "  DB_SSLMODE: $DB_SSLMODE"
        
        # Exporter les variables extraites
        export DB_HOST="$DB_HOST"
        export DB_PORT="$DB_PORT"
        export DB_DATABASE="$DB_DATABASE"
        export DB_USERNAME="$DB_USERNAME"
        export DB_PASSWORD="$DB_PASSWORD"
        export DB_SSLMODE="$DB_SSLMODE"
        
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
        
        # Ajouter les variables individuelles dans .env (pour que Laravel les utilise)
        [ -n "$DB_HOST" ] && (grep -q "DB_HOST=" .env && sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|" .env || echo "DB_HOST=$DB_HOST" >> .env)
        [ -n "$DB_DATABASE" ] && (grep -q "DB_DATABASE=" .env && sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env || echo "DB_DATABASE=$DB_DATABASE" >> .env)
        [ -n "$DB_USERNAME" ] && (grep -q "DB_USERNAME=" .env && sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env || echo "DB_USERNAME=$DB_USERNAME" >> .env)
        [ -n "$DB_PASSWORD" ] && (grep -q "DB_PASSWORD=" .env && sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env || echo "DB_PASSWORD=$DB_PASSWORD" >> .env)
        [ -n "$DB_PORT" ] && (grep -q "DB_PORT=" .env && sed -i "s|DB_PORT=.*|DB_PORT=$DB_PORT|" .env || echo "DB_PORT=$DB_PORT" >> .env)
        [ -n "$DB_SSLMODE" ] && (grep -q "DB_SSLMODE=" .env && sed -i "s|DB_SSLMODE=.*|DB_SSLMODE=$DB_SSLMODE|" .env || echo "DB_SSLMODE=$DB_SSLMODE" >> .env)
        
        echo "DB_URL et variables individuelles configurées dans .env"
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
# Vérifier d'abord si la base de données est accessible
echo "Vérification de la connexion à la base de données..."
php artisan db:show 2>&1 | head -10 || echo "Note: db:show peut échouer, mais on continue"

# Vérifier l'état des migrations avant d'exécuter
echo "État actuel des migrations:"
php artisan migrate:status 2>&1 | head -20 || echo "Note: migrate:status peut échouer si la table migrations n'existe pas"

# Exécuter les migrations avec gestion d'erreur améliorée
echo "Exécution des migrations..."
php artisan migrate --force --no-interaction 2>&1
MIGRATION_EXIT_CODE=$?

if [ $MIGRATION_EXIT_CODE -eq 0 ]; then
    echo "✅ Migrations exécutées avec succès"
else
    echo "⚠️ ERREUR lors de l'exécution des migrations (code: $MIGRATION_EXIT_CODE)"
    echo "Cela peut être normal si certaines tables existent déjà"
    
    # Vérifier l'état après l'erreur
    echo "État des migrations après l'erreur:"
    php artisan migrate:status 2>&1 | head -20 || true
    
    # Ne pas exit 1 - laisser l'application démarrer même si certaines migrations ont échoué
    # Les tables peuvent déjà exister ou être partiellement créées
    echo "Continuation malgré l'erreur de migration..."
fi

# Vérifier que les tables essentielles existent
echo "=== Vérification des tables essentielles ==="
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
\$tables = ['users', 'sessions', 'cache', 'migrations'];
foreach (\$tables as \$table) {
    if (Schema::hasTable(\$table)) {
        echo \"✅ Table \$table existe\";
    } else {
        echo \"❌ Table \$table manquante\";
    }
}
" 2>/dev/null || echo "Note: Impossible de vérifier via tinker"

# Si la table sessions n'existe pas, la créer manuellement
echo "=== Vérification/création de la table sessions ==="
php artisan tinker --execute="
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
if (!Schema::hasTable('sessions')) {
    echo 'Création de la table sessions...';
    try {
        Schema::create('sessions', function (Blueprint \$table) {
            \$table->string('id')->primary();
            \$table->foreignId('user_id')->nullable()->index();
            \$table->string('ip_address', 45)->nullable();
            \$table->text('user_agent')->nullable();
            \$table->longText('payload');
            \$table->integer('last_activity')->index();
        });
        echo '✅ Table sessions créée';
    } catch (Exception \$e) {
        echo '❌ Erreur lors de la création: ' . \$e->getMessage();
    }
} else {
    echo '✅ Table sessions existe déjà';
}
" 2>/dev/null || echo "Note: Impossible de créer via tinker"

# Optimiser l'application pour la production
if [ "$APP_ENV" = "production" ] || [ -n "$DB_HOST" ]; then
    # Nettoyer à nouveau le cache APRÈS configuration PostgreSQL et migrations
    php artisan config:clear || true
    php artisan route:clear || true
    php artisan view:clear || true
    # Nettoyer le cache seulement APRÈS que les migrations aient créé la table cache
    php artisan cache:clear || echo "Note: cache:clear peut échouer si la table cache n'existe pas encore"
    
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

# Configurer Apache pour utiliser le port Railway ($PORT) ou 80 par défaut
RAILWAY_PORT=${PORT:-80}
echo "=== Configuration du port Apache ==="
echo "Port Railway (PORT): ${PORT:-non défini}"
echo "Port Apache: $RAILWAY_PORT"

# Modifier la configuration Apache pour utiliser le port Railway
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    echo "Configuration Apache pour le port $PORT (Railway)"
    # Modifier ports.conf
    echo "Listen $PORT" > /etc/apache2/ports.conf
    # Modifier la configuration du VirtualHost
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf || true
    echo "Apache configuré pour écouter sur le port $PORT"
else
    echo "Apache configuré pour écouter sur le port 80 (défaut)"
fi

# Démarrer Apache
echo "=== Démarrage d'Apache ==="
exec apache2-foreground

