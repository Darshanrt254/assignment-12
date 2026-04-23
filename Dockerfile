FROM php:8.2-apache

# Install MariaDB server and PHP extensions for MySQL connection
RUN apt-get update && \
    apt-get install -y mariadb-server mariadb-client && \
    docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Copy all project files to the container's document root
COPY . /var/www/html/

# Update Apache to respect the PORT environment variable mapped by Render
RUN sed -s -i -e "s/80/\${PORT}/" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Setup the entrypoint script
RUN chmod +x /var/www/html/entrypoint.sh

# Render uses $PORT for web traffic, we expose it dynamically or use 80 as fallback
EXPOSE 80

# When the container starts, run the entrypoint script
CMD ["/var/www/html/entrypoint.sh"]
