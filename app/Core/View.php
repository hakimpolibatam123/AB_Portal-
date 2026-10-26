<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $__template, array $__data = [], string $__layout = 'main'): void
    {
        // Prefixed names so view data keys such as "data" or "template" are not shadowed by the arguments.
        extract($__data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . '/app/Views/' . $__template . '.php';
        $content = ob_get_clean();
        require BASE_PATH . '/app/Views/layouts/' . $__layout . '.php';
    }
}
