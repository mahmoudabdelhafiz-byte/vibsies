#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
echo "Vibsies Laravel regression preflight"
command -v php >/dev/null || { echo "FAIL: PHP missing"; exit 1; }
php -r 'if(PHP_VERSION_ID < 80300){fwrite(STDERR,"FAIL: PHP 8.3+ required\n");exit(1);}'
for ext in mbstring openssl pdo pdo_sqlite tokenizer xml dom ctype fileinfo; do
  php -r 'exit(extension_loaded($argv[1]) ? 0 : 1);' "$ext" || { echo "FAIL: PHP extension $ext missing"; exit 1; }
done
command -v composer >/dev/null || { echo "FAIL: Composer 2 missing"; exit 1; }
test -f vendor/autoload.php || { echo "FAIL: run composer install first"; exit 1; }
echo "Checking PHP syntax..."
while IFS= read -r -d '' file; do php -l "$file" >/dev/null || exit 1; done < <(find app bootstrap database routes tests -name '*.php' -print0)
composer validate --no-check-publish
php artisan --version
php artisan route:list
php artisan test --stop-on-failure
echo "PASS: Full Laravel test suite completed"
