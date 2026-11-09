<?php
declare(strict_types=1);

// Versioned database changes (database/migrations/, see App\Core\Migrator).
// Usage:
//   php bin/migrate.php            apply every pending migration (one new batch); on a new install it
//                                  also creates the database if missing and loads
//                                  database/snapshot/data.json (or the seeds when that file is absent)
//   php bin/migrate.php status     list applied and pending migrations
//   php bin/migrate.php down       undo the last batch (asks to type YA; --yes skips the question)
//   php bin/migrate.php seed       run database/seeds/*.php (each seed skips tables that already hold data)
// Back up the database before a migration that changes data (see README, "Backup sebelum migrasi").

use App\Core\Database;
use App\Core\Migrator;
use App\Services\DataSnapshot;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/bootstrap/app.php';

$command = $argv[1] ?? 'up';
$database = config('database')['database'];
$report = static function (string $line): void {
    echo '  ' . $line . PHP_EOL;
};

try {
    try {
        $pdo = Database::connection();
    } catch (PDOException $missing) {
        // A fresh install: `migrate` creates the database named in DB_DATABASE (utf8mb4), then
        // carries on. Other commands, and any other connection error, stop here as before.
        if ($command !== 'up' || (int) ($missing->errorInfo[1] ?? 0) !== 1049 || !preg_match('/^[A-Za-z0-9_]{1,64}$/', $database)) {
            throw $missing;
        }
        $server = Database::connect(['database' => ''] + config('database'));
        $server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database {$database} belum ada, sudah dibuat." . PHP_EOL;
        $pdo = Database::connection();
    }
    $migrator = new Migrator($pdo, BASE_PATH . '/database/migrations');
    $migrator->prepare();
    $runSeeds = static function () use ($pdo): void {
        $seeds = glob(BASE_PATH . '/database/seeds/*.php') ?: [];
        sort($seeds, SORT_STRING);
        if ($seeds === []) {
            echo 'Tidak ada seed.' . PHP_EOL;
            return;
        }
        foreach ($seeds as $seed) {
            echo '  Seed ' . basename($seed) . PHP_EOL;
            (require $seed)($pdo);
        }
    };

    switch ($command) {
        case 'up':
            echo "Migrasi database {$database}" . PHP_EOL;
            // A database with no migration yet is a new install: load the committed snapshot, or the
            // starting dashboard when there is none, so one command is enough. Existing databases
            // never receive either here.
            $fresh = $migrator->applied() === [];
            $count = $migrator->up($report);
            echo $count === 0 ? 'Tidak ada migrasi baru.' . PHP_EOL : "Selesai: {$count} migrasi diterapkan." . PHP_EOL;
            if ($fresh && $count > 0) {
                $snapshot = BASE_PATH . DataSnapshot::FILE;
                if (is_file($snapshot)) {
                    echo 'Database baru: memuat data dari database/snapshot/data.json' . PHP_EOL;
                    $loaded = DataSnapshot::import($pdo, (string) file_get_contents($snapshot));
                    echo '  ' . count($loaded) . ' tabel, ' . array_sum($loaded) . ' baris dimuat.' . PHP_EOL;
                } else {
                    echo 'Database baru: mengisi data awal' . PHP_EOL;
                    $runSeeds();
                }
                echo 'Langkah berikutnya: buat akun admin dengan php bin/create-admin.php' . PHP_EOL;
            }
            break;

        case 'status':
            $applied = $migrator->applied();
            $files = $migrator->files();
            echo "Status migrasi database {$database}" . PHP_EOL;
            foreach (array_unique(array_merge(array_keys($applied), $files)) as $name) {
                $state = isset($applied[$name]) ? 'sudah (batch ' . $applied[$name] . ')' : 'belum';
                $missing = in_array($name, $files, true) ? '' : ' (file tidak ada)';
                echo "  [{$state}] {$name}{$missing}" . PHP_EOL;
            }
            break;

        case 'down':
            $last = Migrator::lastBatchOf($migrator->applied());
            if ($last === []) {
                echo 'Tidak ada migrasi untuk dibatalkan.' . PHP_EOL;
                break;
            }
            echo "Akan membatalkan pada database {$database}:" . PHP_EOL . '  ' . implode(PHP_EOL . '  ', $last) . PHP_EOL;
            if (!in_array('--yes', $argv, true)) {
                echo 'Pastikan sudah ada backup. Ketik YA untuk melanjutkan: ';
                if (trim((string) fgets(STDIN)) !== 'YA') {
                    echo 'Dibatalkan. Tidak ada perubahan.' . PHP_EOL;
                    break;
                }
            }
            $count = $migrator->down($report);
            echo "Selesai: {$count} migrasi dibatalkan." . PHP_EOL;
            break;

        case 'seed':
            $runSeeds();
            break;

        default:
            fwrite(STDERR, "Perintah tidak dikenal: {$command}. Pakai: up, status, down, seed." . PHP_EOL);
            exit(1);
    }
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migrasi gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
