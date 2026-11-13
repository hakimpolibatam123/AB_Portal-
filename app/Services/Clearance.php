<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Format;
use RuntimeException;

/**
 * Form Clearance of a leaving employee, as kept by Unit IT: the employee's data and the Unit IT part only — the
 * accesses checked (and removed), a note per access, the signature of Ka. Unit IT and that of the employee (typed,
 * drawn or uploaded; they can be changed). The exports (ClearancePdf, ClearanceExcel) recap every employee recorded.
 *
 * Record: {id, name, nik, position, resignDate, it: {access: {key: ''|ok|issue}, notes: {key: text}, sign},
 *          employeeSign, createdAt, createdBy, updatedAt, updatedBy}; a sign is {file: {id, name}, at, name} or null
 *          (employeeSign's name is the employee's name; older records have no employeeSign).
 */
final class Clearance
{
    /** The accesses Unit IT checks (and removes). */
    public const IT_ACCESS = [
        'sirs' => 'SIRS', 'emedrec' => 'Akses E-Medrec', 'eprescription' => 'Akses E-Prescription', 'dashboard' => 'Dashboard',
        'wordclient' => 'Word Client', 'door_ruangan' => 'Akses Door Ruangan', 'door_loker' => 'Akses Door Loker', 'door_asrama' => 'Akses Door Asrama',
    ];
    /** Characters of a note per access: kept short (one line beside the access). */
    public const NOTE_MAX = 30;
    /** '' = not checked yet. */
    public const ACCESS_STATUS = ['ok' => 'Tidak ada masalah / Sudah dihapus', 'issue' => 'Bermasalah'];

    /**
     * The record to store from what the form sent. The id, creation and signature times are the server's; the
     * signature file is kept only when it is one of $files (uploaded as a clearance signature) or already on the record.
     *
     * @param array<int, string> $files attachment id => original name, of owner "clearance"
     */
    public static function normalize(array $in, ?array $existing, string $id, string $by, string $now, array $files): array
    {
        $name = self::text($in['name'] ?? '', 150);
        if ($name === '') {
            throw new RuntimeException('Nama karyawan wajib diisi.');
        }
        $date = self::text($in['resignDate'] ?? '', 10);
        if ($date !== '' && !self::isDate($date)) {
            throw new RuntimeException('Tanggal pengunduran diri tidak valid.');
        }
        $it = is_array($in['it'] ?? null) ? $in['it'] : [];
        $access = [];
        $notes = [];
        foreach (self::IT_ACCESS as $key => $_label) {
            $v = $it['access'][$key] ?? '';
            $access[$key] = is_string($v) && isset(self::ACCESS_STATUS[$v]) ? $v : '';
            $notes[$key] = self::text($it['notes'][$key] ?? '', self::NOTE_MAX); // one line beside the access on the form
        }
        return [
            'id' => $id,
            'name' => $name,
            'nik' => self::text($in['nik'] ?? '', 50),
            'position' => self::text($in['position'] ?? '', 150),
            'resignDate' => $date,
            'it' => [
                'access' => $access,
                'notes' => $notes,
                'sign' => self::signature($it['sign'] ?? null, $existing['it']['sign'] ?? null, $files, $now),
            ],
            // The resigning employee's own signature; its name is always the employee's name.
            'employeeSign' => self::named(self::signature($in['employeeSign'] ?? null, $existing['employeeSign'] ?? null, $files, $now), $name),
            'createdAt' => $existing['createdAt'] ?? $now,
            'createdBy' => $existing['createdBy'] ?? $by,
            'updatedAt' => $now,
            'updatedBy' => $by,
        ];
    }

    /** CLR-<year>-NNNN: the next number of that year. @param list<string> $ids */
    public static function nextId(array $ids, string $year): string
    {
        $prefix = 'CLR-' . $year . '-';
        $max = 0;
        foreach ($ids as $id) {
            if (str_starts_with($id, $prefix)) {
                $max = max($max, (int) substr($id, strlen($prefix)));
            }
        }
        return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    /** Where the IT part stands: signed by Ka. Unit IT or not, and whether an access has a problem. */
    public static function progress(array $record): array
    {
        $it = $record['it'] ?? [];
        $signed = ($it['sign'] ?? null) !== null;
        $issue = in_array('issue', (array) ($it['access'] ?? []), true);
        return ['signed' => $signed, 'issue' => $issue,
            'label' => $issue ? 'Ada akses bermasalah' : ($signed ? 'Sudah TTD IT' : 'Belum TTD IT')];
    }

    /** Filters of the list and of its PDF / Excel export (?status=). */
    public const FILTER_STATUS = [
        'signed' => 'Sudah TTD Ka. Unit IT', 'unsigned' => 'Belum TTD Ka. Unit IT', 'issue' => 'Ada akses bermasalah',
        'emp_signed' => 'Sudah TTD karyawan', 'emp_unsigned' => 'Belum TTD karyawan',
    ];

    /**
     * The records the list shows for these filters (query of /clearance/pdf and /clearance/excel): q searches the
     * number, name, NIK and position; dari / sampai bound the resignation date (Y-m-d; a record without one is left
     * out once a date is set); status is a key of FILTER_STATUS. Unknown or empty values do not filter.
     *
     * @param list<array> $records
     * @return list<array>
     */
    public static function filter(array $records, array $query): array
    {
        $f = self::filters($query);
        return array_values(array_filter($records, static function (array $r) use ($f): bool {
            if ($f['q'] !== '' && !str_contains(mb_strtolower(implode(' ', [$r['id'] ?? '', $r['name'] ?? '', $r['nik'] ?? '', $r['position'] ?? ''])), $f['q'])) {
                return false;
            }
            $date = (string) ($r['resignDate'] ?? '');
            if (($f['dari'] !== '' || $f['sampai'] !== '') && $date === '') {
                return false;
            }
            if (($f['dari'] !== '' && $date < $f['dari']) || ($f['sampai'] !== '' && $date > $f['sampai'])) {
                return false;
            }
            $p = self::progress($r);
            $emp = ($r['employeeSign'] ?? null) !== null;
            return match ($f['status']) {
                'signed' => $p['signed'], 'unsigned' => !$p['signed'], 'issue' => $p['issue'],
                'emp_signed' => $emp, 'emp_unsigned' => !$emp,
                default => true,
            };
        }));
    }

    /** The filters in words for the export ("Semua karyawan" when none), e.g. "Tgl. pengunduran diri 1 Nov 2026 – 30 Nov 2026 · Belum TTD Ka. Unit IT". */
    public static function filterText(array $query): string
    {
        $f = self::filters($query);
        $parts = [];
        if ($f['dari'] !== '' || $f['sampai'] !== '') {
            $parts[] = 'Tgl. pengunduran diri ' . ($f['dari'] !== '' ? Format::date($f['dari']) : 'awal') . ' – ' . ($f['sampai'] !== '' ? Format::date($f['sampai']) : 'sekarang');
        }
        if ($f['status'] !== '') {
            $parts[] = self::FILTER_STATUS[$f['status']];
        }
        if ($f['q'] !== '') {
            $parts[] = 'Cari "' . $f['q'] . '"';
        }
        return $parts === [] ? 'Semua karyawan' : implode(' · ', $parts);
    }

    /** @return array{q: string, dari: string, sampai: string, status: string} */
    private static function filters(array $query): array
    {
        $date = static fn (mixed $v): string => is_string($v) && self::isDate($v) ? $v : '';
        $status = is_string($query['status'] ?? null) && isset(self::FILTER_STATUS[$query['status']]) ? $query['status'] : '';
        return ['q' => mb_strtolower(self::text($query['q'] ?? '', 100)), 'dari' => $date($query['dari'] ?? ''), 'sampai' => $date($query['sampai'] ?? ''), 'status' => $status];
    }

    /** What the page draws the form from. */
    public static function definition(): array
    {
        $access = [];
        foreach (self::IT_ACCESS as $key => $label) {
            $access[] = ['key' => $key, 'label' => $label];
        }
        return ['access' => $access, 'accessStatus' => self::ACCESS_STATUS, 'noteMax' => self::NOTE_MAX, 'filterStatus' => self::FILTER_STATUS];
    }

    /** @param array<int, string> $files */
    private static function signature(mixed $sent, mixed $old, array $files, string $now): ?array
    {
        if (!is_array($sent) || !is_array($sent['file'] ?? null)) {
            return null; // removed, or never signed
        }
        $id = (int) ($sent['file']['id'] ?? 0);
        $name = self::text($sent['name'] ?? '', 100);
        if (is_array($old) && (int) ($old['file']['id'] ?? 0) === $id) {
            return ['file' => $old['file'], 'at' => $old['at'] ?? $now, 'name' => $name]; // same signature: keeps when it was signed
        }
        if (!isset($files[$id])) {
            throw new RuntimeException('Berkas tanda tangan tidak ditemukan. Ulangi tanda tangan.');
        }
        return ['file' => ['id' => $id, 'name' => $files[$id]], 'at' => $now, 'name' => $name];
    }

    private static function named(?array $sign, string $name): ?array
    {
        return $sign === null ? null : ['name' => $name] + $sign;
    }

    private static function text(mixed $value, int $max, bool $multiline = false): string
    {
        if (!is_string($value) && !is_int($value)) {
            return '';
        }
        $text = trim((string) $value);
        $text = $multiline ? str_replace("\r", '', $text) : preg_replace('/\s+/u', ' ', $text);
        return mb_substr((string) $text, 0, $max);
    }

    private static function isDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
