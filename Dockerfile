# FROM php:8.2-apache
# COPY . /var/www/html/
# RUN docker-php-ext-install pdo pdo_mysql
# EXPOSE 80

FROM php:8.2-apache
RUN a2dismod mpm_event mpm_worker && a2enmod mpm_prefork
COPY . /var/www/html/
RUN docker-php-ext-install pdo pdo_mysql
EXPOSE 80