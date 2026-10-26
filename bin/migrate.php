<?php
declare(strict_types=1);

// Runs database/schema.sql against the database configured in .env.
// Usage: php bin/migrate.php

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

$schemaFile = BASE_PATH . '/database/schema.sql';
$schema = is_file($schemaFile) ? file_get_contents($schemaFile) : false;
if ($schema === false) {
    fwrite(STDERR, "File skema tidak ditemukan: database/schema.sql" . PHP_EOL);
    exit(1);
}

// Drop "--" comment lines, then split on a semicolon that ends a line.
$schema = (string) preg_replace('/^\s*--.*$/m', '', $schema);
$statements = array_values(array_filter(
    array_map('trim', preg_split('/;\s*$/m', $schema) ?: []),
    static fn (string $statement): bool => $statement !== ''
));

try {
    $pdo = Database::connection();
    $database = config('database')['database'];
    $listTables = $pdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
    $listTables->execute([$database]);
    $existing = array_column($listTables->fetchAll(), 'TABLE_NAME');

    echo "Menjalankan migrasi pada database {$database}" . PHP_EOL;
    foreach ($statements as $statement) {
        $pdo->prepare($statement)->execute();
        if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $match) === 1) {
            $status = in_array($match[1], $existing, true) ? 'sudah ada' : 'dibuat';
            echo "  [{$status}] {$match[1]}" . PHP_EOL;
        } else {
            echo '  [ok] ' . substr((string) preg_replace('/\s+/', ' ', $statement), 0, 60) . PHP_EOL;
        }
    }
    echo 'Migrasi selesai: ' . count($statements) . ' perintah dijalankan.' . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migrasi gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
