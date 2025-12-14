# Installation de l'extension PostgreSQL pour PHP (local)

## Ubuntu/Debian

```bash
sudo apt-get update
sudo apt-get install -y php8.3-pgsql
# Ou pour toutes les versions PHP
sudo apt-get install -y php-pgsql
```

## Vérifier l'installation

```bash
php -m | grep pgsql
php -m | grep pdo_pgsql
```

Vous devriez voir `pgsql` et `pdo_pgsql` dans la liste.

## Redémarrer le serveur PHP (si nécessaire)

```bash
# Si vous utilisez php-fpm
sudo systemctl restart php8.3-fpm

# Si vous utilisez Apache
sudo systemctl restart apache2
```

## Après l'installation

Relancez le script de test :

```bash
export DATABASE_URL="postgresql://neondb_owner:npg_zhybVqrFSt30@ep-patient-cell-ahrb8zvy-pooler.c-3.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require"
./test-postgresql.sh
```

