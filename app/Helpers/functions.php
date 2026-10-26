<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $name): array
{
    static $loaded = [];
    return $loaded[$name] ??= require BASE_PATH . '/config/' . $name . '.php';
}

/** Dummy records used until the module tables are connected (database/dummy/*.php). */
function dummy(string $name): array
{
    static $loaded = [];
    return $loaded[$name] ??= require BASE_PATH . '/database/dummy/' . $name . '.php';
}

function asset(string $path): string
{
    return config('app')['url'] . '/assets/' . ltrim($path, '/');
}

/** Asset URL with a file-modification query string so browsers pick up rebuilt files. */
function asset_v(string $path): string
{
    $file = BASE_PATH . '/public/assets/' . ltrim($path, '/');
    return asset($path) . '?v=' . (is_file($file) ? filemtime($file) : '1');
}

function url(string $path = '/'): string
{
    return config('app')['url'] . '/' . ltrim($path, '/');
}

function redirect(string $path, int $status = 303): void
{
    header('Location: ' . url($path), true, $status);
}

/** Join class names, skipping empty values. */
function cx(string ...$classes): string
{
    return implode(' ', array_filter(array_map('trim', $classes), static fn (string $class): bool => $class !== ''));
}

/** Render HTML attributes; true renders a bare attribute, null/false skips it. */
function attrs(array $attributes): string
{
    $html = '';
    foreach ($attributes as $name => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        $html .= $value === true ? ' ' . e($name) : ' ' . e($name) . '="' . e($value) . '"';
    }
    return $html;
}

/** Render a component from app/Views/components and return its HTML. */
function partial(string $__component, array $__data = []): string
{
    // Prefixed names so data keys such as "name" or "data" are not shadowed by the arguments.
    extract($__data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/app/Views/components/' . $__component . '.php';
    return (string) ob_get_clean();
}

function date_wib(): string
{
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $days[(int) date('w')] . ', ' . date('j') . ' ' . $months[(int) date('n')] . ' ' . date('Y');
}

/** Navbar clock text, e.g. "Kam 15 Okt 10:18:45" (WIB). public/assets/js/app.js keeps it ticking. */
function clock_wib(): string
{
    $days = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    $months = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return $days[(int) date('w')] . ' ' . date('d') . ' ' . $months[(int) date('n')] . ' ' . date('H:i:s');
}

function icon(string $name, string $class = '', array $attributes = []): string
{
    static $paths = null;
    $paths ??= require BASE_PATH . '/app/Helpers/icons.php';
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . ($paths[$name] ?? $paths['info']) . '</svg>';
    return '<span' . attrs(['class' => cx('rs-ic', $class), 'aria-hidden' => 'true'] + $attributes) . '>' . $svg . '</span>';
}
