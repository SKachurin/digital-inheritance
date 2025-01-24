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
    && install-php-extensions apcu

COPY ../../composer.json /app/composer.json
COPY ../../composer.lock* /app/composer.lock


FROM vendor as vendor-prod
RUN --mount=type=ssh composer install --no-interaction --no-scripts --no-dev --prefer-dist \
    && composer dump-autoload -o

FROM vendor as vendor-test
RUN --mount=type=ssh composer install --no-interaction --no-scripts --prefer-dist \
    && composer dump-autoload -o


FROM php:8.3.3-fpm-alpine3.19 AS prod

# Set working directory
WORKDIR /var/www

# ARG to pass PGID
ARG PGID

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install necessary packages including shadow
RUN apk add --no-cache shadow

# Ensure PGID is not 0, if so, skip creating new group
RUN if [ "$PGID" -ne 0 ]; then \
    addgroup -g $PGID newgroup && usermod -aG newgroup www-data; \
    fi

ARG DATABASE_URL
ENV DATABASE_URL=${DATABASE_URL}

# Add docker php ext repo
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install dependencies
RUN apk update && apk upgrade && apk add \
    curl \
    nano \
    shadow \
    supervisor && \
    chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mbstring pdo pdo_mysql pcntl opcache intl apcu mcrypt

# Install php extensions
#RUN chmod +x /usr/local/bin/install-php-extensions && sync \
#    && install-php-extensions mbstring pdo pdo_mysql pcntl opcache intl apcu mcrypt

# Copy php config
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Copy supervisor config
COPY ./docker/php-fpm/supervisor.d/prod.ini /etc/supervisor.d/default.ini

# Copy app inside container
COPY ../.. /var/www
COPY --from=vendor-prod /app/vendor/ /var/www/vendor/

RUN chown -R www-data:www-data /var/www

# Ensure /var/log directory and supervisord log file exist with the correct ownership
RUN mkdir -p /var/log && \
    touch /var/log/supervisord.log && \
    chown -R www-data:www-data /var/log

# Set permissions for Symfony console
RUN chmod +x /var/www/bin/console

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