FROM php:7.2-apache

# Extensions PHP
RUN apt-get update && apt-get install -y \
    unzip git nano cron \
    && docker-php-ext-install pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le code
WORKDIR /var/www
ADD . /var/www
ADD ./public /var/www/html

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader

# Apache config
RUN a2enmod ssl
RUN a2enmod rewrite

RUN mkdir /etc/ssl/abitonda-certification && chmod 700 /etc/ssl/abitonda-certification
RUN chmod -R 777 /var/www/storage
