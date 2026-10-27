<?php
declare(strict_types=1);

// Runs database/schema.sql, then every file in database/migrations/ that has not run yet, in name
// order. A .sql file holds statements; a .php file returns function (PDO $pdo): void. Each applied
// file is recorded in schema_migrations, so running this again only applies new files.
// Usage: php bin/migrate.php

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

/** Statements of a .sql file: "--" comment lines dropped, split on a semicolon that ends a line. */
function sql_statements(string $sql): array
{
    $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
    return array_values(array_filter(
        array_map('trim', preg_split('/;\s*$/m', $sql) ?: []),
        static fn (string $statement): bool => $statement !== ''
    ));
}

function run_statements(PDO $pdo, array $statements, array $existing): void
{
    foreach ($statements as $statement) {
        $pdo->prepare($statement)->execute();
        if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $match) === 1) {
            $status = in_array($match[1], $existing, true) ? 'sudah ada' : 'dibuat';
            echo "  [{$status}] {$match[1]}" . PHP_EOL;
        } else {
            echo '  [ok] ' . substr((string) preg_replace('/\s+/', ' ', $statement), 0, 60) . PHP_EOL;
        }
    }
}

$schemaFile = BASE_PATH . '/database/schema.sql';
$schema = is_file($schemaFile) ? file_get_contents($schemaFile) : false;
if ($schema === false) {
    fwrite(STDERR, "File skema tidak ditemukan: database/schema.sql" . PHP_EOL);
    exit(1);
}

try {
    $pdo = Database::connection();
    $database = config('database')['database'];
    $listTables = $pdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
    $listTables->execute([$database]);
    $existing = array_column($listTables->fetchAll(), 'TABLE_NAME');

    echo "Menjalankan migrasi pada database {$database}" . PHP_EOL;
    $statements = sql_statements($schema);
    run_statements($pdo, $statements, $existing);
    echo 'Skema dasar selesai: ' . count($statements) . ' perintah dijalankan.' . PHP_EOL;

    $applied = array_column($pdo->query('SELECT migration FROM schema_migrations')->fetchAll(), 'migration');
    $files = glob(BASE_PATH . '/database/migrations/*.{sql,php}', GLOB_BRACE) ?: [];
    sort($files, SORT_STRING);
    $record = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
    $count = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            continue;
        }
        echo "Migrasi {$name}" . PHP_EOL;
        if (str_ends_with($name, '.sql')) {
            run_statements($pdo, sql_statements((string) file_get_contents($file)), $existing);
        } else {
            $migration = require $file;
            $migration($pdo);
        }
        $record->execute([$name]);
        $count++;
    }
    echo $count === 0 ? 'Tidak ada migrasi baru.' . PHP_EOL : "Migrasi selesai: {$count} file baru diterapkan." . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migrasi gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
