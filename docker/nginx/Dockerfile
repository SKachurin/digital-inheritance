FROM nginx:1.25.4-alpine3.18

ADD /docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/public

COPY ../../public /var/www/public
