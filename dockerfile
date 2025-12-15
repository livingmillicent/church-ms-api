# Official PHP image with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (Laravel + PostgreSQL)
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd

# Enable Apache rewrite
RUN a2enmod rewrite

# REMOVE any previously cached PassEnv directives
RUN sed -i '/PassEnv/d' /etc/apache2/apache2.conf

# IMPORTANT: Pass Render environment variables to Apache/PHP
RUN echo "PassEnv APP_KEY APP_ENV APP_DEBUG DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD" \
    >> /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html

# Install PHP dependencies (disable Laravel scripts)
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

# Clear cached Laravel config (prevents empty APP_KEY cache)
RUN php artisan config:clear || true

# Correct Laravel permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 storage bootstrap/cache

# Apache virtual host configuration
RUN printf "<VirtualHost *:80>\n\
    ServerName localhost\n\
    DocumentRoot /var/www/html/public\n\
\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php index.html\n\
    </Directory>\n\
\n\
    ErrorLog \${APACHE_LOG_DIR}/error.log\n\
    CustomLog \${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n" > /etc/apache2/sites-available/000-default.conf

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
