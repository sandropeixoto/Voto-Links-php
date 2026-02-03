# Usa a imagem oficial do PHP 8.2 com Apache
FROM php:8.2-apache

# 1. Instala dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Ativa o mod_rewrite do Apache (Para o .htaccess funcionar)
RUN a2enmod rewrite

# 3. Configura o Apache para usar a porta 8080 (Exigência do Cloud Run)
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 4. Define o diretório de trabalho
WORKDIR /var/www/html

# 5. Copia os arquivos do projeto
COPY . /var/www/html/

# 6. Ajusta permissões (Fundamental para o Apache ler/gravar)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# 7. CORREÇÃO DO ERRO: Habilita .htaccess SEM quebrar o PHP
# Criamos um arquivo de configuração NOVO em vez de sobrescrever o padrão
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/custom-allow-override.conf \
    && a2enconf custom-allow-override

# 8. Expõe a porta 8080
EXPOSE 8080