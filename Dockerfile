FROM dunglas/frankenphp:1.10-php8.5-alpine

RUN install-php-extensions intl opcache gd zip pdo_mysql

# Installe Node.js et npm (via Alpine)
RUN apk add --no-cache nodejs npm

# Installe Yarn globalement via npm
RUN npm install -g yarn

# Copier Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
