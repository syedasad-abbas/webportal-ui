FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Enable Apache modules
RUN a2enmod rewrite

# Install system dependencies for Laravel & Composer
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    zip \
    libzip-dev \
    libpq-dev \
     cron \
    && docker-php-ext-install zip pdo pdo_mysql  pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js (for building Vite assets inside the container)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install --global npm@10 \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from the Laravel image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer



# Create a new Laravel project (this generates composer.json)
#RUN composer create-project laravel/laravel .

# Copy Laravel application into container
COPY laravel/ /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 storage bootstrap/cache

# Install Composer dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Generate application key
RUN php artisan key:generate

RUN (crontab -l ; echo '* * * * * cd /var/www/html && /usr/local/bin/php artisan app:campaign-stats-update 2>&1') | crontab

# Copy entrypoint script that builds assets if needed
COPY docker/laravel-entrypoint.sh /usr/local/bin/laravel-entrypoint.sh
RUN chmod +x /usr/local/bin/laravel-entrypoint.sh


# Expose Apache port
EXPOSE 80



# Start cron and Apache
#ENTRYPOINT ["docker-entrypoint.sh"]
#CMD ["apache2-foreground"]
CMD service cron start && apache2-foreground
