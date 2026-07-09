FROM php:8.3-cli

# libpq-dev es necesario para compilar la extensión pdo_pgsql (no viene en la imagen base)
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql fileinfo \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

# Render inyecta el puerto real a usar en la variable de entorno PORT.
# 10000 es solo un valor por defecto para pruebas locales con "docker run".
ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /var/www/html"]
