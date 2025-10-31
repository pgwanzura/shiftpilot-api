ARG CACHE_BREAKER=0
# Use official PHP 8.2 FPM image
FROM php:8.2-fpm AS base

WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    default-mysql-client \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    nginx \
    nodejs \
    npm \
    unzip \
    zip \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        mbstring \
        pcntl \
        pdo \
        pdo_mysql \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Remove default nginx site
RUN rm -rf /etc/nginx/sites-enabled/default

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create necessary directories for nginx and logs
RUN mkdir -p /var/lib/nginx /var/log/nginx /var/run/nginx /var/log/php \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx /var/run/nginx /var/log/php

# Copy PHP configuration
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY ./docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Copy nginx configuration
COPY ./docker/nginx.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Copy supervisor configuration
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Configure PHP-FPM
RUN echo '[www]\n\
listen = 127.0.0.1:9000\n\
listen.owner = www-data\n\
listen.group = www-data\n\
user = www-data\n\
group = www-data\n\
pm = dynamic\n\
pm.max_children = 5\n\
pm.start_servers = 2\n\
pm.min_spare_servers = 1\n\
pm.max_spare_servers = 3\n\
pm.max_requests = 500\n' > /usr/local/etc/php-fpm.d/zz-docker.conf

# Copy application files with proper ownership - THIS IS THE KEY LINE
COPY --chown=www-data:www-data . /var/www/html

# Set proper permissions BEFORE running composer
RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install composer dependencies as www-data user
USER www-data
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Switch back to root for final setup
USER root

# Copy and setup entrypoint scripts
COPY ./docker/wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

COPY ./docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]