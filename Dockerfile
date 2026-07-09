FROM php:8.3-cli

RUN docker-php-ext-install pdo pdo_pgsql fileinfo

WORKDIR /var/www/html
COPY . /var/www/html/

# Render inyecta el puerto real a usar en la variable de entorno PORT.
# 10000 es solo un valor por defecto para pruebas locales con "docker run".
ENV PORT=10000
EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t /var/www/html"]
