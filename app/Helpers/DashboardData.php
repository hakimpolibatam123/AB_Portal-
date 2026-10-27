<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * The dashboard's dummy data (database/dummy/dashboard.php) as the browser receives it: the start
 * state of public/assets/js/dashboard-store.js and the icons the scripts may draw. Used by the
 * dashboard and the form editor page.
 */
final class DashboardData
{
    /** Dummy sections, cards and form definitions, with internal paths turned into URLs. */
    public static function clientState(array $data): array
    {
        $link = static fn (?string $path): ?string => $path === null ? null : url($path);
        return [
            'sections' => $data['sections'],
            'cards' => array_map(static fn (array $card): array => [
                'id' => $card['id'],
                'section' => $card['section'],
                'kind' => $card['kind'],
                'builtin' => $card['kind'] === 'builtin' || !empty($card['builtin']),
                'title' => $card['title'],
                'description' => $card['description'],
                'icon' => $card['icon'],
                'color' => $card['color'],
                'image' => $card['image'] ?? null,
                'url' => $card['kind'] === 'link' ? $card['url'] : $link($card['url']),
                'rekap' => $link($card['rekap'] ?? null),
                'hidden' => !empty($card['hidden']),
                'schedule' => $card['schedule'] ?? null,
                'done_today' => !empty($card['done_today']),
                'has_data' => !empty($card['has_data']),
            ], $data['cards']),
            'forms' => $data['forms'],
            // Answers sent from the dynamic fill-in page, per form id (an object in JSON).
            'responses' => new \stdClass(),
        ];
    }

    /** Every icon the browser may draw: the picker icons, the dummy data's icons and the UI icons. */
    public static function iconNames(array $data, array $extra = []): array
    {
        $style = config('dashboard-cards');
        return array_values(array_unique(array_merge(
            array_keys($style['icons']),
            $style['category_icons'],
            array_column($data['sections'], 'icon'),
            array_column($data['cards'], 'icon'),
            ['ellipsis', 'chart-column', 'check', 'eye', 'eye-off', 'plus', 'trash-2', 'link', 'clipboard-list', 'pencil', 'folder-input', 'image', 'triangle-alert', 'copy', 'file-pen-line', 'calendar-days'],
            $extra,
        )));
    }
}
