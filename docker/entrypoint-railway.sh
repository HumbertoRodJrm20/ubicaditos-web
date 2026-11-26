#!/bin/sh
set -e

PORT="${PORT:-8080}"
REVERB_UPSTREAM="${REVERB_UPSTREAM:-127.0.0.1:8081}"

sed \
    -e "s/\${PORT}/${PORT}/g" \
    -e "s/\${REVERB_UPSTREAM}/${REVERB_UPSTREAM}/g" \
    /etc/nginx/sites-available/ubicaditos-railway.conf.template \
    > /tmp/ubicaditos.conf

php artisan migrate --force
php-fpm -y /etc/php-fpm-railway.conf -D
exec nginx -c /etc/nginx/nginx-railway.conf -g 'daemon off;'
