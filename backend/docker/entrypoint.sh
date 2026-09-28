#!/bin/sh
set -e

# Default port to 10000 if not provided by Render
export PORT=${PORT:-10000}

echo "==> Configuring Nginx to listen on port ${PORT}..."
envsubst '${PORT}' < /etc/nginx/templates/nginx.conf.template > /etc/nginx/http.d/default.conf

# Ensure correct permissions
echo "==> Setting permissions for storage and cache..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run database migrations if configured (default: true)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force || echo "Warning: Migrations failed or database not ready yet."
fi

# Cache configuration, routes, and views for production performance
echo "==> Caching Laravel configuration, routes, and views..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "==> Starting Supervisord (Nginx + PHP-FPM + Queue Worker)..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
