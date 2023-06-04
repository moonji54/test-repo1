FROM php:8.1-fpm
RUN apt-get update
RUN apt-get install -y build-essential libfreetype6-dev libjpeg-dev apache2 libpng-dev libwebp-dev zlib1g-dev libzip-dev gcc g++ make vim unzip curl git jpegoptim optipng pngquant gifsicle locales libonig-dev nodejs nano  \
        && docker-php-ext-configure gd  \
        && docker-php-ext-install gd \
        # gmp
        && apt-get install -y --no-install-recommends libgmp-dev \
        && docker-php-ext-install gmp \
        # pdo_mysql
        && docker-php-ext-install pdo_mysql mbstring \
        # pdo
        && docker-php-ext-install pdo \
        # opcache
        #&& docker-php-ext-enable opcache \
        # exif
    && docker-php-ext-install exif \
    #&& docker-php-ext-install sockets \
    && docker-php-ext-install pcntl \
    && docker-php-ext-install bcmath \
        # zip
        && docker-php-ext-install zip
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /var/www/html
#COPY composer.json .
#RUN composer install --no-scripts --no-autoloader
COPY . /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install
RUN composer dump-autoload --optimize
RUN chown -R www-data:www-data /var/www/html/
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
#COPY nginx/www.conf /usr/local/etc/php-fpm.d/www.conf

EXPOSE 80

