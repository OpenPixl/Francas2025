ARG PHP_VERSION

FROM dunglas/frankenphp:1.10-php${PHP_VERSION}-alpine

ARG SYMFONY_VERSION
ARG GIT_USER_EMAIL
ARG GIT_USER_NAME

RUN install-php-extensions intl opcache gd zip pdo_mysql curl xml mbstring json

# Installe Node.js et npm (via Alpine)
RUN apk add --no-cache nodejs npm nano git

RUN git config --global user.email ${GIT_USER_EMAIL}
RUN git config --global user.name ${GIT_USER_NAME}

# Installe Yarn globalement via npm
RUN npm install -g yarn

RUN apk add --no-cache bash
RUN curl -1sLf 'https://dl.cloudsmith.io/public/symfony/stable/setup.alpine.sh' | bash
RUN apk add symfony-cli

# Copier Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
