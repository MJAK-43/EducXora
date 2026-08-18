FROM php:8.4-fpm-bookworm AS php-base

ARG APP_UID=1000
ARG APP_GID=1000

ENV COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl opcache pcntl pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/eduxora.ini

WORKDIR /var/www/html

RUN groupmod --gid "${APP_GID}" www-data \
    && usermod --uid "${APP_UID}" --gid "${APP_GID}" www-data \
    && git config --system --add safe.directory /var/www/html

FROM php-base AS development

COPY --chown=www-data:www-data . .
RUN composer install --no-interaction --prefer-dist --no-progress \
    && mkdir -p storage/framework/cache/phpstan \
    && chown -R www-data:www-data storage bootstrap/cache vendor /tmp/composer

USER www-data

CMD ["php-fpm"]

FROM node:24-alpine AS frontend-build

WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY postcss.config.js tsconfig.json vite.config.js ./
RUN npm run build

FROM php-base AS production

ENV APP_ENV=production \
    APP_DEBUG=false

COPY --chown=www-data:www-data . .
RUN composer install --no-dev --classmap-authoritative --no-interaction --prefer-dist --no-progress \
    && chown -R www-data:www-data storage bootstrap/cache vendor
COPY --from=frontend-build --chown=www-data:www-data /app/public/build ./public/build

USER www-data

CMD ["php-fpm"]
