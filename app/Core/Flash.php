<?php
declare(strict_types=1);

namespace App\Core;

/** One toast message kept in the session for the next page load (after a POST → redirect). */
final class Flash
{
    private const KEY = 'flash.toast';

    /** @param 'success'|'info'|'error' $variant */
    public static function set(string $title, string $description = '', string $variant = 'success'): void
    {
        $_SESSION[self::KEY] = ['title' => $title, 'description' => $description, 'variant' => $variant];
    }

    /** @return array{title: string, description: string, variant: string}|null */
    public static function pull(): ?array
    {
        $flash = $_SESSION[self::KEY] ?? null;
        unset($_SESSION[self::KEY]);
        return is_array($flash) ? $flash : null;
    }
}
