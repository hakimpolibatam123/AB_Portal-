<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Append-only change log (audit_logs). Every data change writes one row; the application has no
 * update or delete path for this table. $meta holds e.g. ['changes' => [['field', 'old', 'new']]].
 */
final class Audit
{
    public static function log(PDO $pdo, string $action, string $entityType, ?int $entityId, array $meta = []): void
    {
        $user = Auth::user();
        $userId = isset($user['id']) && is_int($user['id']) ? $user['id'] : null;
        if (!empty($user['dev_bypass'])) {
            $meta['dev_bypass_user'] = (string) ($user['name'] ?? '');
        }
        $pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip, user_agent, meta_json) VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $userId,
            $action,
            $entityType,
            $entityId,
            substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
            $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
    }

    /** Field changes between two rows, for the 'changes' entry of $meta. */
    public static function changes(array $old, array $new, array $fields): array
    {
        $changes = [];
        foreach ($fields as $field) {
            if (($old[$field] ?? null) != ($new[$field] ?? null)) {
                $changes[] = ['field' => $field, 'old' => $old[$field] ?? null, 'new' => $new[$field] ?? null];
            }
        }
        return $changes;
    }
}
