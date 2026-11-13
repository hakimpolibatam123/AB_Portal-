<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/** The clearances table: one Form Clearance per row, the record as JSON (App\Services\Clearance). */
final class ClearanceRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array> newest first */
    public function all(): array
    {
        $rows = $this->pdo->query('SELECT data_json FROM clearances ORDER BY updated_at DESC, id DESC')->fetchAll(PDO::FETCH_COLUMN);
        return array_values(array_filter(array_map(static fn ($json) => json_decode((string) $json, true), $rows), 'is_array'));
    }

    public function find(string $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT data_json FROM clearances WHERE id = ?');
        $statement->execute([$id]);
        $json = $statement->fetchColumn();
        $record = $json !== false ? json_decode((string) $json, true) : null;
        return is_array($record) ? $record : null;
    }

    /** @return list<string> */
    public function ids(): array
    {
        return $this->pdo->query('SELECT id FROM clearances')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Removes one clearance; false when there was none with this id. */
    public function delete(string $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM clearances WHERE id = ?');
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }

    public function save(array $record): void
    {
        $this->pdo->prepare('INSERT INTO clearances (id, data_json, created_at, updated_at) VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE data_json = VALUES(data_json), updated_at = VALUES(updated_at)')
            ->execute([$record['id'], json_encode($record, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), $record['createdAt'], $record['updatedAt']]);
    }
}
