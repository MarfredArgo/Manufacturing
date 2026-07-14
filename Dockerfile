FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first for better layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy the rest of the project
COPY . .

# Run post-install scripts now that full project is copied
RUN composer run-script post-autoload-dump 2>/dev/null || true

# Set storage and cache permissions
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Cache Laravel config, routes, and views for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose the port Render expects
EXPOSE 8080

# Start Laravel server
CMD php artisan serve --host=0.0.0.0 --port=8080
