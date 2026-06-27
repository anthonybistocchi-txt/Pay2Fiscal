FROM php:8.4-cli-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    unzip \
    zip \
  && docker-php-ext-configure intl \
  && docker-php-ext-install -j"$(nproc)" \
    bcmath \
    intl \
    mbstring \
    opcache \
    pcntl \
    pdo \
    pdo_pgsql \
    zip \
  && rm -rf /tmp/*

RUN curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV APP_ENV=local \
    APP_DEBUG=true \
    LOG_CHANNEL=stack \
    PHP_OPCACHE_ENABLE=0

EXPOSE 8000

CMD ["sh", "-lc", "cd laravel && composer install --no-interaction && php artisan serve --host=0.0.0.0 --port=8000"]
