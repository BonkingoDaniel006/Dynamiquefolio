# Image PHP 8.3 avec Apache
FROM php:8.3-apache

# Installation des dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql pdo_pgsql zip opcache

# Activation de mod_rewrite pour Apache
RUN a2enmod rewrite

# Configuration du DocumentRoot d'Apache vers /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Définition de l'environnement en production pour la phase de build
ENV APP_ENV=prod

WORKDIR /var/www/html
COPY . .

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Compilation des assets avec la bonne syntaxe (asset-map:compile)
RUN php bin/console asset-map:compile

# Droits sur le dossier cache/logs
RUN chown -R www-data:www-data var/

EXPOSE 80

CMD ["./render-start.sh"]