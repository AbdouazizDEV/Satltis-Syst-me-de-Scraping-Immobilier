# 🧪 Guide de test local avec DATABASE_URL

## ⚠️ Problème : Extension PostgreSQL non installée

Pour tester localement avec PostgreSQL, vous devez installer l'extension PHP PostgreSQL.

## 📋 Étapes pour installer l'extension PostgreSQL

### 1. Installer l'extension

```bash
sudo apt-get update
sudo apt-get install -y php8.3-pgsql
```

### 2. Vérifier l'installation

```bash
php -m | grep pgsql
php -m | grep pdo_pgsql
```

Vous devriez voir `pgsql` et `pdo_pgsql` dans la liste.

### 3. Tester la connexion

```bash
export DATABASE_URL="postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
./test-postgresql.sh
```

## 🐳 Alternative : Utiliser Docker (si configuré)

Si vous préférez utiliser Docker :

1. Ajoutez votre utilisateur au groupe docker :
   ```bash
   sudo usermod -aG docker $USER
   newgrp docker
   ```

2. Puis exécutez :
   ```bash
   ./test-docker.sh
   ```

## ✅ Après l'installation

Une fois l'extension installée, vous pourrez :

1. **Tester la connexion** :
   ```bash
   export DATABASE_URL="postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
   php artisan db:show
   ```

2. **Exécuter les migrations** :
   ```bash
   php artisan migrate
   ```

3. **Tester le scraping** :
   ```bash
   php artisan app:scrape-rentals --source=ladresse --url=https://www.ladresse.com/
   ```

4. **Vérifier les données** :
   ```bash
   php artisan tinker
   >>> \App\Models\RentalSource::count()
   >>> \App\Models\RentalSource::first()
   ```

## 🚀 Ou déployer directement sur Render

Si vous préférez ne pas installer l'extension localement, vous pouvez déployer directement sur Render qui a déjà PostgreSQL configuré. Voir `DEPLOYMENT_STEPS.md`.

