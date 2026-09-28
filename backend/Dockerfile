# -------------------------------------------------------------
# Stage 1: Build PHP Vendor Dependencies
# -------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader \
    --no-scripts

# -------------------------------------------------------------
# Stage 2: Production Runtime with PHP 8.3 & Nginx
# -------------------------------------------------------------
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# Install system packages (PostgreSQL client/libs, Nginx, Supervisor, gettext for envsubst)
RUN apk add --no-cache \
    nginx \
    supervisor \
    gettext \
    curl \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    linux-headers

# Configure and compile PHP extensions for PostgreSQL, Excel, and Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        zip \
        bcmath \
        intl \
        opcache \
        pcntl

# Copy configuration files
COPY docker/php.ini $PHP_INI_DIR/conf.d/custom.ini
COPY docker/nginx.conf.template /etc/nginx/templates/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Copy application source code
COPY . /var/www/html

# Copy pre-built vendor dependencies from Stage 1
COPY --from=vendor /app/vendor /var/www/html/vendor

# Set permissions for web server
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Default port exposed by Render
EXPOSE 10000

# Run entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
