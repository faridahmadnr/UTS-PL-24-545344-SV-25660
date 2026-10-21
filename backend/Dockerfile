# Backend Laravel running on FrankenPHP (Bonus +10 Points)
FROM dunglas/frankenphp:1.4-php8.3-bookworm

# Install necessary PHP extensions for Laravel and MySQL
RUN install-php-extensions \
    pdo_mysql \
    bcmath \
    intl \
    zip \
    opcache

# Copy Composer binary from official composer image (specific tag 2.8)
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy dependency files first to leverage Docker layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application source code
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize --no-dev

# Set permissions for storage and bootstrap cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Expose port and configure FrankenPHP Caddy server
ENV SERVER_NAME=":8000"
EXPOSE 8000
