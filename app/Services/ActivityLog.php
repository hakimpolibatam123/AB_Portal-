<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Format;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Riwayat aktivitas (PRD SEC-12): the filters of the page and every change-log row in words. Query parameters:
 *   dari=YYYY-MM-DD, sampai=YYYY-MM-DD (WIB days, either alone) · aksi=<group> · objek=<entity type> · q=<words> · halaman=<n>
 */
final class ActivityLog
{
    public const PER_PAGE = 50;

    /** Action filter: label and the actions it covers. */
    public const GROUPS = [
        'create' => ['Dibuat', ['create', 'admin_create']],
        'update' => ['Diubah', ['update', 'move', 'hide', 'show', 'duplicate']],
        'delete' => ['Dihapus', ['delete']],
        'export' => ['Diekspor', ['export']],
        'login' => ['Masuk dan keluar', ['login', 'logout']],
        'login_failed' => ['Gagal masuk', ['login_failed', 'login_locked']],
        'password' => ['Ganti password', ['password_change', 'password_change_failed']],
    ];

    public const ACTIONS = [
        'create' => 'Dibuat', 'admin_create' => 'Akun dibuat', 'update' => 'Diubah', 'delete' => 'Dihapus', 'export' => 'Diekspor',
        'move' => 'Dipindah', 'hide' => 'Disembunyikan', 'show' => 'Ditampilkan', 'duplicate' => 'Diduplikat',
        'login' => 'Masuk', 'logout' => 'Keluar', 'login_failed' => 'Gagal masuk', 'login_locked' => 'Login dikunci',
        'password_change' => 'Ganti password', 'password_change_failed' => 'Ganti password gagal',
        'mail_test' => 'Tes email', 'mail_test_failed' => 'Tes email gagal',
    ];
    /** Fields of the "Pengaturan Email" page (the password is only ever named, never shown). */
    private const MAIL_FIELDS = ['host' => 'Server SMTP', 'port' => 'Port', 'encryption' => 'Keamanan', 'username' => 'Username',
        'password' => 'Password', 'from_address' => 'Email pengirim', 'from_name' => 'Nama pengirim', 'alert_to' => 'Penerima pengingat'];

    /** Objects of the filter; the former Suhu/CCTV/Clear Desk modules only name old rows. */
    public const ENTITIES = [
        'form_entry' => 'Laporan form', 'form' => 'Edit Isi Form', 'dashboard_card' => 'Kartu dashboard',
        'dashboard_section' => 'Bagian dashboard', 'rekap' => 'Ekspor rekap', 'user' => 'Akun admin', 'asset' => 'Aset IT', 'clearance' => 'Form Clearance', 'mail_settings' => 'Pengaturan email',
    ];
    private const OLD_ENTITIES = ['temperature_entry' => 'Suhu server', 'cctv_entry' => 'CCTV', 'cleardesk_session' => 'Clear Desk'];

    private const TONES = ['mail_test_failed' => 'bad', 'mail_test' => 'ok', 'delete' => 'warn', 'login_failed' => 'bad', 'login_locked' => 'bad', 'password_change_failed' => 'bad', 'export' => 'ok'];
    private const FORMATS = ['xlsx' => 'Excel', 'pdf' => 'PDF', 'cetak' => 'Cetak'];
    private const MODULES = ['rekap-suhu' => 'Suhu server', 'rekap-cctv' => 'CCTV', 'rekap-cleardesk' => 'Clear Desk'];
    /** Labels of dashboard, editor and account fields; module fields come from their controllers. */
    private const LABELS = [
        'title' => 'Judul', 'url' => 'Alamat', 'icon' => 'Ikon', 'icon_attachment_id' => 'Ikon (unggahan)', 'icon_url' => 'Ikon (alamat)', 'image_url' => 'Ikon (gambar)',
        'section' => 'Bagian', 'section_id' => 'Bagian', 'badge' => 'Label', 'description' => 'Keterangan', 'questions' => 'Pertanyaan',
        'schedule' => 'Jadwal', 'color' => 'Warna', 'kind' => 'Jenis', 'workstation' => 'Workstation', 'berkas' => 'Bukti',
        'left_title' => 'TTD kiri: judul', 'left_role' => 'TTD kiri: jabatan', 'left_name' => 'TTD kiri: nama',
        'right_title' => 'TTD kanan: judul', 'right_role' => 'TTD kanan: jabatan', 'right_name' => 'TTD kanan: nama',
    ];

    /** @return array{from: string, to: string, from_utc: ?string, to_utc: ?string, group: string, actions: list<string>, entity: string, q: string, page: int, error: ?string} */
    public static function filters(array $query): array
    {
        $text = static fn (string $key): string => is_string($query[$key] ?? null) ? trim($query[$key]) : '';
        $from = self::date($text('dari'));
        $to = self::date($text('sampai'));
        $error = null;
        if (($text('dari') !== '' && $from === null) || ($text('sampai') !== '' && $to === null)) {
            $error = 'Tanggal tidak valid.';
        } elseif ($from !== null && $to !== null && $to < $from) {
            $error = 'Tanggal akhir tidak boleh sebelum tanggal awal.';
        }
        $okFrom = $error === null && $from !== null;
        $okTo = $error === null && $to !== null;
        $utc = new DateTimeZone('UTC');
        $group = isset(self::GROUPS[$text('aksi')]) ? $text('aksi') : '';
        return [
            'from' => $okFrom ? $from->format('Y-m-d') : '',
            'to' => $okTo ? $to->format('Y-m-d') : '',
            // WIB days as UTC bounds: 1 Okt 00:00 WIB = 30 Sep 17:00 UTC; the end is the next day, exclusive.
            'from_utc' => $okFrom ? $from->setTimezone($utc)->format('Y-m-d H:i:s') : null,
            'to_utc' => $okTo ? $to->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s') : null,
            'group' => $group,
            'actions' => $group !== '' ? self::GROUPS[$group][1] : [],
            'entity' => isset(self::ENTITIES[$text('objek')]) ? $text('objek') : '',
            'q' => mb_substr($text('q'), 0, 100),
            'page' => max(1, (int) $text('halaman')),
            'error' => $error,
        ];
    }

    /**
     * One log row in words: time, action with its tone, the object, detail lines, IP and device.
     * @param array<string, array<string, string>> $fieldLabels entity type => field => label
     */
    public static function row(array $log, array $fieldLabels = []): array
    {
        $meta = json_decode((string) $log['meta_json'], true);
        $meta = is_array($meta) ? $meta : [];
        $action = (string) $log['action'];
        $type = (string) $log['entity_type'];
        $labels = ($fieldLabels[$type] ?? []) + self::LABELS;
        $label = static fn (string $field): string => $labels[$field] ?? $field;
        $value = static fn (mixed $v): string => $v === null || $v === '' ? '—' : mb_strimwidth(is_scalar($v) ? (string) $v : (string) json_encode($v, JSON_UNESCAPED_UNICODE), 0, 80, '…');

        $object = (string) ($log['entry_no'] ?? '');
        $lines = [];
        if ($action === 'export') {
            $module = (string) ($meta['module'] ?? '');
            $object = 'Rekap ' . (self::MODULES[$module] ?? preg_replace('/^rekap-/', '', $module));
            $lines[] = (self::FORMATS[$meta['format'] ?? ''] ?? (string) ($meta['format'] ?? '')) . ' · ' . (int) ($meta['rows'] ?? 0) . ' baris · ' . ($meta['report_id'] ?? '');
            $filters = array_diff_key(is_array($meta['filters'] ?? null) ? $meta['filters'] : [], ['id' => 1]);
            $lines[] = 'Filter: ' . ($filters === [] ? 'tanpa filter' : implode(', ', array_map(static fn ($k, $v): string => $k . '=' . (is_scalar($v) ? $v : ''), array_keys($filters), $filters)));
        } elseif ($object === '' && isset($meta['title'])) {
            $object = (string) $meta['title'];
        } elseif ($object === '' && isset($meta['username'])) {
            $object = (string) $meta['username'];
        }
        foreach (array_slice($meta['changes'] ?? [], 0, 6) as $change) {
            if (is_array($change)) {
                $lines[] = $label((string) ($change['field'] ?? '')) . ': ' . $value($change['old'] ?? null) . ' → ' . $value($change['new'] ?? null);
            }
        }
        if (count($meta['changes'] ?? []) > 6) {
            $lines[] = '+' . (count($meta['changes']) - 6) . ' perubahan lain';
        }
        if (is_array($meta['values'] ?? null)) {
            $shown = array_filter($meta['values'], static fn ($v): bool => $v !== null && $v !== '');
            foreach (array_slice($shown, 0, 3, true) as $field => $v) {
                $lines[] = $label((string) $field) . ': ' . $value($v);
            }
        }
        if ($type === 'mail_settings') {
            $object = 'Pengaturan Email';
            if (is_array($meta['fields'] ?? null)) {
                $lines[] = 'Diubah: ' . implode(', ', array_map(static fn ($f): string => self::MAIL_FIELDS[$f] ?? (string) $f, $meta['fields']));
            }
            if (is_string($meta['to'] ?? null)) {
                $lines[] = 'Ke: ' . $value($meta['to']);
            }
            if (is_string($meta['reason'] ?? null)) {
                $lines[] = mb_strimwidth($meta['reason'], 0, 160, '…');
            }
        }
        if ($type === 'asset' && is_string($meta['text'] ?? null)) {
            $lines[] = mb_strimwidth($meta['text'], 0, 160, '…');
        }
        if (isset($meta['url']) && is_string($meta['url'])) {
            $lines[] = 'Alamat: ' . $value($meta['url']);
        }
        if (isset($meta['version']) && $action !== 'export') {
            $lines[] = 'Versi ' . (int) $meta['version'];
        }
        if (($meta['reason'] ?? null) === 'current_password') {
            $lines[] = 'Password saat ini salah';
        }
        if (($log['reason'] ?? null) !== null && $log['reason'] !== '') {
            $lines[] = 'Alasan: ' . $log['reason'];
        }
        return [
            'id' => (int) $log['id'],
            'time' => Format::dateTime((string) $log['created_at']),
            'action' => self::ACTIONS[$action] ?? $action,
            'tone' => self::TONES[$action] ?? 'plain',
            'entity' => self::ENTITIES[$type] ?? self::OLD_ENTITIES[$type] ?? $type,
            'object' => $object !== '' ? $object : ($log['entity_id'] !== null ? '#' . $log['entity_id'] : ''),
            'details' => $lines,
            'ip' => (string) ($log['ip'] ?? ''),
            'device' => self::device((string) ($log['user_agent'] ?? '')),
        ];
    }

    /** "Chrome · Windows" from a user agent; empty when unknown. */
    public static function device(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            str_contains($agent, 'curl/') => 'curl',
            default => '',
        };
        $system = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => '',
        };
        return implode(' · ', array_filter([$browser, $system]));
    }

    private static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Asia/Jakarta'));
        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }
}
