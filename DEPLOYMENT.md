# Guide de Déploiement

Ce document explique comment déployer le projet Satltis sur différentes plateformes.

## ✅ Vérification de Conformité

### Exigences respectées :

- ✅ **Route et vue Blade simple** : Route `/rentals` avec vue `rentals.index`
- ✅ **Tableau HTML** : Tableau responsive avec toutes les colonnes requises
- ✅ **Filtre par Ville** : Filtre dropdown en haut du tableau (ligne 22-33 de `resources/views/rentals/index.blade.php`)
- ✅ **Laravel 12.x** : Compatible avec Laravel 10.x/11.x (même architecture)
- ✅ **Architecture propre** : Code PSR-12, utilisation stricte d'Eloquent
- ✅ **Déploiement** : Fichiers de configuration créés pour Railway, Render, Heroku

## 🚀 Déploiement sur Railway

1. **Créer un compte** sur [Railway](https://railway.app)
2. **Créer un nouveau projet** depuis GitHub
3. **Ajouter une base de données** PostgreSQL ou MySQL
4. **Configurer les variables d'environnement** :
   ```
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:... (généré automatiquement)
   DB_CONNECTION=pgsql (ou mysql)
   DB_HOST=...
   DB_DATABASE=...
   DB_USERNAME=...
   DB_PASSWORD=...
   ```
5. **Déployer** : Railway détectera automatiquement Laravel et utilisera le `Procfile`

## 🚀 Déploiement sur Render

1. **Créer un compte** sur [Render](https://render.com)
2. **Créer un nouveau "Web Service"** depuis GitHub
3. **Configurer** :
   - **Build Command** : `composer install --optimize-autoloader --no-dev && php artisan optimize && php artisan migrate --force`
   - **Start Command** : `php artisan serve --host=0.0.0.0 --port=$PORT`
4. **Ajouter une base de données** PostgreSQL
5. **Configurer les variables d'environnement** (voir Railway)
6. Le fichier `render.yaml` est déjà configuré

## 🚀 Déploiement sur Heroku

1. **Créer un compte** sur [Heroku](https://heroku.com)
2. **Installer Heroku CLI**
3. **Créer l'application** :
   ```bash
   heroku create votre-app-name
   ```
4. **Ajouter une base de données** :
   ```bash
   heroku addons:create heroku-postgresql:hobby-dev
   ```
5. **Configurer les variables** :
   ```bash
   heroku config:set APP_ENV=production
   heroku config:set APP_DEBUG=false
   ```
6. **Déployer** :
   ```bash
   git push heroku main
   ```

## 📋 Variables d'environnement requises

```env
APP_NAME="Satltis"
APP_ENV=production
APP_KEY=base64:... (généré avec php artisan key:generate)
APP_DEBUG=false
APP_URL=https://votre-domaine.com

DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
LOG_CHANNEL=stderr
LOG_LEVEL=error
```

## 🔍 Vérification Post-Déploiement

1. Accéder à `https://votre-domaine.com/rentals`
2. Vérifier que le tableau s'affiche
3. Tester le filtre par ville
4. Vérifier la pagination

## 📝 Notes importantes

- Le `Procfile` est configuré pour Railway/Heroku
- Le fichier `render.yaml` est pour Render
- Les migrations s'exécutent automatiquement lors du build
- Assurez-vous que la base de données est créée avant le déploiement

