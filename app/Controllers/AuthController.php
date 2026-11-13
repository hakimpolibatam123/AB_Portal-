<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Helpers\DashboardData;
use App\Models\User;
use App\Services\LoginThrottle;
use Throwable;

/**
 * Login and logout (PRD SEC-1 to SEC-4, SEC-12). The CSRF token is checked by App\Core\VerifyCsrf
 * before these actions run. Unknown username and wrong password give the same message.
 */
final class AuthController extends Controller
{
    private const WRONG = 'Username atau password salah.';

    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/', 302);
            return;
        }
        $flash = Flash::pull();
        $this->renderLogin('', $flash !== null && $flash['variant'] === 'error' ? $flash['description'] : null, $flash !== null && $flash['variant'] !== 'error' ? $flash : null);
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            http_response_code(422);
            $this->renderLogin($username, 'Username dan password wajib diisi.');
            return;
        }
        if (mb_strlen($username) > 100 || strlen($password) > 1024) {
            http_response_code(422);
            $this->renderLogin(mb_substr($username, 0, 100), self::WRONG);
            return;
        }
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        try {
            $pdo = Database::connection();
            $throttle = new LoginThrottle($pdo);
            $wait = $throttle->lockedFor($username, $ip, time());
            if ($wait > 0) {
                Audit::tryLog('login_locked', 'user', null, ['username' => $username]);
                http_response_code(429);
                header('Retry-After: ' . $wait);
                $this->renderLogin($username, 'Terlalu banyak percobaan. Coba lagi dalam ' . max(1, (int) ceil($wait / 60)) . ' menit.');
                return;
            }
            $users = new User($pdo);
            $account = $users->findByUsername($username);
            // A dummy hash keeps the timing of unknown usernames close to that of real ones.
            $hash = $account['password_hash'] ?? '$2y$12$qOFZUH0LWWA8zl1IkMtSB.rHs7dp./m6ZqaorQk5W1.3v7mMILbBy';
            $valid = password_verify($password, $hash) && $account !== null && (int) $account['is_active'] === 1;
            $throttle->record($username, $ip, $valid);
            if (!$valid) {
                Audit::tryLog('login_failed', 'user', $account !== null ? (int) $account['id'] : null, ['username' => $username]);
                http_response_code(422);
                $this->renderLogin($username, self::WRONG);
                return;
            }
            $users->recordLogin((int) $account['id'], User::needsRehash($account['password_hash']) ? User::hash($password) : null);
        } catch (Throwable $exception) {
            error_log((string) $exception);
            http_response_code(503);
            $this->renderLogin($username, 'Login belum dapat diproses. Periksa koneksi database.');
            return;
        }

        Auth::login([
            'id' => (int) $account['id'],
            'name' => (string) $account['name'],
            'username' => (string) $account['username'],
            'email' => (string) $account['email'],
            'session_version' => (int) $account['session_version'],
        ]);
        Audit::tryLog('login', 'user', (int) $account['id']);
        // Every sign-in lands on the Form dashboard, whichever page sent the visitor to /login.
        redirect('/', 302);
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user !== null) {
            Audit::tryLog('logout', 'user', $user['id']);
        }
        Auth::logout();
        redirect('/login', 302);
    }

    private function renderLogin(string $username = '', ?string $error = null, ?array $notice = null): void
    {
        $this->render('auth/login', [
            'pageTitle' => 'Masuk',
            'username' => $username,
            'error' => $error,
            'notice' => $notice,
            'linkSections' => $this->publicLinks(),
        ], 'auth');
    }

    /**
     * The link sections of the dashboard (Monitoring & Laporan, Sistem), shown read only under the login form.
     * Only link cards that are not hidden; forms need a session. [] when the database cannot be read,
     * so the login form still shows.
     *
     * @return list<array{id: mixed, title: string, icon: string, cards: list<array<string, mixed>>}>
     */
    private function publicLinks(): array
    {
        try {
            $data = DashboardData::load();
        } catch (Throwable $exception) {
            error_log('Login links: ' . $exception->getMessage());
            return [];
        }
        $sections = [];
        foreach ($data['sections'] as $section) {
            if (($section['kind'] ?? '') === 'links') {
                $sections[$section['id']] = ['id' => $section['id'], 'title' => (string) $section['title'], 'icon' => (string) $section['icon'], 'cards' => []];
            }
        }
        foreach ($data['cards'] as $card) {
            if ($card['kind'] !== 'link' || !empty($card['hidden']) || !isset($sections[$card['section']])) {
                continue;
            }
            $image = $card['image'] ?? null;
            // An uploaded icon is a /berkas file, readable only when signed in: the card shows its icon instead.
            if (!is_string($image) || $image === '' || str_contains($image, '/berkas/') || preg_match('#^https?://#i', $image) !== 1) {
                $image = null;
            }
            $sections[$card['section']]['cards'][] = [
                'title' => (string) $card['title'],
                'description' => (string) ($card['description'] ?? ''),
                'icon' => (string) $card['icon'],
                'color' => (string) $card['color'],
                'image' => $image,
                'url' => is_string($card['url']) && preg_match('#^https?://#i', $card['url']) === 1 ? $card['url'] : null,
            ];
        }
        return array_values(array_filter($sections, static fn (array $section): bool => $section['cards'] !== []));
    }
}
