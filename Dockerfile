# Utiliser l'image officielle PHP avec Apache
FROM php:8.2-apache

# Installation des extensions PHP requises pour Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql \
    && a2enmod rewrite \
    && apt-get clean

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le fichier composer.json et composer.lock dans le conteneur
COPY composer.json composer.lock /var/www/html/

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copier tous les fichiers de l'application Laravel dans le conteneur
COPY . /var/www/html/

# Assurez-vous que le répertoire de travail appartient à l'utilisateur Apache
RUN chown -R www-data:www-data /var/www/html

# Configurer Apache pour servir Laravel à partir du répertoire public
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Installer les dépendances PHP avec Composer (production uniquement)
RUN composer install --no-dev --optimize-autoloader

# Exécuter les commandes artisan pour nettoyer les caches Laravel
RUN php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Ajouter la directive ServerName pour éviter l'erreur Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Exposer le port 80 pour accéder à Apache
EXPOSE 80

# Démarrer Apache en mode foreground
CMD ["apache2-foreground"]
