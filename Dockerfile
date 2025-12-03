# CMIS - Cognitive Marketing Intelligence Suite
# Multi-stage Dockerfile for Laravel Application with Nginx + Supervisor

FROM php:8.2-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    nodejs \
    npm \
    icu-dev \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    supervisor \
    nginx

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    opcache

# Install Redis extension
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

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
    --prefer-dist \
    --optimize-autoloader

# Copy package files
COPY package.json package-lock.json ./

# Install Node dependencies and build assets
RUN npm ci --only=production

# Copy application files
COPY . .

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Build frontend assets
RUN npm run build

# Copy configuration files
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini /usr/local/etc/php/php.ini

# Create necessary directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /var/log/nginx \
    && mkdir -p /var/log/php

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
      org.opencontainers.image.description="CMIS - Cognitive Marketing Intelligence Suite"

# Expose port (Nginx)
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
    CMD curl -f http://localhost/health || exit 1

# Start supervisor to manage PHP-FPM and Nginx
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
