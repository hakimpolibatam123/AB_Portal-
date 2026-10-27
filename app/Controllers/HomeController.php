<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\DashboardData;
use App\Helpers\Schedule;
use DateTimeImmutable;

final class HomeController extends Controller
{
    public function index(): void
    {
        // Frontend only: sections and cards come from database/dummy/dashboard.php (no database).
        $data = dummy('dashboard');
        $dashboard = config('dashboard');
        $groups = $this->groups($data['sections'], $data['cards']);
        // Progress = visible Monitoring Harian forms due today, and how many of them are done.
        $due = [];
        foreach ($groups as $group) {
            if ($group['kind'] === 'forms') {
                $due = array_merge($due, array_filter($group['cards'], static fn (array $card): bool => !$card['hidden'] && $card['status'] !== null && $card['status']['due']));
            }
        }
        $target = count($due);
        $completed = count(array_filter($due, static fn (array $card): bool => $card['done_today']));
        $progress = [
            'completed' => $completed,
            'target' => $target,
            'remaining' => $target - $completed,
            'percentage' => $target > 0 ? (int) round($completed / $target * 100) : 0,
            'ratio' => $target > 0 ? $completed / $target : 0,
        ];
        $this->render('home/index', [
            'pageTitle' => 'Portal Monitoring IT',
            'groups' => $groups,
            // Card menus (forms and links) and the "Tambah" dialog.
            'editable' => true,
            'showDialogs' => true,
            'linkMenus' => true,
            // Start state of the in-browser dashboard (public/assets/js/dashboard-store.js keeps changes in sessionStorage).
            'clientState' => DashboardData::clientState($data),
            'iconNames' => DashboardData::iconNames($data),
            'pageScripts' => ['js/dashboard-store.js', 'js/dashboard.js'],
            'linkSections' => array_values(array_filter($groups, static fn (array $group): bool => $group['kind'] === 'links')),
            'newCardId' => null,
            'cardStyle' => config('dashboard-cards'),
            'progress' => $progress,
            'profile' => $dashboard['profile'],
        ], 'main');
    }

    /** @return list<array<string, mixed>> sections with their cards, in display order */
    private function groups(array $sections, array $cards): array
    {
        $now = new DateTimeImmutable('now');
        $groups = [];
        foreach ($sections as $section) {
            $groups[$section['id']] = $section + ['cards' => []];
        }
        foreach ($cards as $card) {
            if (!isset($groups[$card['section']])) {
                continue;
            }
            $groups[$card['section']]['cards'][] = [
                'id' => $card['id'],
                'section_id' => $card['section'],
                'kind' => $card['kind'],
                'builtin' => $card['kind'] === 'builtin' || !empty($card['builtin']),
                'title' => $card['title'],
                'description' => $card['description'],
                'icon' => $card['icon'],
                'color' => $card['color'],
                'image' => $card['image'] ?? null,
                'url' => $card['url'],
                'rekap_url' => $card['rekap'] ?? null,
                'hidden' => !empty($card['hidden']),
                'schedule' => $card['schedule'] ?? null,
                'done_today' => !empty($card['done_today']),
                'has_data' => !empty($card['has_data']),
                // Badge and footer status of Monitoring Harian cards (link cards have neither).
                'label' => isset($card['schedule']) ? Schedule::label($card['schedule']) : null,
                'status' => isset($card['schedule'])
                    ? Schedule::status($card['schedule'], !empty($card['done_today']), !empty($card['has_data']), $now)
                    : null,
            ];
        }
        return array_values($groups);
    }
}
