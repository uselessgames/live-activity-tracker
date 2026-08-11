FROM php:8.3-cli
RUN apt-get update && apt-get install -y unzip git \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
WORKDIR /app
COPY composer.json .
RUN composer install
COPY server.php .
EXPOSE 8081 8082
CMD ["php", "server.php"]
