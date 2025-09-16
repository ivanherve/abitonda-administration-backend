FROM php:8.2-apache

# Installer dépendances système + PHP extensions
RUN apt-get update && apt-get install -y \
    cron \
    nano \
    git \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Copier composer depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www

# Copier projet dans le conteneur
ADD . /var/www
ADD ./public /var/www/html

# Activer Apache modules
RUN a2enmod ssl
RUN a2enmod rewrite

# Créer dossier SSL
RUN mkdir /etc/ssl/abitonda-certification && chmod 700 /etc/ssl/abitonda-certification

# Installer dépendances PHP (sans dev, optimisé)
RUN composer install --no-dev --optimize-autoloader

# Donner droits sur les dossiers nécessaires
RUN chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Modifier Apache pour écouter sur 8082
RUN sed -i 's/Listen 80/Listen 8082/' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:8082>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8082

CMD ["apache2-foreground"]
