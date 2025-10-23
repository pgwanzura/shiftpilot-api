# Use official PHP 8.2 FPM image
FROM php:8.2-fpm AS base

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    default-mysql-client \
    git \
    libfreetype6-dev \
    libjpeg-dev \
    libonig-dev \
    libpng-dev \
    libzip-dev \
    nginx \
    nodejs \
    npm \
    unzip \
    zip \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) calendar exif gd mbstring pcntl pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

COPY ./nginx.conf /etc/nginx/sites-available/default
RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chown -R www-data:www-data /var/www \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx \
    && chmod -R 755 /var/lib/nginx /var/log/nginx

# Configure PHP-FPM to listen on TCP 127.0.0.1:9000
RUN echo '[www]\n\
    listen = 127.0.0.1:9000\n\
    listen.owner = www-data\n\
    listen.group = www-data\n\
    pm = dynamic\n\
    pm.max_children = 5\n\
    pm.start_servers = 2\n\
    pm.min_spare_servers = 1\n\
    pm.max_spare_servers = 3\n' > /usr/local/etc/php-fpm.d/zz-docker.conf

EXPOSE 80 9000

COPY ./wait-for-it.sh /usr/local/bin/wait-for-it.sh
RUN chmod +x /usr/local/bin/wait-for-it.sh

COPY ./entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
