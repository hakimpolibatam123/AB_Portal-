<?php
declare(strict_types=1);

namespace App\Core;

/** Route middleware: visitors who are not signed in are sent to /login (302). */
final class RequireLogin
{
    public function __invoke(string $method, string $path): bool
    {
        if (Auth::check()) {
            return true;
        }
        // A fetch() from an open page (e.g. a dashboard dialog) gets a status it can read, not a login page.
        if (str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            json_response(['message' => 'Sesi berakhir. Muat ulang halaman lalu masuk kembali.'], 401);
            return false;
        }
        if ($method === 'GET') {
            Auth::rememberIntended($path . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }
        redirect('/login', 302);
        return false;
    }
}
