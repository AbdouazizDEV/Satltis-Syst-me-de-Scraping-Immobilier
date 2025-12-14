# Satltis - Système de Scraping Immobilier

Système backend Laravel pour scanner le web et extraire des données immobilières depuis différents sites d'annonces.

## 📚 Choix de la Librairie de Scraping

Le projet utilise **Symfony DomCrawler** combiné avec **Laravel HTTP Client** pour le scraping web.

### Pourquoi ce choix ?

1. **Symfony DomCrawler** :
   - ✅ Intégré nativement dans Laravel (pas de dépendance supplémentaire)
   - ✅ API simple et intuitive pour parser le HTML
   - ✅ Support des sélecteurs CSS et XPath
   - ✅ Gestion robuste des erreurs et du HTML malformé
   - ✅ Performant et léger

2. **Laravel HTTP Client** :
   - ✅ Intégré dans Laravel (basé sur Guzzle)
   - ✅ Gestion automatique des timeouts et retry
   - ✅ Support natif des headers personnalisés (User-Agent, etc.)
   - ✅ Facile à tester et à mocker

3. **Alternative considérée : Goutte**
   - ❌ Incompatible avec Laravel 11/12
   - ❌ Dépendances supplémentaires non nécessaires

Cette combinaison offre un excellent équilibre entre simplicité, performance et maintenabilité, tout en respectant les standards Laravel.

## 🚀 Installation Rapide

### Prérequis
- PHP >= 8.2
- Composer
- SQLite (par défaut) ou MySQL/PostgreSQL

### Installation

```bash
# 1. Cloner le projet
git clone <url-du-repo>
cd Satltis_Agences_Immobilières

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 4. Créer la base de données (SQLite)
touch database/database.sqlite

# 5. Exécuter les migrations
php artisan migrate

# 6. Démarrer le serveur
php artisan serve
```

Le projet sera accessible sur `http://localhost:8000`

## 📝 Utilisation

### 1. Lancer un scraping

```bash
# Scraper le site l'Adresse
php artisan app:scrape-rentals --source=ladresse --url=https://www.ladresse.com/
```

### 2. Accéder à l'interface web

Ouvrez votre navigateur : **http://localhost:8000/rentals**

Vous verrez :
- Un filtre par ville en haut du tableau
- Un tableau HTML avec toutes les annonces scrapées
- Pagination (15 résultats par page)

### 3. Filtrer par ville

1. Sélectionnez une ville dans le dropdown
2. Cliquez sur "Filtrer"
3. Le tableau affiche uniquement les annonces de cette ville

## 🌐 API Endpoints

### Démarrer un scraping
```bash
POST /api/scraping/start
Body: {"source": "ladresse", "url": "https://www.ladresse.com/"}
```

### Liste des sources
```bash
GET /api/rentals
```

### Statistiques
```bash
GET /api/rentals/stats/summary
```

## 🏗️ Structure du Projet

```
app/
├── Console/Commands/ScrapeRentals.php      # Commande de scraping
├── Http/Controllers/
│   ├── RentalSourceController.php         # Controller web
│   └── Api/                                # Controllers API
├── Models/RentalSource.php                 # Modèle Eloquent
├── Services/
│   ├── Scraper/                            # Services de scraping
│   ├── DataExtractor/                      # Extraction de données
│   └── RentalSource/                       # Repository pattern
```

## 🔧 Commandes Utiles

```bash
# Voir les routes
php artisan route:list

# Nettoyer les caches
php artisan optimize:clear

# Réinitialiser la base de données (ATTENTION : supprime tout)
php artisan migrate:fresh

# Voir les logs
tail -f storage/logs/laravel.log
```

## 🐛 Problèmes Courants

### Erreur "Vite manifest not found"
✅ **Résolu** : La vue utilise Tailwind CSS via CDN, pas besoin de compiler les assets.

### Aucune donnée dans le tableau
➡️ Lancez d'abord un scraping : `php artisan app:scrape-rentals --source=ladresse --url=https://www.ladresse.com/`

### Erreur de base de données
➡️ Vérifiez que `database/database.sqlite` existe et que les migrations sont exécutées.

### Erreur "could not find driver" (PostgreSQL)
➡️ En développement local, utilisez SQLite (par défaut). Si vous voulez utiliser PostgreSQL :
```bash
# Ubuntu/Debian
sudo apt-get install php-pgsql

# Vérifier l'extension
php -m | grep pgsql
```

## 📊 Base de Données

Table principale : `rental_sources`

Champs :
- `source_url` (unique)
- `source_type` (AGENCY/PRIVATE)
- `name_or_title`
- `phone_number`
- `email`
- `property_type`
- `city`
- `district`
- `is_qualified` (calculé automatiquement)

## 🐳 Docker

### Développement local avec Docker

```bash
# Construire et démarrer les conteneurs
docker-compose up -d

# Voir les logs
docker-compose logs -f

# Arrêter les conteneurs
docker-compose down

# Reconstruire l'image
docker-compose build --no-cache
```

L'application sera accessible sur `http://localhost:8000`

### Tester l'image Docker

```bash
# Construire l'image
docker build -t satltis-app .

# Lancer le conteneur
docker run -p 8000:80 satltis-app
```

## 🚢 Déploiement

Le projet est prêt pour déploiement sur :
- **Render** : ✅ Recommandé - Utilise Docker (voir `render.yaml`)
- **Railway** : ✅ Supporté - Utilise le `railway.json` ou Docker
- **Heroku** : ✅ Supporté - Utilise le `Procfile`

### ⚠️ Plateformes NON supportées

- **Netlify** : ❌ Ne supporte pas PHP/Laravel (conçu uniquement pour les sites statiques)
  - Voir `NETLIFY_NOTE.md` pour plus de détails

### Déploiement sur Render avec Docker (Recommandé)

1. Connectez votre repo GitHub à Render
2. Créez un nouveau "Web Service"
3. Sélectionnez votre repo
4. Render détectera automatiquement le `Dockerfile`
5. Les variables d'environnement sont déjà définies dans `render.yaml`
6. La base de données PostgreSQL (Neon) est déjà configurée

**Note** : Le projet utilise Neon PostgreSQL. Les variables d'environnement sont définies dans `render.yaml`.

## 📄 Licence

Développé pour AlphaDev/LocaMax
