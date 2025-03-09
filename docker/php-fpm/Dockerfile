FROM composer:2.7.2 as vendor

WORKDIR /app

# Add docker php ext repo
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN mkdir -p -m 0700 ~/.ssh && ssh-keyscan github.com >> ~/.ssh/known_hosts

RUN apk update && apk upgrade

# Install PHP extensions needed for composer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# Install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && sync \
    && install-php-extensions apcu pdo pdo_mysql

#COPY ../../composer.json /app/composer.json
#COPY ../../composer.lock* /app/composer.lock
COPY ../../composer.json /app/
COPY ../../composer.lock /app/


FROM composer:2.7.2 as vendor-prod

WORKDIR /app

# Install required PHP extensions BEFORE composer install
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions apcu pdo pdo_mysql

# Setup SSH for private repositories (optional but recommended)
RUN mkdir -p -m 0700 ~/.ssh && ssh-keyscan github.com >> ~/.ssh/known_hosts

# Composer files first
COPY composer.json composer.lock ./

# Now composer install with extensions properly installed
RUN --mount=type=ssh composer install --no-interaction --no-dev --prefer-dist --no-autoloader --no-scripts

# Finally, copy the full codebase
COPY . /app/

# Finish composer setup
RUN composer dump-autoload --optimize

# Set permissions explicitly
RUN chown -R 1000:1000 vendor && chmod -R 775 vendor


FROM vendor as vendor-test

# Set working directory
WORKDIR /app

# copy the app code again (or do partial copy if you prefer)
COPY . /app/

RUN --mount=type=ssh composer install --no-interaction --prefer-dist \
    && composer dump-autoload -o


FROM php:8.3.3-fpm-alpine3.19 AS prod

WORKDIR /var/www

# FIRST copy composer vendor (dependency rarely changes)
COPY --from=vendor-prod /app/vendor/ /var/www/vendor/

# THEN copy application source code (changes frequently, Docker notices clearly)
COPY . /var/www/

ARG PGID
ARG DATABASE_URL
ENV DATABASE_URL=${DATABASE_URL}

# Add PHP extension installer script once clearly and efficiently
RUN curl -fsSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    -o /usr/local/bin/install-php-extensions && chmod +x /usr/local/bin/install-php-extensions

# Install system packages and PHP extensions clearly in one command
RUN apk update && apk upgrade && apk add --no-cache shadow libcap curl nano supervisor \
    && install-php-extensions mbstring pdo pdo_mysql pcntl opcache intl apcu mcrypt \
    && setcap 'cap_net_bind_service=+ep' /usr/local/sbin/php-fpm

# Configure php.ini
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Supervisor config (correct)
COPY ./docker/php-fpm/supervisord.conf /etc/supervisord.conf
COPY ./docker/php-fpm/supervisor.d/prod.ini /etc/supervisor.d/default.ini

# Permissions after copying everything
RUN chown -R www-data:www-data /var/www \
    && chmod +x bin/console \
    && mkdir -p var/cache var/log /var/log \
    && chown -R www-data:www-data var/cache var/log /var/log \
    && chmod -R 775 var/cache var/log /var/log

# Correct supervisor entrypoint (original and correct)
CMD ["supervisord", "--nodaemon", "-c", "/etc/supervisord.conf"]

EXPOSE 9000


FROM prod as test

ARG DATABASE_URL
ENV DATABASE_URL=${DATABASE_URL}

# Copy php config
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# Copy supervisor config
COPY ./docker/php-fpm/supervisor.d/test.ini /etc/supervisor.d/default.ini

RUN rm -rf /var/www/vendor/
COPY --from=vendor-test /app/vendor/ /var/www/vendor/

RUN chown -R www-data:www-data /var/www


FROM test as local

ARG PUID
ARG PGID
ENV PUID=${PUID}
ENV PGID=${PGID}

RUN unset DATABASE_URL

RUN apk add \
        shadow \
        openssh-client \
        git

# Copy supervisor config
COPY ./docker/php-fpm/supervisor.d/local.ini /etc/supervisor.d/default.ini

RUN install-php-extensions xdebug
ADD /docker/php-fpm/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# --non-unique фикс для мака\локальной разработки где группа хоста staff 20 пересекается с группой линукса 20 dialout
# или же на случай если пересечется что-то другое
RUN groupmod --non-unique --gid $PGID www-data
RUN usermod --non-unique --uid $PUID --gid $PGID www-data

RUN chown -R www-data:www-data /var/www

# not tested

#FROM composer:2.7.2 as vendor
#
#WORKDIR /app
#
#RUN mkdir -p -m 0700 ~/.ssh && ssh-keyscan github.com >> ~/.ssh/known_hosts
#
## Add PHP extension installer
#ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
#RUN chmod +x /usr/local/bin/install-php-extensions && sync \
#    && install-php-extensions apcu pdo pdo_mysql
#
#COPY ../../composer.json composer.json
#COPY ../../composer.lock composer.lock
#
#FROM vendor as vendor-prod
#
## Dependencies first
#RUN --mount=type=ssh composer install --no-interaction --no-dev --prefer-dist --no-autoloader --no-scripts
#
## Application source code
#COPY ../../ /app/
#
## Finalize autoload
#RUN composer dump-autoload --optimize
#
#RUN chown -R 1000:1000 vendor && chmod -R 775 vendor
