# 🚀 Étapes de déploiement sur Render

## ✅ Configuration terminée

L'application est maintenant configurée pour utiliser `DATABASE_URL` (format Prisma/standard) avec PostgreSQL Neon.

## 📋 Étapes de déploiement

### 1. Tester localement (optionnel)

```bash
# Tester avec PostgreSQL
export DATABASE_URL="postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
./test-postgresql.sh
```

### 2. Déployer sur Render

#### Option A : Via render.yaml (Recommandé)

1. Allez sur [Render Dashboard](https://dashboard.render.com)
2. Cliquez sur **"New"** → **"Blueprint"**
3. Connectez votre dépôt GitHub
4. Render détectera automatiquement `render.yaml`
5. Les variables d'environnement seront configurées automatiquement

#### Option B : Via l'interface Render

1. Allez sur [Render Dashboard](https://dashboard.render.com)
2. Cliquez sur **"New"** → **"Web Service"**
3. Connectez votre dépôt GitHub
4. Configurez :
   - **Name** : `satltis-scraping`
   - **Environment** : `Docker`
   - **Dockerfile Path** : `./Dockerfile`
   - **Docker Context** : `.`
5. Ajoutez les variables d'environnement :
   ```
   APP_ENV=production
   APP_DEBUG=false
   LOG_CHANNEL=stderr
   LOG_LEVEL=error
   DB_CONNECTION=pgsql
   DATABASE_URL=postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require
   ```

### 3. Vérifier le déploiement

1. Attendez la fin du build (5-10 minutes)
2. Vérifiez les logs pour :
   ```
   === Configuration PostgreSQL détectée via DATABASE_URL (format Prisma/standard) ===
   === Exécution des migrations ===
   ✅ Migrations exécutées avec succès
   === Démarrage d'Apache ===
   ```
3. Testez l'URL : `https://votre-service.onrender.com/rentals`

## 🔍 Dépannage

### Erreur : "Application failed to respond"

1. Vérifiez les logs dans Render
2. Vérifiez que `DATABASE_URL` est bien défini
3. Vérifiez que les migrations ont réussi

### Erreur : "relation sessions does not exist"

Les migrations n'ont pas créé la table `sessions`. Le script `docker-entrypoint.sh` devrait la créer automatiquement, mais si l'erreur persiste :

1. Allez dans les logs Render
2. Cherchez les messages de migration
3. Si nécessaire, redéployez

## 📝 Variables d'environnement dans render.yaml

Le fichier `render.yaml` contient déjà toutes les variables nécessaires :
- `DATABASE_URL` (format Prisma/standard)
- `DB_URL` (compatibilité Laravel)
- Variables individuelles (fallback)

## ✅ Checklist avant déploiement

- [x] Configuration `DATABASE_URL` ajoutée
- [x] `config/database.php` mis à jour
- [x] `docker-entrypoint.sh` mis à jour
- [x] `render.yaml` mis à jour
- [ ] Tests locaux réussis (optionnel)
- [ ] Déploiement sur Render
- [ ] Vérification des logs
- [ ] Test de l'URL publique

