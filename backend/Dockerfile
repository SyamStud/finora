FROM php:8.4-cli-alpine

RUN apk add --no-cache \
        git \
        unzip \
        postgresql-dev \
        libzip-dev \
        libpng-dev \
        icu-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql bcmath gd intl zip opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --optimize-autoloader

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
