#!/bin/sh
set -e
# When the repo is bind-mounted without a vendor/ directory, install first.
if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi
exec "$@"
