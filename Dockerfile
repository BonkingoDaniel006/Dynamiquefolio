# Image PHP 8.2 avec Apache
FROM php:8.2-apache

# Installation des dépendances système et extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev libzip-dev \
    && docker-php-ext-install intl pdo pdo_mysql pdo_pgsql zip opcache

# Activation du module rewrite d'Apache (indispensable pour Symfony)
RUN a2enmod rewrite

# Configuration du DocumentRoot d'Apache vers /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# Copie des fichiers du projet
WORKDIR /var/www/html
COPY . .

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Nettoyage des droits sur les dossiers de cache et logs
RUN chown -R www-data:www-data var/

EXPOSE 80

CMD ["./render-start.sh"]