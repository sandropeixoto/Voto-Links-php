# Usa a imagem oficial do PHP 8.2 com Apache
FROM php:8.2-apache

# 1. Instala dependências e extensões
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Ativa o mod_rewrite
RUN a2enmod rewrite

# 3. Configura porta 8080 (Cloud Run)
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 4. AUMENTO DE LIMITES PHP (Upload até 10MB)
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# 5. Configurações de diretório
WORKDIR /var/www/html
COPY . /var/www/html/

# 6. Permissões
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# 7. Permite .htaccess customizado
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom-allow-override.conf \
    && a2enconf custom-allow-override

EXPOSE 8080