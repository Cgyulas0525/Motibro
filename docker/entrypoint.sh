#!/bin/sh
set -e

if [ -f "composer.json" ] && [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -f "scripts/package.json" ] && [ ! -d "scripts/node_modules" ]; then
    (cd scripts && npm install && npx playwright install chromium --with-deps)
fi

[ -d "storage" ] && chmod -R 775 storage || true
[ -d "bootstrap/cache" ] && chmod -R 775 bootstrap/cache || true

exec "$@"
