<?php
declare(strict_types=1);

// M1: copy the sections and cards of config/portal.php into the dashboard tables, once.
// Internal cards become 'builtin' cards that keep pointing at their existing module routes.

return static function (PDO $pdo): void {
    if ((int) $pdo->query('SELECT COUNT(*) FROM dashboard_sections')->fetchColumn() > 0) {
        echo '  [lewati] dashboard_sections sudah berisi data' . PHP_EOL;
        return;
    }
    $portal = config('portal');
    $legacy = config('dashboard-cards')['legacy_icons'];
    $iconName = static fn (string $name): string => $legacy[$name] ?? $name;

    $insertSection = $pdo->prepare('INSERT INTO dashboard_sections (slug, title, icon, kind, is_system, sort_order) VALUES (?, ?, ?, ?, 1, ?)');
    $insertCard = $pdo->prepare(
        'INSERT INTO dashboard_cards (section_id, kind, builtin_key, title, description, icon, color, url, rekap_url, is_hidden, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $pdo->beginTransaction();
    try {
        $sectionIds = [];
        $order = 0;
        foreach ($portal['categories'] as $slug => $category) {
            $order += 10;
            $kind = $slug === 'daily' ? 'forms' : 'links';
            $insertSection->execute([$slug, $category['title'], $iconName($category['icon']), $kind, $order]);
            $sectionIds[$slug] = (int) $pdo->lastInsertId();
        }
        $cards = $portal['cards'];
        usort($cards, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);
        foreach ($cards as $card) {
            $builtin = $card['type'] === 'internal';
            $insertCard->execute([
                $sectionIds[$card['category']],
                $builtin ? 'builtin' : 'link',
                $builtin ? $card['key'] : null,
                $card['title'],
                $card['description'],
                $iconName($card['icon']),
                $card['tone'],
                $card['target'],
                $builtin ? ($card['rekap'] ?? null) : null,
                $card['enabled'] ? 0 : 1,
                $card['sort_order'],
            ]);
        }
        $pdo->commit();
        echo '  [ok] ' . count($sectionIds) . ' kategori dan ' . count($cards) . ' kartu disalin dari config/portal.php' . PHP_EOL;
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
};
