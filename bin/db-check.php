<?php
declare(strict_types=1);

// Verifies the MySQL connection configured in .env. Never prints the password.
// Usage: php bin/db-check.php

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

$config = config('database');
try {
    $pdo = Database::connection();

    $version = $pdo->prepare('SELECT VERSION() AS version, @@session.time_zone AS time_zone');
    $version->execute();
    $server = $version->fetch();

    $schema = $pdo->prepare('SELECT DEFAULT_CHARACTER_SET_NAME AS charset, DEFAULT_COLLATION_NAME AS collation FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $schema->execute([$config['database']]);
    $database = $schema->fetch() ?: ['charset' => '-', 'collation' => '-'];

    $tables = $pdo->prepare('SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_COLLATION AS collation FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME');
    $tables->execute([$config['database']]);
    $tableRows = $tables->fetchAll();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Koneksi database gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

echo 'Koneksi MySQL berhasil' . PHP_EOL;
echo "  Host        : {$config['host']}:{$config['port']}" . PHP_EOL;
echo "  Pengguna    : {$config['username']}" . PHP_EOL;
echo "  Database    : {$config['database']}" . PHP_EOL;
echo "  Versi MySQL : {$server['version']}" . PHP_EOL;
echo "  Kolasi DB   : {$database['collation']} ({$database['charset']})" . PHP_EOL;
echo "  Zona waktu  : {$server['time_zone']}" . PHP_EOL;
echo '  Tabel (' . count($tableRows) . '):' . PHP_EOL;
if ($tableRows === []) {
    echo '    (belum ada tabel, jalankan php bin/migrate.php)' . PHP_EOL;
}
foreach ($tableRows as $table) {
    echo "    - {$table['name']} ({$table['engine']}, {$table['collation']})" . PHP_EOL;
}
