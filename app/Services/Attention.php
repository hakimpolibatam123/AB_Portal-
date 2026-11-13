<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Helpers\Format;
use App\Models\AssetAttention;
use App\Models\AttentionList;
use Throwable;

/**
 * "Perlu perhatian" (PRD FR-P6): reports with an answer marked red in the editor. The header bell lists the
 * ones of the last DAYS days (older ones leave the notification); the "Perlu perhatian" page lists them all.
 * Each item names the marked answers ("Kondisi CPU: Tidak baik") and opens the report on its recap page.
 * Aset IT services waiting for spare parts are in the bell too, for as long as they wait (kind "service").
 */
final class Attention
{
    public const DAYS = 7;
    public const BELL_LIMIT = 8;

    /** @var array{total: int, unread: int, items: list<array>}|null */
    private static ?array $bell = null;

    /**
     * The header bell of the signed-in account, read once per request; empty when the database is down. The
     * badge counts the reports this account has not seen yet (opened from the bell or its detail on a recap).
     */
    public static function notifications(): array
    {
        if (self::$bell !== null) {
            return self::$bell;
        }
        self::$bell = ['total' => 0, 'unread' => 0, 'items' => []];
        $userId = self::userId();
        if ($userId === null) {
            return self::$bell;
        }
        try {
            $recent = (new AttentionList(Database::connection()))->recent($userId, self::DAYS, self::BELL_LIMIT);
            $waiting = self::waiting($userId);
            // Reports and waiting services together, newest first.
            $items = array_merge(array_map([self::class, 'item'], $recent['rows']), array_map([self::class, 'serviceItem'], $waiting));
            usort($items, static fn (array $a, array $b): int => strcmp($b['sort'], $a['sort']));
            self::$bell = [
                'total' => $recent['total'] + count($waiting),
                'unread' => $recent['unread'] + count(array_filter($waiting, static fn (array $w): bool => !$w['read'])),
                'items' => array_slice($items, 0, self::BELL_LIMIT),
            ];
        } catch (Throwable $exception) {
            error_log('Notifikasi perlu perhatian: ' . $exception->getMessage());
        }
        return self::$bell;
    }

    /** The bell's unread count of an account (reports of the last DAYS days + waiting services), after "dilihat". */
    public static function unread(int $userId): int
    {
        $reports = (new AttentionList(Database::connection()))->recent($userId, self::DAYS, 1)['unread'];
        return $reports + count(array_filter(self::waiting($userId), static fn (array $w): bool => !$w['read']));
    }

    /**
     * Aset IT services waiting for spare parts (App\Models\AssetAttention); none when the asset tables are not
     * there yet, so the bell keeps working for the reports.
     */
    public static function waiting(int $userId): array
    {
        try {
            return (new AssetAttention(Database::connection()))->waiting($userId);
        } catch (Throwable $exception) {
            error_log('Notifikasi servis menunggu: ' . $exception->getMessage());
            return [];
        }
    }

    /** One waiting service as shown in the bell and on the page; it opens the Servis form of its device. */
    public static function serviceItem(array $w): array
    {
        $utc = AssetAttention::utc($w['since']);
        return [
            'kind' => 'service',
            'id' => $w['id'],
            'entry_no' => $w['id'],
            'form' => 'Aset IT · Menunggu Suku Cadang',
            'when' => Format::dateTime($utc),
            'sort' => $utc,
            'read' => $w['read'],
            'reasons' => array_values(array_filter([$w['deviceName'] . ' (' . $w['deviceId'] . ') — ' . $w['type'] . ': ' . $w['problem'], $w['note']])),
            'url' => url('/aset/progres-servis') . '?' . http_build_query(['id' => $w['id']]),
            'device' => $w['deviceName'], 'technician' => $w['technician'],
        ];
    }

    /** The signed-in account, or null. */
    public static function userId(): ?int
    {
        $id = Auth::check() ? (int) (Auth::user()['id'] ?? 0) : 0;
        return $id > 0 ? $id : null;
    }

    /** One report row of App\Models\AttentionList as shown in the bell and on the page. */
    public static function item(array $row): array
    {
        $definition = json_decode((string) ($row['definition_json'] ?? ''), true);
        $answers = json_decode((string) ($row['answers_json'] ?? ''), true);
        return [
            'kind' => 'report',
            'id' => (int) $row['id'],
            'entry_no' => (string) $row['entry_no'],
            'form' => (string) ($row['form_title'] ?? $row['slug']),
            'when' => Format::dateTime($row['submitted_at']),
            'sort' => (string) $row['submitted_at'],
            'read' => ($row['read_at'] ?? null) !== null,
            'reasons' => self::reasons(is_array($definition) ? $definition : [], is_array($answers) ? $answers : []),
            'url' => url('/form-dinamis/respons') . '?' . http_build_query(['id' => $row['slug'], 'buka' => (int) $row['id']]),
        ];
    }

    /** "Question: answer" for every answer with a red option. @return list<string> */
    public static function reasons(array $definition, array $answers): array
    {
        $reasons = [];
        foreach (FormDefinition::answerItems($definition) as $q) {
            if (!array_key_exists($q['id'] ?? '', $answers) || $q['type'] === 'confirm') {
                continue;
            }
            $shown = FormAnswers::display($q, $answers[$q['id']]);
            if (in_array($shown['tone'] ?? '', ['warn', 'bad'], true)) {
                $reasons[] = $q['title'] . ': ' . $shown['text'];
            }
        }
        return $reasons;
    }
}
