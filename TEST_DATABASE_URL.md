# Test de DATABASE_URL avec Laravel

## Configuration pour tester avec PostgreSQL (Neon)

### 1. Configurer le .env local pour tester avec PostgreSQL

```bash
# Dans votre .env local, ajoutez temporairement :
DATABASE_URL="postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
DB_CONNECTION=pgsql
```

**⚠️ Note** : Pour le développement local, SQLite est recommandé. Utilisez PostgreSQL seulement pour tester la connexion.

### 2. Tester la connexion

```bash
# Vérifier la connexion
php artisan db:show

# Exécuter les migrations
php artisan migrate

# Tester le scraping
php artisan app:scrape-rentals --source=ladresse --url=https://www.ladresse.com/
```

### 3. Vérifier les données

```bash
# Voir les données dans la base
php artisan tinker
>>> \App\Models\RentalSource::count()
>>> \App\Models\RentalSource::first()
```

## Configuration pour Render

Dans `render.yaml`, `DATABASE_URL` est déjà configuré. Render utilisera automatiquement cette variable.

## Variables d'environnement supportées

L'application supporte maintenant :
1. **DATABASE_URL** (format Prisma/standard) - **Prioritaire**
2. **DB_URL** (format Laravel) - Compatibilité
3. **Variables individuelles** (DB_HOST, DB_DATABASE, etc.) - Fallback

Laravel utilisera automatiquement `DATABASE_URL` si disponible, sinon `DB_URL`, sinon les variables individuelles.

