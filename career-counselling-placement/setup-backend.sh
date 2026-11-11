#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/ccp-api"
composer install
php ../scripts/prepare-env.php
php artisan config:clear
php artisan route:clear
php artisan migrate --seed
printf '%s\n' 'Backend ready. Run php artisan serve; open another terminal for ccp-web.'
