<?php
declare(strict_types=1);

namespace App\Controllers;

final class HomeController extends Controller
{
    public function index(): void
    {
        $portal = config('portal');
        $sections = [];
        foreach ($portal['sections'] as $key => $section) {
            $sections[$key] = $section + ['cards' => array_values(array_filter($portal['cards'], static fn (array $card): bool => $card['section'] === $key))];
        }
        $initialProgress = config('dashboard')['daily_progress'];
        $target = max(0, (int) $initialProgress['target']);
        $completed = min($target, max(0, (int) $initialProgress['completed']));
        $ratio = $target > 0 ? $completed / $target : 0;
        $this->render('home/index', [
            'pageTitle' => 'Portal Monitoring Terpadu',
            'page' => 'portal',
            'bodyClass' => 'min-h-screen flex flex-col antialiased rs-forms',
            'brandSubtitle' => 'IT Division',
            'sections' => $sections,
            'progress' => [
                'completed' => $completed,
                'target' => $target,
                'remaining' => $target - $completed,
                'percentage' => (int) round($ratio * 100),
                'ratio' => $ratio,
            ],
            'footer' => ['class' => 'text-center text-xs py-8'],
        ]);
    }
}
