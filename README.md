# Satltis - Système de Scraping Immobilier

Système backend Laravel pour scanner le web et extraire des données immobilières depuis différents sites d'annonces.

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

## 🚢 Déploiement

Le projet est prêt pour déploiement sur :
- **Railway** : Utilise le `Procfile`
- **Render** : Utilise le `render.yaml`
- **Heroku** : Utilise le `Procfile`

Voir `DEPLOYMENT.md` pour les détails.

## 📄 Licence

Développé pour AlphaDev/LocaMax
