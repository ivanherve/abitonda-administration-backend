FROM php:8.2-apache

RUN apt-get update && apt-get install -y cron && apt-get install nano
RUN docker-php-ext-install pdo_mysql

ADD . /var/www
ADD ./public /var/www/html
#ADD ./conf /etc/apache2/sites-enabled
#ADD ./conf /etc/apache2/sites-available
RUN mkdir /etc/ssl/abitonda-certification
RUN chmod 700 /etc/ssl/abitonda-certification
#COPY abitonda-certification /etc/ssl/abitonda-certification

RUN a2enmod ssl
RUN a2enmod rewrite
#RUN a2ensite homework.abitonda.rw

RUN chmod -R 777 /var/www/storage/
# Désactiver affichage des Deprecated warnings PHP
RUN echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_NOTICE" > /usr/local/etc/php/conf.d/error_reporting.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/error_reporting.ini

# Exposer le port 8082 (au lieu du 80 par défaut)
EXPOSE 8082