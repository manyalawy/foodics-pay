#!/bin/bash
set -e

# Only run migrations from the app service, not from horizon
if [ "$1" != "php" ] || [ "$2" != "artisan" ] || [ "$3" != "horizon" ]; then
  php artisan migrate --force
fi

# Execute the passed command (serve or horizon)
exec "$@"