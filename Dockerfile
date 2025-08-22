# Dockerfile
FROM php:7.2-apache

# Installer dépendances + extensions PHP
RUN apt-get update && apt-get install -y \
    cron \
    nano \
  && rm -rf /var/lib/apt/lists/* \
  && docker-php-ext-install pdo_mysql

# Copier projet
COPY . /var/www
COPY ./public /var/www/html

# Droits
RUN chown -R www-data:www-data /var/www

# SSL
RUN mkdir /etc/ssl/abitonda-certification && chmod 700 /etc/ssl/abitonda-certification
# COPY abitonda-certification /etc/ssl/abitonda-certification

# Apache modules
RUN a2enmod ssl && a2enmod rewrite
# RUN a2ensite homework.abitonda.rw
