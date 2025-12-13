# 🔧 Variables d'environnement Railway - Guide rapide

## ⚠️ IMPORTANT

**Vous DEVEZ ajouter ces variables dans Railway**, sinon l'application utilisera SQLite au lieu de PostgreSQL.

## 📋 Liste des variables à ajouter

### Méthode 1 : Copier-coller rapide

Allez dans Railway → Votre service → **Variables** → Cliquez sur "New Variable" pour chaque ligne :

```
APP_ENV=production
```

```
APP_DEBUG=false
```

```
DB_CONNECTION=pgsql
```

```
DB_URL=postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require
```

```
LOG_CHANNEL=stderr
```

```
LOG_LEVEL=error
```

### Méthode 2 : Variables individuelles (si DB_URL ne fonctionne pas)

```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=npg_zhybVqrFSt30
DB_SSLMODE=require
LOG_CHANNEL=stderr
LOG_LEVEL=error
```

## ✅ Vérification

Après avoir ajouté les variables et redéployé, vérifiez les logs Railway. Vous devriez voir :

```
=== Configuration PostgreSQL détectée via DB_URL ===
Informations extraites de DB_URL:
  DB_HOST: ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech
  DB_PORT: 5432
  DB_DATABASE: neondb
  DB_USERNAME: neondb_owner
  DB_SSLMODE: require
```

**Au lieu de** :
```
⚠️ ATTENTION: Aucune configuration PostgreSQL détectée !
DB_URL: non défini
```

## 🎯 Étapes détaillées

1. **Ouvrir Railway** : https://railway.app
2. **Sélectionner votre service** : "Satltis-Syst-me-de-Scraping-Immobilier"
3. **Aller dans l'onglet "Variables"** (ou "Environment")
4. **Cliquer sur "New Variable"**
5. **Ajouter chaque variable** :
   - Nom : `APP_ENV`
   - Valeur : `production`
   - Cliquer sur "Add"
6. **Répéter pour toutes les variables**
7. **Railway redéploiera automatiquement**

## 📝 Note sur APP_KEY

`APP_KEY` sera généré automatiquement par le script `docker-entrypoint.sh` si absent. Vous n'avez pas besoin de l'ajouter manuellement.

## 🔍 Si les variables ne sont pas prises en compte

1. Vérifiez que vous avez bien cliqué sur "Add" après chaque variable
2. Vérifiez que Railway a redéployé (onglet "Deployments")
3. Vérifiez les logs pour voir si les variables sont détectées
4. Essayez de redéployer manuellement : "Deploy" → "Redeploy"

