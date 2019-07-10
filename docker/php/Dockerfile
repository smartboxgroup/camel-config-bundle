FROM composer:1 AS composer

FROM php:7.0-fpm

COPY php.ini /usr/local/etc/php/php.ini

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -qq \
    libxml2-dev \
    git \
    unzip

RUN docker-php-ext-install \
    soap \
    pcntl

RUN pecl install apcu-5.1.8

RUN docker-php-ext-enable \
    apcu