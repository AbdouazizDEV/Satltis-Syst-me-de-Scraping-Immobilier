#!/bin/bash
# Script de test avec Docker (qui a déjà PostgreSQL installé)

set -e

echo "=== Test avec Docker (PostgreSQL déjà installé) ==="
echo ""

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé"
    echo "Installez Docker ou utilisez l'option 1 pour installer PostgreSQL localement"
    exit 1
fi

echo "✅ Docker est installé"
echo ""

# Vérifier que docker-compose est disponible
if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "❌ docker-compose n'est pas disponible"
    exit 1
fi

echo "✅ docker-compose est disponible"
echo ""

# Arrêter les conteneurs existants
echo "=== Arrêt des conteneurs existants ==="
docker-compose down 2>/dev/null || true
echo ""

# Démarrer les conteneurs
echo "=== Démarrage des conteneurs Docker ==="
echo "Cela peut prendre quelques minutes la première fois..."
docker-compose up -d
echo ""

# Attendre que le conteneur soit prêt
echo "=== Attente que le conteneur soit prêt ==="
sleep 10

# Vérifier que le conteneur est en cours d'exécution
if ! docker-compose ps | grep -q "Up"; then
    echo "❌ Le conteneur n'est pas démarré"
    echo "Vérifiez les logs : docker-compose logs"
    exit 1
fi

echo "✅ Conteneur démarré"
echo ""

# Exécuter les migrations dans le conteneur
echo "=== Exécution des migrations ==="
docker-compose exec -T web php artisan migrate --force || {
    echo "⚠️ Les migrations ont peut-être déjà été exécutées"
}
echo ""

# Tester le scraping dans le conteneur
echo "=== Test du scraping ==="
docker-compose exec -T web php artisan app:scrape-rentals --source=ladresse --url=https://www.ladresse.com/ || {
    echo "⚠️ Le scraping a rencontré des erreurs (peut être normal)"
}
echo ""

# Vérifier les données
echo "=== Vérification des données ==="
docker-compose exec -T web php artisan tinker --execute="
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
    echo "⚠️ Impossible de vérifier les données"
}
echo ""

echo "✅ Tests terminés avec Docker !"
echo ""
echo "Pour voir les logs :"
echo "  docker-compose logs -f"
echo ""
echo "Pour accéder au conteneur :"
echo "  docker-compose exec web bash"
echo ""
echo "Pour arrêter les conteneurs :"
echo "  docker-compose down"

