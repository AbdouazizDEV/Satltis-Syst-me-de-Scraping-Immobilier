# Guide Docker

## 🐳 Développement Local

### Prérequis
- Docker
- Docker Compose

### Démarrer l'application

```bash
# Construire et démarrer
docker-compose up -d

# Voir les logs
docker-compose logs -f app

# Arrêter
docker-compose down
```

L'application sera accessible sur `http://localhost:8000`

### Commandes utiles

```bash
# Exécuter une commande Artisan dans le conteneur
docker-compose exec app php artisan migrate

# Accéder au shell du conteneur
docker-compose exec app bash

# Reconstruire l'image
docker-compose build --no-cache

# Voir les logs en temps réel
docker-compose logs -f
```

## 🚀 Déploiement sur Render

### Configuration

Le fichier `render.yaml` est configuré pour utiliser Docker.

### Étapes de déploiement

1. **Connecter votre repo GitHub** à Render
2. **Créer un nouveau Web Service**
   - Type : Web Service
   - Connecter votre repo
   - Render détectera automatiquement le `Dockerfile`
3. **Créer une base de données PostgreSQL**
   - Dans Render Dashboard
   - Créer une nouvelle base de données PostgreSQL
4. **Configurer les variables d'environnement**
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://votre-app.onrender.com
   DB_CONNECTION=pgsql
   DB_HOST=<host de Render>
   DB_DATABASE=<database de Render>
   DB_USERNAME=<username de Render>
   DB_PASSWORD=<password de Render>
   ```
5. **Déployer**
   - Render construira automatiquement l'image Docker
   - Les migrations s'exécuteront automatiquement au démarrage

### Variables d'environnement Render

Les variables peuvent être configurées dans le dashboard Render ou dans `render.yaml` :

```yaml
envVars:
  - key: APP_ENV
    value: production
  - key: DB_CONNECTION
    value: postgresql
```

## 🔧 Personnalisation

### Modifier le port

Par défaut, Apache écoute sur le port 80. Pour changer :

1. Modifier `Dockerfile` :
   ```dockerfile
   EXPOSE 8080
   ```

2. Modifier `docker-compose.yml` :
   ```yaml
   ports:
     - "8000:8080"
   ```

### Ajouter des extensions PHP

Modifier le `Dockerfile` :

```dockerfile
RUN docker-php-ext-install pdo_mysql pdo mbstring exif pcntl bcmath gd redis
```

### Utiliser MySQL au lieu de SQLite

1. Décommenter la section MySQL dans `docker-compose.yml`
2. Modifier `.env` :
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_DATABASE=satltis
   DB_USERNAME=root
   DB_PASSWORD=root
   ```

## 🐛 Troubleshooting

### Erreur de permissions

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Reconstruire complètement

```bash
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Voir les logs détaillés

```bash
docker-compose logs -f app
```

### Tester l'image localement

```bash
docker build -t satltis-app .
docker run -p 8000:80 -e APP_ENV=local satltis-app
```

