#!/bin/sh
set -e

# PHP-FPM se ejecuta en segundo plano y Nginx queda como proceso principal para
# que el contenedor responda a SIGTERM correctamente.
php-fpm -D
exec nginx -g 'daemon off;'
