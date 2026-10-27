<?php
declare(strict_types=1);

namespace App\Core;

/** Session-backed sign-in state, CSRF token and the page to return to after login. */
final class Auth
{
    private const USER = 'auth.user';
    private const LAST_SEEN = 'auth.last_seen';
    private const INTENDED = 'auth.intended';
    private const CSRF = 'csrf_token';
    private const IDLE_SECONDS = 1800;

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_save_path(BASE_PATH . '/storage/sessions');
        session_name('abportal_session');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
        // Sign out after 30 idle minutes.
        if (isset($_SESSION[self::USER]) && time() - (int) ($_SESSION[self::LAST_SEEN] ?? 0) > self::IDLE_SECONDS) {
            unset($_SESSION[self::USER]);
        }
        if (isset($_SESSION[self::USER])) {
            $_SESSION[self::LAST_SEEN] = time();
        }
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::USER]);
    }

    /** @return array{id: ?int, name: string, email: string, dev_bypass?: bool}|null */
    public static function user(): ?array
    {
        $user = $_SESSION[self::USER] ?? null;
        return is_array($user) ? $user : null;
    }

    /**
     * Development-only free login, on only when APP_ENV=local AND AUTH_DEV_BYPASS=true.
     * TODO(auth): HAPUS/NONAKTIFKAN sebelum dipakai di server RS.
     */
    public static function devBypassEnabled(): bool
    {
        return getenv('APP_ENV') === 'local' && strtolower(trim((string) getenv('AUTH_DEV_BYPASS'))) === 'true';
    }

    /** @param array{id: ?int, name: string, email: string, dev_bypass?: bool} $user */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION[self::USER] = $user + ['logged_in_at' => time()];
        $_SESSION[self::LAST_SEEN] = time();
        unset($_SESSION[self::CSRF]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600] + array_intersect_key($params, array_flip(['path', 'domain', 'secure', 'httponly', 'samesite'])));
        session_destroy();
    }

    public static function rememberIntended(string $path): void
    {
        $_SESSION[self::INTENDED] = $path;
    }

    /** The page the visitor asked for before being sent to /login (local paths only). */
    public static function pullIntended(string $default = '/'): string
    {
        $path = (string) ($_SESSION[self::INTENDED] ?? '');
        unset($_SESSION[self::INTENDED]);
        return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : $default;
    }

    public static function csrfToken(): string
    {
        return $_SESSION[self::CSRF] ??= bin2hex(random_bytes(32));
    }

    public static function verifyCsrf(mixed $token): bool
    {
        return is_string($token) && isset($_SESSION[self::CSRF]) && hash_equals($_SESSION[self::CSRF], $token);
    }
}
