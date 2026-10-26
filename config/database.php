<?php
declare(strict_types=1);

// Values come from the environment (.env locally); never hard-code credentials here.
return [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'abportal_db',
    'username' => getenv('DB_USERNAME') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'timezone' => getenv('DB_TIMEZONE') ?: '+07:00',
];
