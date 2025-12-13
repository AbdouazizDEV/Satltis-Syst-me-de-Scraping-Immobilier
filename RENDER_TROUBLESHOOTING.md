# 🔧 Guide de dépannage Render

## Problème : L'application utilise SQLite au lieu de PostgreSQL

### Vérification 1 : Variables d'environnement dans Render

1. Allez sur votre service Render
2. Cliquez sur "Environment" dans le menu de gauche
3. Vérifiez que ces variables sont présentes :

**Option A : Utiliser DB_URL (Recommandé)**
```
DB_URL=postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require
DB_CONNECTION=pgsql
APP_ENV=production
```

**Option B : Utiliser les variables individuelles**
```
DB_CONNECTION=pgsql
DB_HOST=ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=npg_zhybVqrFSt30
DB_SSLMODE=require
APP_ENV=production
```

### Vérification 2 : Logs de déploiement

1. Allez dans l'onglet "Logs" de votre service Render
2. Cherchez ces messages lors du démarrage :

```
=== Vérification des variables d'environnement ===
DB_URL: [devrait être défini]
DB_HOST: [devrait être défini]
DB_CONNECTION: pgsql
APP_ENV: production
```

Si vous voyez :
```
⚠️ ATTENTION: Aucune configuration PostgreSQL détectée !
```

Cela signifie que les variables d'environnement ne sont **pas** injectées par Render.

### Solution : Ajouter les variables manuellement

1. Dans Render, allez dans "Environment"
2. Cliquez sur "Add Environment Variable"
3. Ajoutez **au minimum** :
   - `DB_URL` OU `DB_HOST` (les deux si vous voulez)
   - `DB_CONNECTION=pgsql`
   - `APP_ENV=production`

4. Cliquez sur "Save Changes"
5. Render redéploiera automatiquement

### Vérification 3 : Après le redéploiement

Dans les logs, vous devriez voir :
```
=== Configuration PostgreSQL détectée via DB_URL ===
DB_URL configuré dans .env
DB_CONNECTION (config): pgsql
```

Si vous voyez toujours `DB_CONNECTION (config): sqlite`, le cache contient encore l'ancienne configuration.

### Solution : Forcer le redéploiement

1. Dans Render, allez dans "Manual Deploy"
2. Cliquez sur "Clear build cache & deploy"
3. Cela supprimera tous les caches et redéploiera

### Vérification 4 : Test de connexion

Dans les logs, vous devriez voir :
```
=== Test de connexion PostgreSQL ===
```

Si la connexion échoue, vérifiez :
- Que les identifiants Neon PostgreSQL sont corrects
- Que l'IP de Render n'est pas bloquée par Neon
- Que `DB_SSLMODE=require` est défini

## Commandes utiles pour déboguer

### Vérifier les variables dans le conteneur

Si vous avez accès au shell du conteneur :
```bash
env | grep DB_
env | grep APP_ENV
cat .env | grep DB_
```

### Vérifier la configuration Laravel

```bash
php artisan tinker --execute="echo config('database.default');"
```

## Problèmes courants

### 1. Variables définies dans render.yaml mais pas injectées

**Solution** : Ajoutez-les manuellement dans l'interface Render (Environment)

### 2. Cache de configuration obsolète

**Solution** : Utilisez "Clear build cache & deploy" dans Render

### 3. APP_ENV=local en production

**Solution** : Définissez `APP_ENV=production` dans les variables d'environnement Render

### 4. DB_URL avec caractères spéciaux

**Solution** : Si `DB_URL` contient des caractères spéciaux, utilisez les variables individuelles à la place

## Support

Si le problème persiste après avoir suivi ces étapes :
1. Vérifiez les logs complets dans Render
2. Vérifiez que Neon PostgreSQL est accessible depuis Render
3. Vérifiez que les migrations ont été exécutées avec succès

