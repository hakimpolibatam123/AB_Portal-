<?php
declare(strict_types=1);

namespace App\Services;

use JsonException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * The portal's working data as one file in the repository (database/snapshot/data.json), so a fresh
 * clone starts with the same forms, reports and assets. Written by bin/db-snapshot.php, loaded by
 * bin/migrate.php on a new install only.
 *
 * TABLES is an allow-list on purpose: a table reaches the repository only if it is named here. Left
 * out are users (password hash), app_settings (SMTP account), login_attempts and audit_logs (IP
 * addresses), the per-user read marks, sent-alert recipients, and retired legacy_ and m1_ tables.
 */
final class DataSnapshot
{
    public const FILE = '/database/snapshot/data.json';

    private const FORMAT = 1;

    public const TABLES = [
        'asset_activity',
        'asset_devices',
        'asset_disposals',
        'asset_moves',
        'asset_services',
        'attachments',
        'dashboard_cards',
        'dashboard_sections',
        'form_entries',
        'form_schedule_dates',
        'form_schedules',
        'form_versions',
        'forms',
        'number_sequences',
    ];

    /** Reads every listed table, in primary-key order so an unchanged database gives an unchanged file. */
    public static function export(PDO $pdo): string
    {
        $tables = [];
        foreach (self::TABLES as $table) {
            $columns = self::columns($pdo, $table);
            $list = implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns));
            $order = implode(', ', array_map(static fn (string $c): string => "`{$c}`", self::primaryKey($pdo, $table) ?: $columns));
            $rows = $pdo->query("SELECT {$list} FROM `{$table}` ORDER BY {$order}")->fetchAll(PDO::FETCH_NUM);
            $tables[$table] = ['columns' => $columns, 'rows' => $rows];
        }
        return self::encode($tables);
    }

    /**
     * Loads a snapshot into empty tables, all or nothing. It refuses to touch a table that already
     * holds rows, so it can never overwrite data.
     *
     * @return array<string, int> table => rows loaded
     */
    public static function import(PDO $pdo, string $json): array
    {
        $tables = self::decode($json);
        foreach (array_keys($tables) as $table) {
            if ((int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn() > 0) {
                throw new RuntimeException("Tabel {$table} sudah berisi data, snapshot tidak dimuat.");
            }
        }

        $counts = [];
        // forms and form_versions point at each other, so rows cannot be ordered to satisfy the keys.
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        try {
            $pdo->beginTransaction();
            foreach ($tables as $table => $data) {
                $counts[$table] = count($data['rows']);
                if ($data['rows'] === []) {
                    continue;
                }
                $list = implode(', ', array_map(static fn (string $c): string => "`{$c}`", $data['columns']));
                $marks = implode(', ', array_fill(0, count($data['columns']), '?'));
                $insert = $pdo->prepare("INSERT INTO `{$table}` ({$list}) VALUES ({$marks})");
                foreach ($data['rows'] as $row) {
                    $insert->execute($row);
                }
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        } finally {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }
        return $counts;
    }

    /**
     * One row per line, so `git diff` shows exactly which rows changed.
     *
     * @param array<string, array{columns: list<string>, rows: list<list<mixed>>}> $tables
     */
    public static function encode(array $tables): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;
        $blocks = [];
        foreach ($tables as $table => $data) {
            $rows = array_map(static fn (array $row): string => '        ' . json_encode($row, $flags), $data['rows']);
            $blocks[] = '    ' . json_encode((string) $table, $flags) . ": {\n"
                . '      "columns": ' . json_encode($data['columns'], $flags) . ",\n"
                . '      "rows": [' . ($rows === [] ? ']' : "\n" . implode(",\n", $rows) . "\n      ]") . "\n    }";
        }
        return "{\n  \"format\": " . self::FORMAT . ",\n  \"tables\": {\n" . implode(",\n", $blocks) . "\n  }\n}\n";
    }

    /**
     * Validates a snapshot before any of it is used. Table names come only from the allow-list.
     *
     * @return array<string, array{columns: list<string>, rows: list<list<mixed>>}>
     */
    public static function decode(string $json): array
    {
        try {
            $document = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('File snapshot rusak (bukan JSON yang valid).');
        }
        if (!is_array($document) || ($document['format'] ?? null) !== self::FORMAT || !is_array($document['tables'] ?? null)) {
            throw new RuntimeException('Format file snapshot tidak dikenal.');
        }
        foreach ($document['tables'] as $table => $data) {
            if (!in_array($table, self::TABLES, true)) {
                throw new RuntimeException("Tabel {$table} tidak boleh ada di snapshot.");
            }
            $columns = $data['columns'] ?? null;
            if (!is_array($columns) || $columns === [] || !is_array($data['rows'] ?? null)) {
                throw new RuntimeException("Data tabel {$table} di snapshot tidak lengkap.");
            }
            foreach ($columns as $column) {
                if (!is_string($column) || preg_match('/^[A-Za-z0-9_]+$/', $column) !== 1) {
                    throw new RuntimeException("Nama kolom di tabel {$table} tidak valid.");
                }
            }
            foreach ($data['rows'] as $row) {
                if (!is_array($row) || count($row) !== count($columns)) {
                    throw new RuntimeException("Jumlah nilai pada baris tabel {$table} tidak sesuai kolom.");
                }
            }
        }
        return $document['tables'];
    }

    /** @return list<string> stored columns only; a generated column cannot be inserted into */
    private static function columns(PDO $pdo, string $table): array
    {
        $statement = $pdo->prepare(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COALESCE(GENERATION_EXPRESSION, '') = ''
             ORDER BY ORDINAL_POSITION"
        );
        $statement->execute([$table]);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<string> */
    private static function primaryKey(PDO $pdo, string $table): array
    {
        $statement = $pdo->prepare(
            "SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY'
             ORDER BY ORDINAL_POSITION"
        );
        $statement->execute([$table]);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }
}
