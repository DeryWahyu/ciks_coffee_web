# Stage 1: Build PHP dependencies
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts --ignore-platform-reqs

# Stage 2: Build frontend assets
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 3: Setup Production Image
FROM php:8.3-fpm-bookworm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    cron \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Copy PHP dependencies from vendor stage
COPY --from=vendor /app/vendor/ /var/www/html/vendor/

# Copy Frontend build from frontend stage
COPY --from=frontend /app/public/build/ /var/www/html/public/build/

# Copy application files
COPY . .

# Copy Nginx configuration
COPY docker/nginx.conf /etc/nginx/sites-available/default
# Remove the default index.html and link our config
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Copy Supervisord configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy Entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set correct permissions for Laravel storage and bootstrap cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Remove unnecessary files from production image
RUN rm -rf node_modules tests Dockerfile docker .dockerignore .github

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
