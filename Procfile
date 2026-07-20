web: frankenphp php-server --listen :8080 --root public/
horizon: php artisan horizon
scheduler: php artisan schedule:work
release: bash -lc "php artisan config:cache && php artisan route:cache && php artisan event:cache && php artisan storage:link"
