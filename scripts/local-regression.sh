#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
echo "Vibsies Laravel local regression preflight"
command -v php >/dev/null || { echo "FAIL: PHP missing"; exit 1; }
php -v | head -n 1
php -r 'if(PHP_VERSION_ID < 80300){fwrite(STDERR,"PHP 8.3+ required\n");exit(1);}'
for ext in mbstring openssl pdo token­izer xml ctype fileinfo; do
  ext="${ext//­/}"
  php -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" || { echo "FAIL: PHP extension $ext missing"; exit 1; }
done
command -v composer >/dev/null || { echo "FAIL: Composer 2 missing"; exit 1; }
php -r 'exit(extension_loaded("pdo_sqlite") || extension_loaded("pdo_pgsql") ? 0 : 1);' || { echo "FAIL: install pdo_sqlite (test) or pdo_pgsql (production)"; exit 1; }
test -f vendor/autoload.php || { echo "FAIL: run composer install first"; exit 1; }
composer validate --no-check-publish
php artisan --version
php artisan route:list
php artisan test --stop-on-failure
echo "PASS: Laravel regression suite completed"
