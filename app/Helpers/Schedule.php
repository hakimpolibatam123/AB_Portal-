<?php
declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;

/**
 * Schedule rules of the dashboard form cards (same rules as the design's store.js). A schedule is
 * ['type' => daily|weekly|monthly|yearly|dates, 'days' => [0–6, 0 = Minggu], 'month_day' => 1–31|'last',
 *  'year_md' => 'MM-DD', 'dates' => ['YYYY-MM-DD'], 'start' => 'YYYY-MM-DD'|'', 'end' => …, 'deadline' => 'HH:MM'|'', 'reminder' => bool].
 * Dates are WIB calendar dates (the app's time zone).
 */
final class Schedule
{
    private const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    private const DAY_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    private const DAY_LONG = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    /**
     * Badge of a form card: Harian · Mingguan - Senin / Sen, Kam · Bulanan - Tgl 1 / Akhir bulan ·
     * Tahunan - 17 Agu · Tanggal tertentu (same text as scheduleLabel() in dashboard-store.js).
     * @return array{text: string, tone: 'teal'|'blue'|'amber'|'purple'|'slate'}
     */
    public static function label(array $schedule): array
    {
        switch ($schedule['type'] ?? 'daily') {
            case 'weekly':
                $days = array_map('intval', $schedule['days'] ?? []);
                usort($days, static fn (int $a, int $b): int => (($a + 6) % 7) <=> (($b + 6) % 7));
                if ($days === []) {
                    return ['text' => 'Mingguan', 'tone' => 'blue'];
                }
                $names = count($days) === 1 ? self::DAY_LONG[$days[0]] : implode(', ', array_map(static fn (int $d): string => self::DAY_SHORT[$d], $days));
                return ['text' => 'Mingguan - ' . $names, 'tone' => 'blue'];
            case 'monthly':
                return ['text' => 'Bulanan - ' . (($schedule['month_day'] ?? 1) === 'last' ? 'Akhir bulan' : 'Tgl ' . (int) ($schedule['month_day'] ?: 1)), 'tone' => 'amber'];
            case 'yearly':
                $parts = explode('-', (string) ($schedule['year_md'] ?? ''));
                $text = count($parts) === 2 ? ' - ' . (int) $parts[1] . ' ' . self::MONTHS[(int) $parts[0] - 1] : '';
                return ['text' => 'Tahunan' . $text, 'tone' => 'purple'];
            case 'dates':
                return ['text' => 'Tanggal tertentu', 'tone' => 'slate'];
            default:
                return ['text' => 'Harian', 'tone' => 'teal'];
        }
    }

    public static function isDue(array $schedule, DateTimeImmutable $day): bool
    {
        $key = $day->format('Y-m-d');
        if (($schedule['start'] ?? '') !== '' && $key < $schedule['start']) {
            return false;
        }
        if (($schedule['end'] ?? '') !== '' && $key > $schedule['end']) {
            return false;
        }
        $lastDay = (int) $day->format('t');
        return match ($schedule['type'] ?? 'daily') {
            'weekly' => in_array((int) $day->format('w'), array_map('intval', $schedule['days'] ?? []), true),
            'monthly' => ($schedule['month_day'] ?? 1) === 'last'
                ? (int) $day->format('j') === $lastDay
                : (int) $day->format('j') === min(max(1, (int) ($schedule['month_day'] ?? 1)), $lastDay),
            'yearly' => ($schedule['year_md'] ?? '') !== '' && $day->format('m-d') === $schedule['year_md'],
            'dates' => in_array($key, $schedule['dates'] ?? [], true),
            default => true,
        };
    }

    /** First due day after $from, looking up to about two years ahead. */
    public static function nextDue(array $schedule, DateTimeImmutable $from): ?DateTimeImmutable
    {
        $day = $from->setTime(0, 0)->modify('+1 day');
        for ($i = 0; $i < 800; $i++, $day = $day->modify('+1 day')) {
            if (self::isDue($schedule, $day)) {
                return $day;
            }
        }
        return null;
    }

    /**
     * Footer status of a form card today: Belum ada data · Berikutnya: 3 Nov · Tidak terjadwal · Selesai
     * · Belum Diisi (past the deadline with the reminder on) · Batas 14:00 · Hari ini.
     * @return array{text: string, tone: 'muted'|'next'|'ok'|'warn'|'plain', due: bool}
     */
    public static function status(array $schedule, bool $doneToday, bool $hasData, DateTimeImmutable $now): array
    {
        $due = self::isDue($schedule, $now);
        if (!$hasData && !$doneToday) {
            return ['text' => 'Belum ada data', 'tone' => 'muted', 'due' => $due];
        }
        if (!$due) {
            $next = self::nextDue($schedule, $now);
            $text = $next !== null ? 'Berikutnya: ' . (int) $next->format('j') . ' ' . self::MONTHS[(int) $next->format('n') - 1] : 'Tidak terjadwal';
            return ['text' => $text, 'tone' => 'next', 'due' => false];
        }
        if ($doneToday) {
            return ['text' => 'Selesai', 'tone' => 'ok', 'due' => true];
        }
        $deadline = (string) ($schedule['deadline'] ?? '');
        if ($deadline !== '' && !empty($schedule['reminder']) && $now->format('H:i') > $deadline) {
            return ['text' => 'Belum Diisi', 'tone' => 'warn', 'due' => true];
        }
        if ($deadline !== '') {
            return ['text' => 'Batas ' . $deadline, 'tone' => 'plain', 'due' => true];
        }
        return ['text' => 'Hari ini', 'tone' => 'plain', 'due' => true];
    }
}
