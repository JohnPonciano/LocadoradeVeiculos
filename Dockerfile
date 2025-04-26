FROM laravelsail/php82-composer

# Instalar dependências e limpar depois
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mysqli \
    && rm -rf /var/lib/apt/lists/*

# Configurar PHP para melhor desempenho
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/custom.ini

# Configurar o diretório de trabalho
WORKDIR /var/www/html

# Garantir permissões corretas para storage e cache
RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Copiar arquivos do composer
COPY composer.json composer.lock ./

# Instalar dependências
RUN composer install --no-scripts --no-autoloader --no-dev

# Copiar o resto dos arquivos
COPY . .

# Adicionar script de inicialização
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Finalizar composer install
RUN composer dump-autoload --optimize && \
    chmod +x /var/www/html/artisan

# Expor a porta
EXPOSE 80

# Definir o comando de inicialização
CMD ["/usr/local/bin/start.sh"]
