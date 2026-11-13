<?php
declare(strict_types=1);

// D15: Form Clearance (a leaving employee's clearance across the divisions): one record per employee, the
// answers and signatures (attachments of owner "clearance") in data_json. Only a new table; no existing data changes.
// Undo (`php bin/migrate.php down`): the table is dropped with every clearance in it.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS clearances (
            id VARCHAR(20) NOT NULL PRIMARY KEY,
            data_json MEDIUMTEXT NOT NULL,
            created_at VARCHAR(30) NOT NULL,
            updated_at VARCHAR(30) NOT NULL,
            KEY clearances_updated (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS clearances');
    },
];
