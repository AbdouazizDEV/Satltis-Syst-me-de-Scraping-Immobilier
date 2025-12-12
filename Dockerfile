# Utiliser l'image PHP officielle avec Apache
FROM php:8.3-apache

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql pdo mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Activer le module rewrite d'Apache
RUN a2enmod rewrite

# Copier la configuration Apache personnalisée
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copier les fichiers de l'application
COPY . /var/www/html

# Installer les dépendances PHP (sans dev pour production)
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Exposer le port (Render utilisera le port défini dans $PORT)
EXPOSE 80

# Configurer Apache pour écouter sur le port 80
RUN echo "Listen 80" > /etc/apache2/ports.conf

# Script de démarrage
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

