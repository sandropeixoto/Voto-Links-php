# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala extensões necessárias e utilitários
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ativa o mod_rewrite do Apache
RUN a2enmod rewrite

# --- CORREÇÃO DO ERRO DO CLOUD RUN ---
# O Cloud Run usa a porta 8080. O Apache padrão usa 80.
# Este comando altera as configurações do Apache para escutar na 8080.
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . /var/www/html/

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurações do Apache para ler .htaccess corretamente
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf

# Expõe a porta 8080 (Padrão Cloud Run)
EXPOSE 8080