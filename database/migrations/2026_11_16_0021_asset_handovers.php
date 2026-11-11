<?php
declare(strict_types=1);

// D14: Serah Terima Perangkat of Aset IT: one record per berita acara (who hands over, who receives, the devices,
// the two signatures as attachments of owner "asset"). Only a new table; no existing data changes.
// Undo (`php bin/migrate.php down`): the table is dropped with every serah terima in it.

return [
    'up' => static function (PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS asset_handovers (
            id VARCHAR(20) NOT NULL PRIMARY KEY,
            data_json MEDIUMTEXT NOT NULL,
            created_at VARCHAR(30) NOT NULL,
            KEY asset_handovers_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    },
    'down' => static function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS asset_handovers');
    },
];
