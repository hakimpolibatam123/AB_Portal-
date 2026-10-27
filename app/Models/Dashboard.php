<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/** Dashboard sections (categories) and their cards. Deleted rows keep deleted_at and are never shown. */
final class Dashboard
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function sections(): array
    {
        return $this->pdo->query(
            'SELECT id, slug, title, icon, kind, is_system FROM dashboard_sections WHERE deleted_at IS NULL ORDER BY sort_order, id'
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function cards(): array
    {
        return $this->pdo->query(
            'SELECT id, section_id, kind, builtin_key, title, description, icon, color, icon_attachment_id, url, rekap_url, is_hidden
             FROM dashboard_cards WHERE deleted_at IS NULL ORDER BY sort_order, id'
        )->fetchAll();
    }

    public function section(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, slug, title, icon, kind, is_system FROM dashboard_sections WHERE id = ? AND deleted_at IS NULL');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function card(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, section_id, kind, builtin_key, title, description, icon, color, icon_attachment_id, url, rekap_url, is_hidden
             FROM dashboard_cards WHERE id = ? AND deleted_at IS NULL'
        );
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function sectionTitleTaken(string $title): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM dashboard_sections WHERE UPPER(title) = UPPER(?) AND deleted_at IS NULL');
        $statement->execute([$title]);
        return $statement->fetchColumn() !== false;
    }

    public function sectionHasCards(int $sectionId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM dashboard_cards WHERE section_id = ? AND deleted_at IS NULL LIMIT 1');
        $statement->execute([$sectionId]);
        return $statement->fetchColumn() !== false;
    }

    /** A new category always holds link cards and is placed after the existing sections. */
    public function createSection(string $title, string $icon): int
    {
        $order = (int) $this->pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM dashboard_sections')->fetchColumn() + 10;
        $this->pdo->prepare('INSERT INTO dashboard_sections (slug, title, icon, kind, is_system, sort_order) VALUES (?, ?, ?, \'links\', 0, ?)')
            ->execute(['c-' . bin2hex(random_bytes(6)), $title, $icon, $order]);
        return (int) $this->pdo->lastInsertId();
    }

    public function deleteSection(int $id): void
    {
        $this->pdo->prepare('UPDATE dashboard_sections SET deleted_at = NOW() WHERE id = ? AND is_system = 0 AND deleted_at IS NULL')->execute([$id]);
    }

    /** @param array{section_id: int, title: string, description: string, url: string, icon: string, icon_attachment_id: ?int} $data */
    public function createLink(array $data): int
    {
        $this->pdo->prepare(
            'INSERT INTO dashboard_cards (section_id, kind, title, description, icon, color, icon_attachment_id, url, sort_order)
             VALUES (?, \'link\', ?, ?, ?, \'teal\', ?, ?, ?)'
        )->execute([$data['section_id'], $data['title'], $data['description'], $data['icon'], $data['icon_attachment_id'], $data['url'], $this->nextOrder($data['section_id'])]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Moving to another section puts the card at the end of that section. */
    public function updateLink(int $id, array $data, bool $sectionChanged): void
    {
        $this->pdo->prepare(
            'UPDATE dashboard_cards SET section_id = ?, title = ?, description = ?, icon = ?, icon_attachment_id = ?, url = ?' . ($sectionChanged ? ', sort_order = ?' : '') . '
             WHERE id = ? AND kind = \'link\' AND deleted_at IS NULL'
        )->execute(array_merge(
            [$data['section_id'], $data['title'], $data['description'], $data['icon'], $data['icon_attachment_id'], $data['url']],
            $sectionChanged ? [$this->nextOrder($data['section_id'])] : [],
            [$id]
        ));
    }

    public function moveCard(int $id, int $sectionId): void
    {
        $this->pdo->prepare('UPDATE dashboard_cards SET section_id = ?, sort_order = ? WHERE id = ? AND deleted_at IS NULL')
            ->execute([$sectionId, $this->nextOrder($sectionId), $id]);
    }

    public function setHidden(int $id, bool $hidden): void
    {
        $this->pdo->prepare('UPDATE dashboard_cards SET is_hidden = ? WHERE id = ? AND deleted_at IS NULL')->execute([$hidden ? 1 : 0, $id]);
    }

    public function deleteCard(int $id): void
    {
        $this->pdo->prepare('UPDATE dashboard_cards SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL')->execute([$id]);
    }

    private function nextOrder(int $sectionId): int
    {
        $statement = $this->pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM dashboard_cards WHERE section_id = ?');
        $statement->execute([$sectionId]);
        return (int) $statement->fetchColumn() + 10;
    }
}
