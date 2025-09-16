FROM php:7.4-apache

# Corriger les dépôts expirés
RUN sed -i 's/deb.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i 's/security.debian.org/archive.debian.org/g' /etc/apt/sources.list \
    && sed -i '/buster-updates/d' /etc/apt/sources.list

# Installer paquets
RUN apt-get update && apt-get install -y unzip git nano cron \
    && docker-php-ext-install pdo_mysql

WORKDIR /var/www
ADD . /var/www
ADD ./public /var/www/html

RUN composer install --no-dev --optimize-autoloader

RUN a2enmod ssl && a2enmod rewrite
RUN mkdir /etc/ssl/abitonda-certification && chmod 700 /etc/ssl/abitonda-certification
RUN chmod -R 777 /var/www/storage
