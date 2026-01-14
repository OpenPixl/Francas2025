FROM dunglas/frankenphp:1.10-php8.4

RUN install-php-extensions \
	pdo_mysql \
	gd \
	intl \
	zip \
	opcache

WORKDIR /app
