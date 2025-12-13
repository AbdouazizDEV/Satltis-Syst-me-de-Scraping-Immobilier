# 🚂 Guide de déploiement Railway

## Configuration

Le projet est configuré pour utiliser **Docker** sur Railway (comme Render).

## Étapes de déploiement

### 1. Connecter votre repo GitHub

1. Allez sur [railway.app](https://railway.app)
2. Connectez-vous avec votre compte GitHub
3. Cliquez sur "New Project"
4. Sélectionnez "Deploy from GitHub repo"
5. Choisissez le repo : `AbdouazizDEV/Satltis-Syst-me-de-Scraping-Immobilier`

### 2. Railway détectera automatiquement

Railway détectera automatiquement :
- Le `Dockerfile` (pour le build)
- Le `railway.json` (pour la configuration)

### 3. Configurer les variables d'environnement

Dans l'onglet **Variables** de votre service Railway, ajoutez :

#### Option 1 : Utiliser DB_URL (Recommandé)

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:VOTRE_CLE_GENEREE
LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_URL=postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require
```

#### Option 2 : Utiliser les variables individuelles

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:VOTRE_CLE_GENEREE
LOG_CHANNEL=stderr
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=npg_zhybVqrFSt30
DB_SSLMODE=require
```

### 4. Générer APP_KEY

Si vous n'avez pas encore de `APP_KEY`, Railway le générera automatiquement via le `docker-entrypoint.sh`.

Sinon, vous pouvez le générer manuellement :
```bash
php artisan key:generate
```

### 5. Déployer

1. Cliquez sur "Deploy" dans Railway
2. Railway construira l'image Docker
3. Les migrations s'exécuteront automatiquement au démarrage (via `docker-entrypoint.sh`)

## Configuration Railway

### Utiliser Docker (Recommandé - Déjà configuré)

Le `railway.json` est configuré pour utiliser Docker :
```json
{
  "build": {
    "builder": "DOCKERFILE",
    "dockerfilePath": "Dockerfile"
  }
}
```

### Alternative : Utiliser NIXPACKS

Si vous préférez NIXPACKS au lieu de Docker, modifiez `railway.json` :
```json
{
  "build": {
    "builder": "NIXPACKS",
    "buildCommand": "composer install --optimize-autoloader --no-dev"
  },
  "deploy": {
    "startCommand": "php artisan serve --host=0.0.0.0 --port=$PORT"
  }
}
```

**Note** : Avec NIXPACKS, vous devrez exécuter les migrations manuellement ou via un script de démarrage.

## Vérification après déploiement

### 1. Vérifier les logs

Dans l'onglet **Logs** de Railway, vous devriez voir :
```
=== Configuration PostgreSQL détectée via DB_URL ===
=== Exécution des migrations ===
✅ Migrations exécutées avec succès
```

### 2. Vérifier l'URL

Railway générera automatiquement une URL pour votre service (ex: `votre-app.up.railway.app`)

### 3. Tester l'application

Accédez à : `https://votre-app.up.railway.app/rentals`

## Problèmes courants

### Erreur : "There was an error deploying from source"

**Causes possibles** :
1. Railway n'a pas détecté le Dockerfile
2. Erreur lors du build Docker
3. Variables d'environnement manquantes

**Solutions** :
1. **Vérifier que Railway utilise Docker** :
   - Allez dans Settings → Build
   - Vérifiez que "Dockerfile" est sélectionné comme builder
   - Si ce n'est pas le cas, sélectionnez "Dockerfile" et redéployez

2. **Vérifier les logs de build** :
   - Allez dans l'onglet "Deployments"
   - Cliquez sur le dernier déploiement
   - Vérifiez les logs pour voir l'erreur exacte

3. **Forcer l'utilisation de Docker** :
   - Dans Settings → Build, sélectionnez "Dockerfile"
   - Le fichier `railway.json` devrait automatiquement utiliser Docker

4. **Vérifier que le Dockerfile est présent** :
   - Le fichier `Dockerfile` doit être à la racine du repo
   - Vérifiez dans GitHub que le fichier est bien présent

### Erreur : "Database connection failed"

**Solutions** :
1. Vérifiez que `DB_URL` ou les variables `DB_*` sont correctement définies
2. Vérifiez que Neon PostgreSQL est accessible depuis Railway
3. Vérifiez que `DB_SSLMODE=require` est défini

### Erreur : "Table sessions does not exist"

**Solutions** :
1. Les migrations devraient s'exécuter automatiquement via `docker-entrypoint.sh`
2. Si ce n'est pas le cas, exécutez manuellement dans le shell Railway :
   ```bash
   php artisan migrate --force
   ```

## Avantages de Railway

- ✅ Support Docker natif
- ✅ Déploiement automatique depuis GitHub
- ✅ Variables d'environnement faciles à gérer
- ✅ Logs en temps réel
- ✅ Base de données PostgreSQL intégrée (optionnelle)
- ✅ SSL automatique

## Comparaison avec Render

| Feature | Railway | Render |
|---------|---------|--------|
| Docker | ✅ | ✅ |
| Déploiement auto | ✅ | ✅ |
| Variables d'env | ✅ | ✅ |
| Base de données | ✅ (intégrée) | ✅ (externe) |
| SSL | ✅ (auto) | ✅ (auto) |
| Gratuit | ✅ (limité) | ✅ (limité) |

Les deux plateformes sont excellentes pour Laravel. Railway est peut-être plus simple à configurer initialement.

