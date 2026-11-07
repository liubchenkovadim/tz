FROM php:8.2-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    bash \
    icu-dev \
    libzip-dev \
    mysql-client \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    opcache \
    intl \
    zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Adjust permissions
RUN mkdir -p /var/www/html/var/templates_c \
    && chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]

