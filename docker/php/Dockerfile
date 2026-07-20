FROM php:8.4-fpm

# ─── System Dependencies ─────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    postgresql-client \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    fcgiwrap \
    libfcgi-bin \
    && rm -rf /var/lib/apt/lists/*

# ─── PHP Extensions ──────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        bcmath \
        pcntl \
        opcache \
        mbstring \
        xml

# ─── Redis Extension ─────────────────────────────────────────────────────────
RUN pecl install redis \
    && docker-php-ext-enable redis

# ─── PHP-FPM Health Check ────────────────────────────────────────────────────
COPY docker/php/php-fpm-healthcheck /usr/local/bin/php-fpm-healthcheck
RUN chmod +x /usr/local/bin/php-fpm-healthcheck

# ─── PHP Configuration ───────────────────────────────────────────────────────
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/php-fpm-healthcheck.conf /usr/local/etc/php-fpm.d/zz-healthcheck.conf

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─── Application Code ────────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# ─── Install Dependencies ────────────────────────────────────────────────────
RUN if [ -f composer.json ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-scripts; \
    fi

# ─── Permissions ──────────────────────────────────────────────────────────────
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
