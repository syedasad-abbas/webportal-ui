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

# Generate app key if not set
if [ -f /var/www/html/.env ]; then
    if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
        echo "Generating application key..."
        php /var/www/html/artisan key:generate --force
    fi
fi

# Set up cron jobs
echo "* * * * * cd /var/www/html && /usr/local/bin/php artisan campaign-stats-update 2>&1" | crontab -

# Start supervisord
echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
