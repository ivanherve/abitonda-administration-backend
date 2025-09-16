FROM php:7.4-apache

# Installer extensions et outils utiles
RUN apt-get update && apt-get install -y unzip git nano cron \
    && docker-php-ext-install pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copier le code source
ADD . /var/www
ADD ./public /var/www/html

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Config Apache
RUN a2enmod ssl && a2enmod rewrite
RUN mkdir /etc/ssl/abitonda-certification && chmod 700 /etc/ssl/abitonda-certification
RUN chmod -R 777 /var/www/storage
