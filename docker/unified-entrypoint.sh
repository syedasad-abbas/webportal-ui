#!/bin/sh
# Unified entrypoint script

echo "Starting unified container..."

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure storage directories exist
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

# A development Vite marker must never override the compiled asset manifest.
rm -f /var/www/html/public/hot

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php /var/www/html/artisan migrate --force
fi

# Cache Laravel configuration, routes, events, and views for production.
php /var/www/html/artisan optimize

# Set up cron jobs
echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan app:campaign-stats-update >> /proc/1/fd/1 2>> /proc/1/fd/2" | crontab -

# Start supervisord
echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
