<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    /** Shared connection built from config/database.php, opened on first use. */
    public static function connection(): PDO
    {
        return self::$connection ??= self::connect(config('database'));
    }

    public static function connect(array $config): PDO
    {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('The pdo_mysql PHP extension is not enabled. Enable "extension=pdo_mysql" in php.ini.');
        }
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']);
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->prepare('SET time_zone = ?')->execute([$config['timezone']]);
        return $pdo;
    }
}
