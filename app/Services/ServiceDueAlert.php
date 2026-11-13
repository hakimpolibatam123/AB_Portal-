<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Reminders for Aset IT vendor deadlines ("Tenggat Vendor"): an open service whose deadline is tomorrow (H-1) — or
 * already today or past, when the reminder could not go out earlier — gets one email per deadline.
 * Pure logic; bin/service-alerts.php loads the services, sends the email and records what was sent.
 */
final class ServiceDueAlert
{
    private const CLOSED = ['done', 'rejected'];
    private const STATUS = ['reported' => 'Dilaporkan', 'progress' => 'Dalam Proses', 'parts' => 'Menunggu Suku Cadang'];

    /**
     * Open services due by tomorrow that have no reminder yet for that deadline, nearest deadline first.
     * @param list<array> $services
     * @param array<string, true> $sent "SRV-2026-0001|2026-11-07" keys of reminders already sent
     * @return list<array>
     */
    public static function due(array $services, string $today, array $sent): array
    {
        $tomorrow = (new \DateTimeImmutable($today))->modify('+1 day')->format('Y-m-d');
        $out = array_values(array_filter($services, static fn (array $t): bool =>
            !in_array($t['status'] ?? '', self::CLOSED, true)
            && ($t['dueDate'] ?? '') !== '' && $t['dueDate'] <= $tomorrow
            && !isset($sent[$t['id'] . '|' . $t['dueDate']])));
        usort($out, static fn (array $a, array $b): int => [$a['dueDate'], $a['id']] <=> [$b['dueDate'], $b['id']]);
        return $out;
    }

    /** "besok", "hari ini", "lewat 2 hari" */
    public static function when(string $due, string $today): string
    {
        $days = (int) (new \DateTimeImmutable($today))->diff(new \DateTimeImmutable($due))->format('%r%a');
        return match (true) {
            $days === 1 => 'besok',
            $days === 0 => 'hari ini',
            $days < 0 => 'lewat ' . -$days . ' hari',
            default => $days . ' hari lagi',
        };
    }

    /**
     * The email: subject, HTML and plain text.
     * @param list<array> $due
     * @return array{subject: string, html: string, text: string}
     */
    public static function email(array $due, string $today, string $portalUrl): array
    {
        $first = $due[0];
        $subject = count($due) === 1
            ? 'Pengingat tenggat vendor ' . self::when($first['dueDate'], $today) . ': ' . $first['deviceName'] . ' (' . $first['id'] . ')'
            : 'Pengingat: ' . count($due) . ' servis mendekati tenggat vendor';
        $rows = '';
        $text = "Servis Aset IT berikut mendekati atau melewati tenggat vendor:\n\n";
        foreach ($due as $t) {
            $link = $portalUrl . '/aset/progres-servis?id=' . rawurlencode((string) $t['id']);
            $when = self::when($t['dueDate'], $today);
            $late = $t['dueDate'] <= $today;
            $rows .= '<tr>'
                . '<td style="padding:8px;border:1px solid #dfe5e4"><b>' . self::e($t['deviceName']) . '</b><br><span style="color:#637377;font-family:monospace">' . self::e($t['deviceId']) . '</span></td>'
                . '<td style="padding:8px;border:1px solid #dfe5e4">' . self::e($t['id']) . '<br>' . self::e($t['type'] ?? '') . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe5e4">' . self::e($t['technician'] ?? '') . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe5e4">' . self::e(AssetLedger::dateText($t['dueDate'])) . '<br><b style="color:' . ($late ? '#b42318' : '#9a5b00') . '">' . self::e(ucfirst($when)) . '</b></td>'
                . '<td style="padding:8px;border:1px solid #dfe5e4">' . self::e(self::STATUS[$t['status']] ?? $t['status']) . '</td>'
                . '<td style="padding:8px;border:1px solid #dfe5e4"><a href="' . self::e($link) . '">Buka</a></td>'
                . '</tr>';
            $text .= '- ' . $t['deviceName'] . ' (' . $t['deviceId'] . '), ' . $t['id'] . ', vendor: ' . ($t['technician'] ?? '-')
                . ', tenggat ' . AssetLedger::dateText($t['dueDate']) . ' (' . $when . "), status " . (self::STATUS[$t['status']] ?? $t['status']) . "\n  " . $link . "\n";
        }
        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;color:#17262a">'
            . '<p>Servis Aset IT berikut <b>mendekati atau melewati tenggat vendor</b>. Mohon follow up ke vendor / teknisi.</p>'
            . '<table style="border-collapse:collapse;font-size:13px"><thead><tr style="background:#eef7f4;text-align:left">'
            . '<th style="padding:8px;border:1px solid #dfe5e4">Perangkat</th><th style="padding:8px;border:1px solid #dfe5e4">Servis</th>'
            . '<th style="padding:8px;border:1px solid #dfe5e4">Teknisi / Vendor</th><th style="padding:8px;border:1px solid #dfe5e4">Tenggat</th>'
            . '<th style="padding:8px;border:1px solid #dfe5e4">Status</th><th style="padding:8px;border:1px solid #dfe5e4"></th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p style="color:#637377;font-size:12px">Email otomatis dari Portal Monitoring IT — RS Awal Bros Botania. Tenggat bisa diubah di formulir Servis &amp; Pemeliharaan.</p></div>';
        return ['subject' => $subject, 'html' => $html, 'text' => $text];
    }

    private static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
