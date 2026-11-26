#!/bin/sh
set -e

PORT="${PORT:-80}"
REVERB_UPSTREAM="${REVERB_UPSTREAM:-reverb:8080}"

sed \
    -e "s/\${PORT}/${PORT}/g" \
    -e "s/\${REVERB_UPSTREAM}/${REVERB_UPSTREAM}/g" \
    /etc/nginx/sites-available/ubicaditos-railway.conf.template \
    > /etc/nginx/sites-available/default

php artisan migrate --force
php-fpm -D
exec nginx -g 'daemon off;'
