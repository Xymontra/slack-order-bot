FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    unzip \
    && docker-php-ext-install curl

COPY . /app
WORKDIR /app

CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
