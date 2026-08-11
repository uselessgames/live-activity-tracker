FROM php:8.3-cli
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql
WORKDIR /app
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
