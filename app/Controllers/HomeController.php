<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Flash;
use App\Models\Dashboard;
use PDOException;
use RuntimeException;

final class HomeController extends Controller
{
    public function index(): void
    {
        // Sections and cards come from the database; without it (not migrated yet) the dashboard
        // shows config/portal.php read-only, so the portal stays usable.
        $editable = true;
        try {
            $dashboard = new Dashboard(Database::connection());
            $groups = $this->groups($dashboard->sections(), $dashboard->cards());
        } catch (PDOException | RuntimeException $exception) {
            error_log('Dashboard dari konfigurasi, database belum siap: ' . $exception->getMessage());
            $editable = false;
            $groups = $this->configGroups();
        }
        $dashboard = config('dashboard');
        $initialProgress = $dashboard['daily_progress'];
        $target = max(0, (int) $initialProgress['target']);
        $completed = min($target, max(0, (int) $initialProgress['completed']));
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
            'editable' => $editable,
            'linkSections' => array_values(array_filter($groups, static fn (array $group): bool => $group['kind'] === 'links')),
            'newCardId' => ctype_digit((string) ($_GET['baru'] ?? '')) ? (int) $_GET['baru'] : null,
            'flash' => Flash::pull(),
            'cardStyle' => config('dashboard-cards'),
            'progress' => $progress,
            'profile' => $dashboard['profile'],
            'pageScripts' => $editable ? ['js/dashboard.js'] : [],
        ], 'main');
    }

    /** @return list<array<string, mixed>> sections with their cards, in display order */
    private function groups(array $sections, array $cards): array
    {
        $groups = [];
        foreach ($sections as $section) {
            $groups[(int) $section['id']] = [
                'id' => (int) $section['id'],
                'slug' => $section['slug'],
                'title' => $section['title'],
                'icon' => $section['icon'],
                'kind' => $section['kind'],
                'custom' => (int) $section['is_system'] === 0,
                'cards' => [],
            ];
        }
        foreach ($cards as $card) {
            if (!isset($groups[(int) $card['section_id']])) {
                continue;
            }
            $groups[(int) $card['section_id']]['cards'][] = [
                'id' => (int) $card['id'],
                'section_id' => (int) $card['section_id'],
                'kind' => $card['kind'],
                'title' => $card['title'],
                'description' => $card['description'],
                'icon' => $card['icon'],
                'color' => $card['color'],
                'image' => $card['icon_attachment_id'] !== null ? url('/files/' . (int) $card['icon_attachment_id']) : null,
                'url' => $card['url'],
                'rekap_url' => $card['rekap_url'],
                'hidden' => (bool) $card['is_hidden'],
            ];
        }
        return array_values($groups);
    }

    /** Read-only dashboard from config/portal.php (the seed of the database tables). */
    private function configGroups(): array
    {
        $portal = config('portal');
        $legacy = config('dashboard-cards')['legacy_icons'];
        $cards = array_values(array_filter($portal['cards'], static fn (array $card): bool => $card['enabled']));
        usort($cards, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);
        $groups = [];
        foreach ($portal['categories'] as $key => $category) {
            $groups[] = [
                'id' => null,
                'slug' => $key,
                'title' => $category['title'],
                'icon' => $legacy[$category['icon']] ?? $category['icon'],
                'kind' => $key === 'daily' ? 'forms' : 'links',
                'custom' => false,
                'cards' => array_values(array_map(static fn (array $card): array => [
                    'id' => null,
                    'section_id' => null,
                    'kind' => $card['type'] === 'internal' ? 'builtin' : 'link',
                    'title' => $card['title'],
                    'description' => $card['description'],
                    'icon' => $legacy[$card['icon']] ?? $card['icon'],
                    'color' => $card['tone'],
                    'image' => null,
                    'url' => $card['target'],
                    'rekap_url' => $card['rekap'] ?? null,
                    'hidden' => false,
                ], array_filter($cards, static fn (array $card): bool => $card['category'] === $key))),
            ];
        }
        return $groups;
    }
}
