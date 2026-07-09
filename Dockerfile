FROM php:8.3-apache

# libpq-dev es necesario para compilar la extensión pdo_pgsql (no viene en la imagen base)
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY . /var/www/html/
WORKDIR /var/www/html

# Apache (en vez del servidor embebido de PHP) maneja varias peticiones en paralelo,
# necesario para no colapsar con tráfico concurrente.
# Render inyecta el puerto real a usar en la variable de entorno PORT; el reemplazo
# se hace al arrancar el contenedor porque el valor no se conoce en tiempo de build.
ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "sed -ri \"s/Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf && sed -ri \"s/:80/:${PORT}/\" /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
