FROM php:8.2-apache

# Enable PDO MySQL extension
RUN docker-php-ext-install pdo_mysql

# Copy all project files
COPY . /var/www/html

# Point Apache document root at /public
RUN sed -i 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf

# Enable mod_rewrite for Slim's pretty URLs
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

EXPOSE 80
