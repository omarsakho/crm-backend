# Utilisation de l'image officielle PHP avec Apache
FROM php:8.2-apache

# Installation des extensions PHP requises
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql && \
    a2enmod rewrite

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le fichier composer.json et installer Composer
COPY composer.json composer.lock /var/www/html/
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copier les fichiers de l'application dans le conteneur
COPY . /var/www/html/

# Assurez-vous que www-data possède les fichiers
RUN chown -R www-data:www-data /var/www/html

# Configurer Apache pour servir à partir du répertoire public de Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Nettoyer les caches Laravel
RUN php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Ajouter la directive ServerName pour éviter l'erreur Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Exposer le port 80 pour le serveur Apache
EXPOSE 80

# Démarrer Apache en mode foreground
CMD ["apache2-foreground"]
