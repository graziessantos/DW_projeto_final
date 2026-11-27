FROM php:8.3-apache

# Extensões que o README pede: pdo_pgsql e curl
RUN apt-get update \
 && apt-get install -y libpq-dev libcurl4-openssl-dev \
 && docker-php-ext-install pdo_pgsql curl \
 && rm -rf /var/lib/apt/lists/*

# A raiz do servidor precisa ser a pasta public/ (os links começam com "/")
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# O Render informa a porta pela variável PORT
CMD sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf \
 && sed -i "s/:80>/:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground

COPY . /var/www/html/