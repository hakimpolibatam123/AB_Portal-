<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * The Excel version of the clearance recap (same content as ClearancePdf): sheet "Data" with one row per employee
 * recorded (header frozen, auto-filter, real dates), sheet "Ringkasan" with the totals.
 */
final class ClearanceExcel
{
    public const STATUS = ['ok' => 'OK', 'issue' => 'Bermasalah', '' => 'Belum diperiksa'];

    /** @param list<array> $records records of App\Services\Clearance */
    public static function write(array $records, string $path, string $printedAt, string $filter = 'Semua karyawan'): void
    {
        usort($records, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));
        $writer = new Writer();
        $writer->openToFile($path);
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Data');
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));
        $head = (new Style())->setFontBold()->setBackgroundColor('E8EEF7');
        $date = (new Style())->setFormat('dd/mm/yyyy');
        $dateTime = (new Style())->setFormat('dd/mm/yyyy hh:mm');
        $ok = (new Style())->setFontColor('166534');
        $bad = (new Style())->setFontBold()->setFontColor('B91C1C')->setBackgroundColor('FEF2F2');
        $none = (new Style())->setFontColor('9CA3AF');
        $wrap = (new Style())->setShouldWrapText();

        $titles = ['No', 'No. Clearance', 'Nama', 'NIK', 'Bagian / Jabatan', 'Tgl. Pengunduran Diri'];
        foreach (Clearance::IT_ACCESS as $label) {
            $titles[] = $label;
        }
        array_push($titles, 'Keterangan', 'Status Unit IT', 'TTD Karyawan', 'Tgl. TTD Karyawan', 'TTD Ka. Unit IT', 'Tgl. TTD Ka. Unit IT', 'Dibuat oleh', 'Diperbarui', 'Diperbarui oleh');
        $writer->addRow(Row::fromValues($titles, $head));

        $signed = 0;
        $empSigned = 0;
        $issues = 0;
        foreach ($records as $i => $r) {
            $it = $r['it'] ?? [];
            $progress = Clearance::progress($r);
            $signed += $progress['signed'] ? 1 : 0;
            $emp = $r['employeeSign'] ?? null;
            $empSigned += $emp !== null ? 1 : 0;
            $issues += $progress['issue'] ? 1 : 0;
            $cells = [
                Cell::fromValue($i + 1), Cell::fromValue((string) $r['id']), Cell::fromValue((string) $r['name']),
                Cell::fromValue((string) ($r['nik'] ?? '')), Cell::fromValue((string) ($r['position'] ?? '')),
                self::date((string) ($r['resignDate'] ?? ''), $date),
            ];
            $notes = [];
            foreach (Clearance::IT_ACCESS as $key => $label) {
                $v = (string) ($it['access'][$key] ?? '');
                $cells[] = Cell::fromValue(self::STATUS[$v] ?? '', $v === 'ok' ? $ok : ($v === 'issue' ? $bad : $none));
                $note = trim((string) ($it['notes'][$key] ?? ''));
                if ($note !== '') {
                    $notes[] = $label . ': ' . $note;
                }
            }
            $sign = $it['sign'] ?? null;
            array_push($cells,
                Cell::fromValue(implode("\n", $notes), $notes !== [] ? $wrap : null),
                Cell::fromValue($progress['label']),
                Cell::fromValue($emp !== null ? 'Sudah ditandatangani' : 'Belum ditandatangani', $emp === null ? $none : null),
                self::dateTime($emp['at'] ?? null, $dateTime),
                Cell::fromValue($sign !== null ? (trim((string) ($sign['name'] ?? '')) ?: '(tanpa nama)') : 'Belum ditandatangani', $sign === null ? $none : null),
                self::dateTime($sign['at'] ?? null, $dateTime),
                Cell::fromValue((string) ($r['createdBy'] ?? '')),
                self::dateTime($r['updatedAt'] ?? null, $dateTime),
                Cell::fromValue((string) ($r['updatedBy'] ?? '')),
            );
            $writer->addRow(new Row($cells));
        }
        $widths = [5, 15, 26, 14, 24, 14];
        foreach (Clearance::IT_ACCESS as $label) {
            $widths[] = max(12, mb_strlen($label) + 2);
        }
        array_push($widths, 42, 20, 20, 17, 22, 17, 18, 17, 18);
        foreach ($widths as $i => $width) {
            $sheet->setColumnWidth($width, $i + 1);
        }
        $sheet->setAutoFilter(new AutoFilter(0, 1, count($titles) - 1, count($records) + 1));

        $summary = $writer->addNewSheetAndMakeItCurrent();
        $summary->setName('Ringkasan');
        $bold = (new Style())->setFontBold();
        $config = config('report');
        $lines = [
            ['Rumah sakit', $config['hospital'] . ' — ' . $config['unit']],
            ['Laporan', 'Rekap Clearance Karyawan'],
            ['Dicetak', $printedAt],
            ['Filter', $filter],
            ['Karyawan didata', count($records)],
            ['Sudah TTD Ka. Unit IT', $signed],
            ['Belum TTD Ka. Unit IT', count($records) - $signed],
            ['Sudah TTD Karyawan', $empSigned],
            ['Belum TTD Karyawan', count($records) - $empSigned],
            ['Ada akses bermasalah', $issues],
        ];
        foreach ($lines as [$name, $value]) {
            $writer->addRow(new Row([Cell::fromValue($name, $bold), Cell::fromValue($value)]));
        }
        $summary->setColumnWidth(24, 1);
        $summary->setColumnWidth(48, 2);
        $writer->close();
    }

    /** A calendar date "Y-m-d" as a real Excel date (empty when not given). */
    private static function date(string $ymd, Style $style): Cell
    {
        $parsed = $ymd !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d', $ymd) : false;
        return $parsed !== false ? Cell::fromValue($parsed, $style) : Cell::fromValue('');
    }

    /** A UTC ISO time as a WIB Excel date-time (empty when not given). */
    private static function dateTime(mixed $utc, Style $style): Cell
    {
        if (!is_string($utc) || $utc === '') {
            return Cell::fromValue('');
        }
        $wib = (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'));
        return Cell::fromValue(new DateTimeImmutable($wib->format('Y-m-d H:i:s')), $style);
    }
}
