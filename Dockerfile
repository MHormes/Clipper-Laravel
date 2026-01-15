FROM php:8.4-apache

# 1. Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev zip curl unzip libpq-dev nodejs npm

# 2. Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# 3. Configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

WORKDIR /var/www/html

# 4. Copy ONLY dependency manifests first
COPY composer.json composer.lock package.json package-lock.json /var/www/html/

# 5. Install Dependencies (This layer is cached unless .json/.lock files change)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --no-scripts --no-autoloader
RUN npm install

# 6. NOW copy the rest of the application
# (This is fast if you created the .dockerignore file)
COPY . /var/www/html

# 7. Finalize Laravel & Build Assets
RUN composer dump-autoload --optimize
RUN npm run build

# 8. Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

CMD ["sh", "./start.sh"]