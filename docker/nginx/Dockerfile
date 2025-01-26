FROM nginx:1.25.4-alpine3.18

# Install libcap to enable setting capabilities
RUN apk add --no-cache libcap && \
    setcap 'cap_net_bind_service=+ep' /usr/sbin/nginx

# Copy Nginx configuration
ADD /docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# Set working directory
WORKDIR /var/www/public

# Copy application public files
COPY ../../public /var/www/public

# Expose port 80 for Nginx
EXPOSE 80