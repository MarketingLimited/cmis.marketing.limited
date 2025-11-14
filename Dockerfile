# Multi-stage Dockerfile for Laravel Application
FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm \
    icu-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# Copy package files
COPY package.json package-lock.json ./

# Install Node dependencies
RUN npm ci --only=production

# Copy application files
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Build arguments
ARG APP_ENV=production
ARG BUILD_DATE
ARG VCS_REF

# Set labels
LABEL org.opencontainers.image.created="${BUILD_DATE}" \
      org.opencontainers.image.source="https://github.com/MarketingLimited/cmis.marketing.limited" \
      org.opencontainers.image.version="${VCS_REF}" \
      org.opencontainers.image.vendor="Marketing Limited" \
      org.opencontainers.image.title="CMIS Laravel Application" \
      org.opencontainers.image.description="Marketing Limited CMIS Application"

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
