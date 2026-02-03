# Usa a imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala extensões do sistema necessárias e limpa o cache depois
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Ativa o módulo de reescrita do Apache (Essencial para seu .htaccess funcionar)
RUN a2enmod rewrite

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto para dentro do container
COPY . /var/www/html/

# Ajusta as permissões para o usuário do Apache (www-data)
# Isso evita erros de permissão de escrita/leitura
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configura o Apache para permitir .htaccess (AllowOverride All)
# Substitui a configuração padrão para garantir que o .htaccess seja lido
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/docker-php.conf

# Expõe a porta 80
EXPOSE 80