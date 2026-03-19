FROM php:8.2-apache

# Install and enable the mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/hide-php-version.ini

# 2. Hide Apache version (Hardens the standard 'Server' header)
RUN echo "ServerTokens Prod\nServerSignature Off" >> /etc/apache2/apache2.conf


RUN { \
  echo 'session.cookie_httponly = 1'; \
  echo 'session.cookie_secure = 1'; \
  echo 'session.cookie_samesite = "Strict"'; \
} > /usr/local/etc/php/conf.d/session-security.ini

RUN { \
  echo 'display_errors = Off'; \
  echo 'display_startup_errors = Off'; \
  echo 'error_reporting = E_ALL'; \
  echo 'log_errors = On'; \
} > /usr/local/etc/php/conf.d/error-logging.ini

# Enable Apache mod_rewrite (useful if you want clean URLs later)
RUN a2enmod rewrite

# Copy the custom SSL host configuration
COPY config/apache/vhost-ssl.conf /etc/apache2/sites-available/vhost-ssl.conf

# Enable SSL module and the new SSL site
RUN a2enmod ssl && \
    a2enmod socache_shmcb && \
    a2ensite vhost-ssl.conf

# Set the working directory
WORKDIR /var/www/html

# This copies everything into the image (except what is blocked by .dockerignore)
# and gives the Apache user ownership so your app runs flawlessly.
COPY --chown=www-data:www-data . /var/www/html/

RUN mv /var/www/html/config/nginx/ssl/self-signed.crt /etc/ssl/certs/ \
    && mv /var/www/html/config/nginx/ssl/self-signed.key /etc/ssl/private/

RUN rm -rf /var/www/html/config/apache \
    && rm -rf /var/www/html/config/nginx

# # SCRUB SENSITIVE INFRASTRUCTURE FROM PUBLIC WEB ROOT
# RUN rm -rf /var/www/html/config/apache \
#     && rm -rf /var/www/html/config/nginx

RUN mkdir -p /var/www/uploads \
    && chown -R www-data:www-data /var/www/uploads \
    && chmod -R 755 /var/www/uploads