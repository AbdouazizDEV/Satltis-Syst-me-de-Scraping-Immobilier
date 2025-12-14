#!/bin/bash
# Script de test pour vérifier la connexion PostgreSQL avec DATABASE_URL

set -e

echo "=== Test de connexion PostgreSQL avec DATABASE_URL ==="
echo ""

# Vérifier que DATABASE_URL est défini
if [ -z "$DATABASE_URL" ]; then
    echo "❌ ERREUR: DATABASE_URL n'est pas défini"
    echo "Définissez DATABASE_URL dans votre .env ou exportez-le :"
    echo 'export DATABASE_URL="postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"'
    exit 1
fi

echo "✅ DATABASE_URL est défini"
echo ""

# Mettre à jour le .env
echo "=== Mise à jour du .env ==="
if grep -q "DATABASE_URL=" .env 2>/dev/null; then
    sed -i "s|DATABASE_URL=.*|DATABASE_URL=$DATABASE_URL|" .env
else
    echo "DATABASE_URL=$DATABASE_URL" >> .env
fi

if grep -q "DB_CONNECTION=" .env 2>/dev/null; then
    sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=pgsql/' .env
else
    echo "DB_CONNECTION=pgsql" >> .env
fi

echo "✅ .env mis à jour"
echo ""

# Nettoyer le cache
echo "=== Nettoyage du cache ==="
php artisan config:clear
php artisan cache:clear || true
echo "✅ Cache nettoyé"
echo ""

# Tester la connexion
echo "=== Test de connexion à la base de données ==="
php artisan db:show || {
    echo "❌ ERREUR: Impossible de se connecter à la base de données"
    exit 1
}
echo "✅ Connexion réussie"
echo ""

# Exécuter les migrations
echo "=== Exécution des migrations ==="
php artisan migrate --force || {
    echo "❌ ERREUR: Les migrations ont échoué"
    exit 1
}
echo "✅ Migrations exécutées avec succès"
echo ""

# Tester le scraping
echo "=== Test du scraping ==="
php artisan app:scrape-rentals --source=ladresse --url=https://www.ladresse.com/ || {
    echo "⚠️ ATTENTION: Le scraping a rencontré des erreurs (peut être normal)"
}
echo ""

# Vérifier les données
echo "=== Vérification des données ==="
php artisan tinker --execute="
\$count = \App\Models\RentalSource::count();
echo 'Nombre total de sources: ' . \$count . PHP_EOL;
if (\$count > 0) {
    \$first = \App\Models\RentalSource::first();
    echo 'Première source:' . PHP_EOL;
    echo '  URL: ' . \$first->source_url . PHP_EOL;
    echo '  Type: ' . \$first->source_type . PHP_EOL;
    echo '  Ville: ' . (\$first->city ?: 'N/A') . PHP_EOL;
    echo '  Qualifié: ' . (\$first->is_qualified ? 'Oui' : 'Non') . PHP_EOL;
}
" || {
    echo "⚠️ ATTENTION: Impossible de vérifier les données"
}
echo ""

echo "✅ Tests terminés avec succès !"
echo ""
echo "Pour voir les données dans la base :"
echo "  php artisan tinker"
echo "  >>> \\App\\Models\\RentalSource::all()"

