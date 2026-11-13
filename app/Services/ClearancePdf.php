<?php
declare(strict_types=1);

namespace App\Services;

use App\Helpers\Format;
use Mpdf\Mpdf;

/**
 * The export of Form Clearance: a recap of every employee recorded (A4 landscape), one row each with the
 * employee's data, the status of each access Unit IT checks, the notes, and the signatures of the employee and of
 * Ka. Unit IT.
 * Header, table head and footer repeat on every page.
 */
final class ClearancePdf
{
    /** Short column titles of the accesses (Clearance::IT_ACCESS order). */
    public const SHORT = [
        'sirs' => 'SIRS', 'emedrec' => 'E-Medrec', 'eprescription' => 'E-Presc.', 'dashboard' => 'Dashboard',
        'wordclient' => 'Word Client', 'door_ruangan' => 'Door Ruangan', 'door_loker' => 'Door Loker', 'door_asrama' => 'Door Asrama',
    ];

    /**
     * @param list<array> $records records of App\Services\Clearance
     * @param callable(?array): ?string $signaturePath the image file of a signature, or null
     */
    public static function render(array $records, callable $signaturePath, string $printedAt): string
    {
        $dir = BASE_PATH . '/storage/app/tmp/mpdf';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $config = config('report');
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4-L', 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 30, 'margin_bottom' => 14,
            'margin_header' => 8, 'margin_footer' => 6,
            'tempDir' => $dir, 'fontDir' => [BASE_PATH . '/resources/fonts'],
            'fontdata' => ['plex' => ['R' => 'IBMPlexSans-Regular.ttf', 'B' => 'IBMPlexSans-Bold.ttf']], 'default_font' => 'plex', 'default_font_size' => 8,
            'shrink_tables_to_fit' => 0,
        ]);
        $mpdf->SetTitle('Rekap Clearance Karyawan');
        $mpdf->SetAuthor($config['hospital']);
        $mpdf->SetCreator('AB Portal Hub');
        $logo = is_file($config['logo']) ? '<img src="' . e($config['logo']) . '" height="10mm">' : '<b>RS AWAL BROS</b>';
        $mpdf->SetHTMLHeader('<table width="100%" class="head"><tr><td width="78mm">' . $logo . '</td>'
            . '<td><div class="org">' . e($config['hospital'] . ' — ' . $config['unit']) . '</div><div class="title">Rekap Clearance Karyawan</div>'
            . '<div class="meta">Data karyawan yang mengundurkan diri dan pemeriksaan akses oleh Unit IT</div></td></tr></table><div class="rule"></div>');
        $mpdf->SetHTMLFooter('<table width="100%" class="meta"><tr><td>Dicetak ' . e($printedAt) . '</td><td align="right">Halaman {PAGENO} dari {nbpg}</td></tr></table>');
        $mpdf->WriteHTML(self::css(), \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML(self::body($records, $signaturePath), \Mpdf\HTMLParserMode::HTML_BODY);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    private static function css(): string
    {
        return 'body { font-family: plex; color: #1f2937; }
            .head td { vertical-align: middle; } .org { font-size: 8pt; color: #4b5563; } .title { font-size: 13pt; font-weight: bold; color: #0b3a6f; }
            .meta { font-size: 7.5pt; color: #4b5563; } .rule { border-bottom: 0.4mm solid #0b3a6f; }
            .stats { width: 100%; border-collapse: separate; border-spacing: 2mm 0; margin: 0 -2mm 3mm -2mm; }
            .stats td { border: 0.2mm solid #cbd5e1; padding: 1.6mm 3mm; width: 20%; }
            .stat-label { font-size: 7.5pt; color: #4b5563; } .stat-value { font-size: 12pt; font-weight: bold; }
            table.data { width: 100%; border-collapse: collapse; }
            table.data th { background-color: #e8eef7; font-weight: bold; padding: 1.2mm 1mm; border: 0.2mm solid #94a3b8; font-size: 7pt; vertical-align: middle; }
            table.data th.acc { text-align: center; }
            table.data td { padding: 1.1mm 1.2mm; border: 0.2mm solid #cbd5e1; vertical-align: top; font-size: 7.5pt; }
            td.c { text-align: center; } td.ok { color: #166534; font-weight: bold; } td.bad { color: #b91c1c; font-weight: bold; background-color: #fef2f2; }
            td.none { color: #9ca3af; } .sub { font-size: 6.8pt; color: #6b7280; }
            .empty { padding: 6mm; text-align: center; color: #6b7280; border: 0.2mm solid #cbd5e1; }
            .legend { margin-top: 2mm; font-size: 7pt; color: #4b5563; }';
    }

    /** @param list<array> $records */
    private static function body(array $records, callable $signaturePath): string
    {
        usort($records, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));
        $signed = 0;
        $empSigned = 0;
        $issues = 0;
        foreach ($records as $r) {
            $p = Clearance::progress($r);
            $signed += $p['signed'] ? 1 : 0;
            $empSigned += ($r['employeeSign'] ?? null) !== null ? 1 : 0;
            $issues += $p['issue'] ? 1 : 0;
        }
        $html = '<table class="stats"><tr>'
            . self::stat('Karyawan didata', (string) count($records)) . self::stat('Sudah TTD Karyawan', (string) $empSigned)
            . self::stat('Sudah TTD Ka. Unit IT', (string) $signed) . self::stat('Belum TTD Ka. Unit IT', (string) (count($records) - $signed))
            . self::stat('Ada akses bermasalah', (string) $issues)
            . '</tr></table>';
        if ($records === []) {
            return $html . '<div class="empty">Belum ada karyawan yang didata.</div>';
        }
        $head = '<tr><th width="6mm">No</th><th width="33mm">Nama / NIK</th><th width="25mm">Bagian / Jabatan</th><th width="18mm">Tgl. Pengunduran Diri</th>';
        foreach (Clearance::IT_ACCESS as $key => $_label) {
            $head .= '<th class="acc" width="12mm">' . e(self::SHORT[$key]) . '</th>';
        }
        $head .= '<th>Keterangan</th><th width="28mm">TTD Karyawan</th><th width="28mm">TTD Ka. Unit IT</th></tr>';
        $rows = '';
        foreach ($records as $i => $r) {
            $it = $r['it'] ?? [];
            $cells = '';
            $notes = [];
            foreach (Clearance::IT_ACCESS as $key => $label) {
                $v = $it['access'][$key] ?? '';
                $cells .= $v === 'ok' ? '<td class="c ok">OK</td>' : ($v === 'issue' ? '<td class="c bad">Masalah</td>' : '<td class="c none">—</td>');
                $note = trim((string) ($it['notes'][$key] ?? ''));
                if ($note !== '') {
                    $notes[] = '<b>' . e($label) . ':</b> ' . e($note);
                }
            }
            $rows .= '<tr><td class="c">' . ($i + 1) . '</td>'
                . '<td><b>' . e((string) $r['name']) . '</b>' . (($r['nik'] ?? '') !== '' ? '<br><span class="sub">NIK ' . e((string) $r['nik']) . '</span>' : '')
                . '<br><span class="sub">' . e((string) $r['id']) . '</span></td>'
                . '<td>' . e(($r['position'] ?? '') !== '' ? (string) $r['position'] : '—') . '</td>'
                . '<td>' . e(($r['resignDate'] ?? '') !== '' ? Format::date((string) $r['resignDate']) : '—') . '</td>'
                . $cells . '<td>' . ($notes !== [] ? implode('<br>', $notes) : '<span class="sub">—</span>') . '</td>'
                . '<td>' . self::signature($r['employeeSign'] ?? null, $signaturePath) . '</td>'
                . '<td>' . self::signature($it['sign'] ?? null, $signaturePath) . '</td></tr>';
        }
        return $html . '<table class="data" repeat_header="1"><thead>' . $head . '</thead><tbody>' . $rows . '</tbody></table>'
            . '<div class="legend">OK = tidak ada masalah / sudah dihapus · Masalah = bermasalah · — = belum diperiksa</div>';
    }

    private static function stat(string $label, string $value): string
    {
        return '<td><div class="stat-label">' . e($label) . '</div><div class="stat-value">' . e($value) . '</div></td>';
    }

    /** A signature (employee or Ka. Unit IT): a small picture (fitted inside 24 x 9 mm), the name, the date signed. */
    private static function signature(?array $sign, callable $signaturePath): string
    {
        if ($sign === null) {
            return '<span class="sub">Belum ditandatangani</span>';
        }
        $html = '';
        $path = $signaturePath($sign['file'] ?? null);
        if ($path !== null) {
            $size = @getimagesize($path);
            $ratio = is_array($size) && $size[1] > 0 ? $size[0] / $size[1] : 2.5;
            $width = min(24.0, 9.0 * $ratio);
            $html .= '<img src="' . e($path) . '" width="' . round($width, 1) . 'mm" height="' . round($width / $ratio, 1) . 'mm"><br>';
        }
        $name = trim((string) ($sign['name'] ?? ''));
        return $html . ($name !== '' ? e($name) : '<span class="sub">(tanpa nama)</span>')
            . (($sign['at'] ?? '') !== '' ? '<br><span class="sub">' . e(Format::dateTime((string) $sign['at'])) . '</span>' : '');
    }
}
