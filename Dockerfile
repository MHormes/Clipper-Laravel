FROM php:8.4-apache

# 1. Install system dependencies (libzip-dev toegevoegd voor de PHP zip extensie)
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libzip-dev zip curl unzip libpq-dev nodejs npm \
    netcat-openbsd git \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install PHP extensions (zip toegevoegd aan de lijst)
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# 3. Configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

WORKDIR /var/www/html

# 4. Copy ONLY dependency manifests
COPY composer.json composer.lock package.json package-lock.json ./

# 5. Install Dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Omdat we nu ook tests willen runnen, installeren we ook de dev-dependencies
RUN composer install --no-scripts --no-autoloader
RUN npm install

# 6. Copy the rest of the application
COPY . .

# 7. Finalize Laravel & Build Assets
RUN composer dump-autoload --optimize
RUN npm run build

# 8. Set permissions AND make start.sh executable
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/scripts/start.sh

CMD ["/var/www/html/scripts/start.sh"]