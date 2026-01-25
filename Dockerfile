# ========= Base commune =========
FROM dunglas/frankenphp:1-php8.4-alpine AS base
WORKDIR /app

# Paquets système (intl, zip, gd, etc. si besoin)
RUN apk add --no-cache \
    icu-dev oniguruma-dev libzip-dev zlib-dev libpng libpng-dev git bash \
 && docker-php-ext-configure intl \
 && docker-php-ext-install -j$(nproc) intl pdo_mysql opcache \
 && apk del libpng-dev || true

# Composer (fourni par l'image frankenphp via /usr/local/bin/composer)
# Xdebug optionnel en dev
ARG INSTALL_XDEBUG=false
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      apk add --no-cache $PHPIZE_DEPS linux-headers \
      && pecl install xdebug \
      && docker-php-ext-enable xdebug \
      && apk del $PHPIZE_DEPS linux-headers ; \
    fi

# Configuration PHP
COPY ./config/php.ini /usr/local/etc/php/conf.d/custom.ini

# Caddyfile pour FrankenPHP
COPY ./config/Caddyfile /etc/caddy/Caddyfile

# >>> AJOUTER COMPOSER <<<
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# >>> AJOUTER SYMFONY CLI <<<
RUN wget https://github.com/symfony-cli/symfony-cli/releases/latest/download/symfony-cli_linux_amd64.tar.gz \
    && tar -xzf symfony-cli_linux_amd64.tar.gz -C /usr/local/bin \
    && rm symfony-cli_linux_amd64.tar.gz \
    && chmod +x /usr/local/bin/symfony

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    nodejs npm

# ========= Image Dev =========
FROM base AS dev
ENV APP_ENV=dev
# Rien à copier : on montera le code en volume
EXPOSE 8080
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
