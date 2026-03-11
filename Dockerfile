FROM php:8.2-apache

# Install and enable the mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite (useful if you want clean URLs later)
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

RUN mkdir -p /var/www/uploads \
    && chown -R www-data:www-data /var/www/uploads \
    && chmod -R 755 /var/www/uploads