<?php
declare(strict_types=1);

namespace App\Services;

use Mpdf\Mpdf;

/**
 * The PDF of one Serah Terima Perangkat (berita acara, A4 portrait), with the signatures placed on the form.
 * A signature not given yet leaves a blank space to sign on paper.
 */
final class HandoverPdf
{
    private const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    private const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    /**
     * @param array $handover one record of AssetLedger::handover()
     * @param callable(?array): ?string $signaturePath the image file of a signature, or null
     */
    public static function render(array $handover, callable $signaturePath): string
    {
        $dir = BASE_PATH . '/storage/app/tmp/mpdf';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        $config = config('report');
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 'format' => 'A4', 'margin_left' => 20, 'margin_right' => 20, 'margin_top' => 18, 'margin_bottom' => 16, 'margin_footer' => 8,
            'tempDir' => $dir, 'fontDir' => [BASE_PATH . '/resources/fonts'],
            'fontdata' => ['plex' => ['R' => 'IBMPlexSans-Regular.ttf', 'B' => 'IBMPlexSans-Bold.ttf']], 'default_font' => 'plex', 'default_font_size' => 10,
        ]);
        $mpdf->SetTitle('Berita Acara Serah Terima ' . $handover['id']);
        $mpdf->SetAuthor($config['hospital']);
        $mpdf->SetCreator('AB Portal Hub');
        $mpdf->SetHTMLFooter('<table width="100%" style="font-size:8pt;color:#6b7280"><tr><td>' . e($handover['id']) . '</td><td align="right">Halaman {PAGENO} dari {nbpg}</td></tr></table>');
        $mpdf->WriteHTML(self::css(), \Mpdf\HTMLParserMode::HEADER_CSS);
        $mpdf->WriteHTML(self::body($handover, $config, $signaturePath), \Mpdf\HTMLParserMode::HTML_BODY);
        return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    /** "Rabu, 11 November 2026" of a YYYY-MM-DD date. */
    public static function longDate(string $date): string
    {
        $time = strtotime($date . ' 12:00:00 UTC');
        if ($time === false) {
            return $date;
        }
        return self::DAYS[(int) gmdate('w', $time)] . ', ' . (int) gmdate('j', $time) . ' ' . self::MONTHS[(int) gmdate('n', $time) - 1] . ' ' . gmdate('Y', $time);
    }

    private static function css(): string
    {
        return 'body { font-family: plex; color: #1f2937; line-height: 1.45; }
            .head td { vertical-align: middle; } .org { font-size: 9pt; color: #4b5563; text-align: right; } .rule { border-bottom: 0.5mm solid #0f766e; margin: 2mm 0 6mm; }
            h1 { font-size: 14pt; text-align: center; margin: 0; } .no { text-align: center; font-size: 10pt; color: #374151; margin: 1mm 0 6mm; }
            p { margin: 0 0 3mm; } h2 { font-size: 10.5pt; margin: 4mm 0 1.5mm; color: #0f4c45; }
            table.party td { padding: 0.7mm 0; vertical-align: top; } table.party td.k { width: 42mm; color: #374151; }
            table.data { width: 100%; border-collapse: collapse; margin-top: 1mm; }
            table.data th { background-color: #e6f2f0; text-align: left; padding: 1.6mm; border: 0.2mm solid #94a3b8; font-size: 9pt; }
            table.data td { padding: 1.6mm; border: 0.2mm solid #cbd5e1; vertical-align: top; font-size: 9pt; }
            table.sign { width: 100%; margin-top: 8mm; page-break-inside: avoid; } table.sign td { width: 50%; text-align: center; vertical-align: top; }
            .space { height: 28mm; } .name { font-weight: bold; } .role { font-size: 9pt; color: #4b5563; }';
    }

    private static function body(array $h, array $config, callable $signaturePath): string
    {
        $logo = is_file($config['logo']) ? '<img src="' . e($config['logo']) . '" height="11mm">' : '';
        $rows = '';
        foreach (array_values($h['devices'] ?? []) as $i => $device) {
            $rows .= '<tr><td>' . ($i + 1) . '</td><td>' . e((string) $device['id']) . '</td><td>' . e((string) $device['name']) . '</td><td>'
                . e(trim(($device['brand'] ?? '') . ' ' . ($device['model'] ?? ''))) . '</td><td>' . e((string) ($device['serial'] ?? '')) . '</td></tr>';
        }
        $party = static fn (string $name, string $role, string $extra = ''): string => '<table class="party"><tr><td class="k">Nama</td><td>: ' . e($name) . '</td></tr>'
            . '<tr><td class="k">Jabatan / Unit</td><td>: ' . e($role !== '' ? $role : '-') . '</td></tr>' . $extra . '</table>';
        $sign = static function (?array $file, string $label, string $name, string $role) use ($signaturePath): string {
            $path = $file !== null ? $signaturePath($file) : null;
            return '<td>' . e($label) . '<div class="space">' . ($path !== null ? '<img src="' . e($path) . '" style="height:27mm">' : '') . '</div>'
                . '<div class="name">' . e($name) . '</div><div class="role">' . e($role) . '</div></td>';
        };
        return '<table width="100%" class="head"><tr><td>' . $logo . '</td><td class="org">' . e($config['hospital']) . '<br>' . e($config['unit']) . '</td></tr></table><div class="rule"></div>'
            . '<h1>BERITA ACARA SERAH TERIMA PERANGKAT</h1><div class="no">Nomor: ' . e($h['id']) . '</div>'
            . '<p>Pada hari ' . e(self::longDate((string) $h['date'])) . ', telah dilakukan serah terima perangkat antara:</p>'
            . '<h2>Pihak Pertama (Yang Menyerahkan)</h2>' . $party((string) $h['fromName'], (string) ($h['fromRole'] ?? ''))
            . '<h2>Pihak Kedua (Yang Menerima)</h2>' . $party((string) $h['toName'], (string) ($h['toRole'] ?? ''), '<tr><td class="k">Departemen</td><td>: ' . e((string) $h['toDept']) . '</td></tr>')
            . '<h2>Perangkat yang Diserahterimakan</h2><table class="data"><thead><tr><th width="8%">No</th><th width="24%">ID Aset</th><th>Nama Perangkat</th><th width="24%">Merek / Model</th><th width="18%">Serial Number</th></tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<h2>Kondisi Perangkat</h2><p>' . e((string) $h['condition']) . '</p>'
            . (($h['note'] ?? '') !== '' ? '<h2>Keterangan</h2><p>' . nl2br(e((string) $h['note'])) . '</p>' : '')
            . '<p>Sejak berita acara ini ditandatangani, tanggung jawab atas perangkat tersebut beralih dari Pihak Pertama kepada Pihak Kedua.</p>'
            . '<table class="sign"><tr>' . $sign($h['signFrom'] ?? null, 'Yang Menyerahkan,', (string) $h['fromName'], (string) ($h['fromRole'] ?? ''))
            . $sign($h['signTo'] ?? null, 'Yang Menerima,', (string) $h['toName'], (string) ($h['toRole'] ?? '')) . '</tr></table>';
    }
}
