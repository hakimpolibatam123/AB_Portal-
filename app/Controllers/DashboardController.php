<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Attachment;
use App\Models\Dashboard;
use PDO;

/**
 * Changes to the dashboard: categories and link cards (M1), hiding built-in cards.
 * The "Tambah" dialog posts with fetch() and gets JSON; the card menu actions are plain form posts
 * that redirect back to "/" with a toast. Every change is checked for CSRF and written to audit_logs.
 */
final class DashboardController extends Controller
{
    private const TITLE_MAX = 40;
    private const DESCRIPTION_MAX = 60;
    private const CATEGORY_MAX = 32;
    private const URL_MAX = 500;

    /** POST /dashboard/sections: "Simpan kategori" inside the dialog. */
    public function storeSection(): void
    {
        if (!$this->csrfOk(true)) {
            return;
        }
        $pdo = Database::connection();
        $dashboard = new Dashboard($pdo);
        $title = mb_strtoupper($this->text($_POST['category_title'] ?? ''));
        $icon = (string) ($_POST['category_icon'] ?? 'folder-open');
        $error = match (true) {
            $title === '' => 'Nama kategori wajib diisi.',
            mb_strlen($title) > self::CATEGORY_MAX => 'Nama kategori maksimal ' . self::CATEGORY_MAX . ' karakter.',
            $dashboard->sectionTitleTaken($title) => 'Nama kategori sudah dipakai.',
            default => null,
        };
        if ($error !== null) {
            json_response(['errors' => ['category_title' => $error]], 422);
            return;
        }
        if (!isset(config('dashboard-cards')['icons'][$icon])) {
            $icon = 'folder-open';
        }
        $id = $dashboard->createSection($title, $icon);
        Audit::log($pdo, 'create', 'dashboard_section', $id, ['title' => $title, 'icon' => $icon]);
        json_response(['section' => ['id' => $id, 'title' => $title, 'icon' => $icon]]);
    }

    /** POST /dashboard/sections/{id}/delete: only an empty category that was added from the dashboard. */
    public function deleteSection(array $params): void
    {
        if (!$this->csrfOk(false)) {
            return;
        }
        $pdo = Database::connection();
        $dashboard = new Dashboard($pdo);
        $section = $dashboard->section((int) $params['id']);
        if ($section === null || (int) $section['is_system'] === 1 || $dashboard->sectionHasCards((int) $section['id'])) {
            Flash::set('Kategori tidak dapat dihapus', 'Hanya kategori buatan sendiri yang sudah kosong yang dapat dihapus.', 'error');
        } else {
            $dashboard->deleteSection((int) $section['id']);
            Audit::log($pdo, 'delete', 'dashboard_section', (int) $section['id'], ['title' => $section['title']]);
            Flash::set('Kategori dihapus', $section['title']);
        }
        redirect('/');
    }

    /** POST /dashboard/links: new link card from the "Kategori & Tautan" dialog. */
    public function storeLink(): void
    {
        $this->saveLink(null);
    }

    /** POST /dashboard/links/{id}: "Edit" from the card menu. */
    public function updateLink(array $params): void
    {
        $this->saveLink((int) $params['id']);
    }

    /** POST /dashboard/cards/{id}/hide and /show */
    public function hideCard(array $params): void
    {
        $this->setHidden((int) $params['id'], true);
    }

    public function showCard(array $params): void
    {
        $this->setHidden((int) $params['id'], false);
    }

    /** POST /dashboard/cards/{id}/move: "Pindah Kategori" of a link card. */
    public function moveCard(array $params): void
    {
        if (!$this->csrfOk(false)) {
            return;
        }
        $pdo = Database::connection();
        $dashboard = new Dashboard($pdo);
        $card = $dashboard->card((int) $params['id']);
        $target = $dashboard->section((int) ($_POST['section_id'] ?? 0));
        if ($card === null || $card['kind'] !== 'link' || $target === null || $target['kind'] !== 'links') {
            Flash::set('Kartu tidak dapat dipindahkan', 'Pilih kategori tautan yang tersedia.', 'error');
            redirect('/');
            return;
        }
        if ((int) $card['section_id'] !== (int) $target['id']) {
            $dashboard->moveCard((int) $card['id'], (int) $target['id']);
            $from = $dashboard->section((int) $card['section_id']);
            Audit::log($pdo, 'move', 'dashboard_card', (int) $card['id'], ['changes' => [['field' => 'section', 'old' => $from['title'] ?? null, 'new' => $target['title']]]]);
            Flash::set('Kartu dipindahkan', $card['title'] . ' sekarang di ' . $target['title'] . '.');
        }
        redirect('/?baru=' . (int) $card['id'] . '#card-' . (int) $card['id']);
    }

    /** POST /dashboard/cards/{id}/delete: link cards in M1 (soft delete; built-in cards can only be hidden). */
    public function deleteCard(array $params): void
    {
        if (!$this->csrfOk(false)) {
            return;
        }
        $pdo = Database::connection();
        $dashboard = new Dashboard($pdo);
        $card = $dashboard->card((int) $params['id']);
        if ($card === null || $card['kind'] !== 'link') {
            Flash::set('Kartu tidak dapat dihapus', 'Kartu form bawaan hanya dapat disembunyikan.', 'error');
        } else {
            $dashboard->deleteCard((int) $card['id']);
            Audit::log($pdo, 'delete', 'dashboard_card', (int) $card['id'], ['title' => $card['title'], 'url' => $card['url']]);
            Flash::set('Tautan dihapus', $card['title']);
        }
        redirect('/');
    }

    /** GET /files/{id}: an uploaded card icon, for a signed-in session only. */
    public function file(array $params): void
    {
        $attachment = (new Attachment(Database::connection()))->find((int) $params['id']);
        $path = $attachment !== null ? Attachment::path($attachment) : null;
        if ($attachment === null || $path === null || !isset(config('dashboard-cards')['image_types'][$attachment['mime_type']])) {
            http_response_code(404);
            return;
        }
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="' . rawurlencode($attachment['original_name']) . '"');
        header('Content-Security-Policy: default-src \'none\'; img-src \'self\'; sandbox');
        header('Cache-Control: private, max-age=86400');
        readfile($path);
    }

    private function setHidden(int $id, bool $hidden): void
    {
        if (!$this->csrfOk(false)) {
            return;
        }
        $pdo = Database::connection();
        $dashboard = new Dashboard($pdo);
        $card = $dashboard->card($id);
        if ($card === null) {
            Flash::set('Kartu tidak ditemukan', 'Muat ulang dashboard lalu coba lagi.', 'error');
            redirect('/');
            return;
        }
        if ((bool) $card['is_hidden'] !== $hidden) {
            $dashboard->setHidden($id, $hidden);
            Audit::log($pdo, $hidden ? 'hide' : 'show', 'dashboard_card', $id, ['title' => $card['title']]);
        }
        Flash::set(
            $hidden ? 'Kartu disembunyikan' : 'Kartu ditampilkan lagi',
            $hidden ? 'Tampilkan lagi lewat tombol "tersembunyi" di judul bagian.' : $card['title']
        );
        redirect('/' . ($hidden ? '' : '?baru=' . $id . '#card-' . $id));
    }

    private function saveLink(?int $id): void
    {
        if (!$this->csrfOk(true)) {
            return;
        }
        $pdo = Database::connection();
        $dashboard = new Dashboard($pdo);
        $current = $id !== null ? $dashboard->card($id) : null;
        if ($id !== null && ($current === null || $current['kind'] !== 'link')) {
            json_response(['message' => 'Tautan tidak ditemukan. Muat ulang dashboard.'], 404);
            return;
        }
        [$data, $image, $errors] = $this->linkInput($dashboard, $current);
        if ($errors !== []) {
            json_response(['errors' => $errors], 422);
            return;
        }
        $pdo->beginTransaction();
        try {
            if ($image !== null) {
                $data['icon_attachment_id'] = (new Attachment($pdo))->store($image, 'dashboard_card');
            }
            if ($current === null) {
                $id = $dashboard->createLink($data);
                Audit::log($pdo, 'create', 'dashboard_card', $id, ['kind' => 'link', 'title' => $data['title'], 'url' => $data['url'], 'section_id' => $data['section_id']]);
            } else {
                $dashboard->updateLink($id, $data, (int) $current['section_id'] !== $data['section_id']);
                $changes = Audit::changes($current, $data, ['section_id', 'title', 'description', 'url', 'icon', 'icon_attachment_id']);
                if ($changes !== []) {
                    Audit::log($pdo, 'update', 'dashboard_card', $id, ['changes' => $changes]);
                }
            }
            if ($image !== null) {
                (new Attachment($pdo))->attachTo($data['icon_attachment_id'], $id);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
        $section = $dashboard->section($data['section_id']);
        if ($current === null) {
            Flash::set('Kartu berhasil ditambahkan', $data['title'] . ' tampil di ' . ($section['title'] ?? 'dashboard') . '.');
        } else {
            Flash::set('Tautan diperbarui', $data['title']);
        }
        json_response(['redirect' => url('/?baru=' . $id . '#card-' . $id)]);
    }

    /**
     * Validated fields of the link dialog.
     * @return array{0: array{section_id: int, title: string, description: string, url: string, icon: string, icon_attachment_id: ?int}, 1: ?array, 2: array<string, string>}
     */
    private function linkInput(Dashboard $dashboard, ?array $current): array
    {
        $style = config('dashboard-cards');
        $errors = [];
        $sectionValue = (string) ($_POST['section_id'] ?? '');
        $section = ctype_digit($sectionValue) ? $dashboard->section((int) $sectionValue) : null;
        if ($sectionValue === '__new') {
            $errors['section_id'] = 'Isi Nama Kategori lalu klik Simpan kategori.';
        } elseif ($section === null || $section['kind'] !== 'links') {
            $errors['section_id'] = 'Pilih kategori.';
        }
        $title = $this->text($_POST['title'] ?? '');
        if ($title === '') {
            $errors['title'] = 'Judul wajib diisi.';
        } elseif (mb_strlen($title) > self::TITLE_MAX) {
            $errors['title'] = 'Judul maksimal ' . self::TITLE_MAX . ' karakter.';
        }
        $description = $this->text($_POST['description'] ?? '');
        if (mb_strlen($description) > self::DESCRIPTION_MAX) {
            $errors['description'] = 'Deskripsi maksimal ' . self::DESCRIPTION_MAX . ' karakter.';
        }
        $url = trim((string) ($_POST['url'] ?? ''));
        if (!$this->validUrl($url)) {
            $errors['url'] = 'Gunakan alamat lengkap yang diawali http:// atau https://';
        }
        $source = ($_POST['icon_source'] ?? 'list') === 'image' ? 'image' : 'list';
        $icon = (string) ($_POST['icon'] ?? '');
        if (!isset($style['icons'][$icon])) {
            if ($source === 'list') {
                $errors['icon'] = 'Pilih icon dari daftar.';
            }
            $icon = $current['icon'] ?? 'link';
        }
        $attachmentId = null;
        $image = null;
        if ($source === 'image') {
            $upload = $_FILES['icon_file'] ?? null;
            if (is_array($upload) && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                [$image, $imageError] = Attachment::checkImage($upload, $style['image_types'], $style['image_max_bytes']);
                if ($imageError !== null) {
                    $errors['icon_file'] = $imageError;
                }
            } elseif (!empty($current['icon_attachment_id'])) {
                $attachmentId = (int) $current['icon_attachment_id'];
            } else {
                $errors['icon_file'] = 'Pilih gambar PNG, JPG, atau WebP.';
            }
        }
        $data = [
            'section_id' => (int) ($section['id'] ?? 0),
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'icon' => $icon,
            'icon_attachment_id' => $attachmentId,
        ];
        return [$data, $errors === [] ? $image : null, $errors];
    }

    private function validUrl(string $url): bool
    {
        if ($url === '' || strlen($url) > self::URL_MAX || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        return in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) && ($parts['host'] ?? '') !== '';
    }

    /** One line of text: control characters removed, spaces collapsed. */
    private function text(mixed $value): string
    {
        $value = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', is_string($value) ? $value : '');
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    private function csrfOk(bool $json): bool
    {
        if (Auth::verifyCsrf($_POST['_token'] ?? null)) {
            return true;
        }
        if ($json) {
            json_response(['message' => 'Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.'], 419);
        } else {
            Flash::set('Perubahan tidak disimpan', 'Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.', 'error');
            redirect('/');
        }
        return false;
    }
}
