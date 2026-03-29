FROM php:8.2-cli

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY composer.json composer.lock ./

# Show verbose output so we can see the real error
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader --verbose --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --ignore-platform-reqs

RUN php artisan config:cache && php artisan route:cache

EXPOSE 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]