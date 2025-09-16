FROM php:7.4-apache

RUN apt-get update && apt-get install -y \
    cron \
    nano \
    git \
    unzip \
    && docker-php-ext-install pdo_mysql zip

# Installer Composer depuis l'image officielle
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Définir le dossier de travail
WORKDIR /var/www

ADD . /var/www
ADD ./public /var/www/html

# Installer les dépendances PHP via Composer
RUN composer install --no-dev --optimize-autoloader
#ADD ./conf /etc/apache2/sites-enabled
#ADD ./conf /etc/apache2/sites-available
RUN mkdir /etc/ssl/abitonda-certification
RUN chmod 700 /etc/ssl/abitonda-certification
#COPY abitonda-certification /etc/ssl/abitonda-certification

RUN a2enmod ssl
RUN a2enmod rewrite
#RUN a2ensite homework.abitonda.rw

RUN chmod -R 777 /var/www/storage/

# Exposer le port 8082 (au lieu du 80 par défaut)
EXPOSE 8082