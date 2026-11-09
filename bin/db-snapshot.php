<?php
declare(strict_types=1);

// Writes the portal's working data to database/snapshot/data.json so it travels with the repository.
// Run it before a commit whenever a clone should start with the latest data:
//   php bin/db-snapshot.php
// Only reads the database. See App\Services\DataSnapshot for what is deliberately left out.

use App\Core\Database;
use App\Services\DataSnapshot;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

try {
    $json = DataSnapshot::export(Database::connection());
    $path = BASE_PATH . DataSnapshot::FILE;
    if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0775, true) && !is_dir(dirname($path))) {
        throw new RuntimeException('Folder database/snapshot tidak bisa dibuat.');
    }
    if (file_put_contents($path, $json, LOCK_EX) === false) {
        throw new RuntimeException('File snapshot tidak bisa ditulis.');
    }
    $tables = DataSnapshot::decode($json);
    echo 'Snapshot ditulis: database/snapshot/data.json' . PHP_EOL;
    foreach ($tables as $table => $data) {
        echo '  ' . str_pad($table, 22) . count($data['rows']) . ' baris' . PHP_EOL;
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Snapshot gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
