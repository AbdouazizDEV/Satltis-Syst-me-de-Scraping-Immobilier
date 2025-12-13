# Configuration du fichier .env

## Pour le développement local (SQLite)

```env
APP_NAME="Satltis"
APP_ENV=local
APP_KEY=base64:VOTRE_CLE_GENEREE
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
# Pas besoin de DB_HOST, DB_DATABASE, etc. pour SQLite
```

## Pour la production avec Neon PostgreSQL

### Option 1 : Utiliser DB_URL (Recommandé - Plus simple)

```env
APP_NAME="Satltis"
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE
APP_DEBUG=false
APP_URL=https://satltis-syst-me-de-scraping-immobilier.onrender.com

# Configuration Neon PostgreSQL avec URL complète
DB_CONNECTION=pgsql
DB_URL=postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require
```

### Option 2 : Utiliser les variables individuelles

```env
APP_NAME="Satltis"
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_GENEREE
APP_DEBUG=false
APP_URL=https://satltis-syst-me-de-scraping-immobilier.onrender.com

# Configuration Neon PostgreSQL (variables individuelles)
DB_CONNECTION=pgsql
DB_HOST=ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=npg_zhybVqrFSt30
DB_SSLMODE=require
```

# Cache et Sessions
CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stderr
LOG_LEVEL=error
```

## Variables importantes à configurer

### Obligatoires
- `APP_KEY` : Généré avec `php artisan key:generate`
- `DB_CONNECTION` : `pgsql` pour Neon PostgreSQL
- `DB_HOST` : Hostname de Neon
- `DB_DATABASE` : Nom de la base de données
- `DB_USERNAME` : Nom d'utilisateur
- `DB_PASSWORD` : Mot de passe
- `DB_SSLMODE` : `require` pour Neon (SSL obligatoire)

### Optionnelles mais recommandées
- `APP_URL` : URL de votre application
- `APP_ENV` : `production` pour la prod, `local` pour le dev
- `APP_DEBUG` : `false` en production, `true` en développement

## Génération de la clé APP_KEY

```bash
php artisan key:generate
```

Cette commande génère automatiquement une clé et l'ajoute au fichier .env.

