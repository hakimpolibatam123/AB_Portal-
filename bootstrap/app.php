<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    // Keep the frontend runnable before Composer dependencies are installed.
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
    require BASE_PATH . '/app/Helpers/functions.php';
}
App\Core\Env::load(BASE_PATH . '/.env');
date_default_timezone_set(config('app')['timezone']);
return new App\Core\Router();
