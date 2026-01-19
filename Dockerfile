FROM dunglas/frankenphp:1.10-php8.4

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    nodejs npm

RUN install-php-extensions \
	pdo_mysql \
	gd \
	intl \
	zip \
	opcache

RUN mkdir -p /var/lib/php/sessions \
 && chown -R www-data:www-data /var/lib/php/sessions

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
