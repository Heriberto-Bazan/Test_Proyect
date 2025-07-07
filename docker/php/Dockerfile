FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    wget \
    nano \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    mysqli

# Instalar Composer manualmente
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Crear usuario para evitar problemas de permisos
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Copiar configuración PHP personalizada
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Cambiar propietario de archivos
RUN chown -R www:www /var/www/html

# Cambiar a usuario www
USER www

# Exponer puerto 9000
EXPOSE 9000

CMD ["php-fpm"]