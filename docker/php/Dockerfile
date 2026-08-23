FROM php:8.4-apache

RUN docker-php-ext-install pdo pdo_mysql

# Set DocumentRoot ke folder public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf && \
    sed -i 's|<Directory /var/www/html>|<Directory /var/www/html/public>|g' \
        /etc/apache2/apache2.conf

WORKDIR /var/www/html
