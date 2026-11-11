<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Import Perangkat of Registrasi: the Excel template, reading a filled one, and registering its rows through
 * AssetLedger::register(), so a batch follows the same rules as the form. A row that breaks a rule is reported and
 * left out; the others go in.
 */
final class AssetImport
{
    public const MAX_ROWS = 1000;
    private const TEMPLATE_ROWS = 500;

    /** field => header in the template; * marks a required one. */
    private const COLUMNS = [
        'id' => 'Barcode / ID Aset', 'category' => 'Kategori *', 'kind' => 'Jenis (khusus Lainnya)', 'name' => 'Nama Perangkat *', 'department' => 'Departemen *', 'pic' => 'Penanggung Jawab',
        'brand' => 'Merek *', 'model' => 'Model *', 'serial' => 'Serial Number', 'ip' => 'IP Address', 'mac' => 'MAC Address', 'status' => 'Status', 'purchaseDate' => 'Tanggal Pembelian', 'notes' => 'Catatan',
        'hostname' => 'Hostname', 'os' => 'Sistem Operasi', 'cpu' => 'Prosesor', 'ram' => 'RAM', 'storage' => 'Penyimpanan', 'size' => 'Ukuran Layar',
    ];
    private const REQUIRED = ['category', 'name', 'department', 'brand', 'model'];
    /** Category key => its name, as in aset-store.js. */
    private const CATEGORIES = ['pc' => 'PC Desktop', 'minipc' => 'Mini PC', 'laptop' => 'Laptop', 'monitor' => 'Monitor', 'printer' => 'Printer', 'scanner' => 'Scanner Dokumen', 'cctv' => 'CCTV', 'network' => 'Perangkat Jaringan', 'server' => 'Server', 'ups' => 'UPS', 'other' => 'Lainnya'];
    private const CATEGORY_ALIASES = ['pc' => 'pc', 'minipc' => 'minipc', 'scanner' => 'scanner', 'jaringan' => 'network', 'network' => 'network', 'other' => 'other'];
    private const STATUSES = ['aktif', 'cadangan', 'rusak'];
    /** As in Registrasi: these must have an IP, these have no network at all. */
    private const NEEDS_IP = ['pc', 'minipc', 'cctv', 'network', 'server'];
    private const NO_NETWORK = ['monitor', 'scanner'];
    /** Specification columns that apply to a category. */
    private const COMPUTER_SPECS = ['hostname', 'os', 'cpu', 'ram', 'storage'];
    private const SPECS = ['pc' => self::COMPUTER_SPECS, 'minipc' => self::COMPUTER_SPECS, 'laptop' => self::COMPUTER_SPECS, 'server' => self::COMPUTER_SPECS, 'monitor' => ['size']];

    /** Writes the template: an empty sheet to fill in, with dropdowns, and a sheet of instructions. */
    public static function template(string $path): void
    {
        $columns = array_values(self::COLUMNS);
        $writer = new Writer();
        $writer->openToFile($path);
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Data Perangkat');
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));
        $writer->addRow(Row::fromValues($columns, (new Style())->setFontBold()->setBackgroundColor('D9EFEA')));
        foreach ($columns as $i => $column) {
            $sheet->setColumnWidth(max(16, mb_strlen($column) + 4), $i + 1);
        }
        $guide = $writer->addNewSheetAndMakeItCurrent();
        $guide->setName('Petunjuk');
        $guide->setColumnWidth(30, 1);
        $guide->setColumnWidth(90, 2);
        $bold = (new Style())->setFontBold();
        foreach (self::guide() as [$a, $b, $head]) {
            $writer->addRow(Row::fromValues([$a, $b], $head ? $bold : null));
        }
        $writer->close();
        self::finishSheet($path);
    }

    /** @return list<array{0: string, 1: string, 2: bool}> */
    private static function guide(): array
    {
        return [
            ['Cara mengisi', '', true],
            ['1', 'Isi sheet "Data Perangkat", satu baris untuk satu perangkat. Jangan mengubah judul kolom.', false],
            ['2', 'Kolom bertanda * wajib diisi.', false],
            ['3', 'Simpan sebagai .xlsx, lalu unggah di Registrasi Perangkat > Import Excel. Hasilnya bisa diperiksa dulu sebelum disimpan.', false],
            ['', '', false],
            ['Kolom', 'Keterangan', true],
            ['Barcode / ID Aset', 'Kode label, contoh ITEM-00049895. Kosongkan bila tidak ada label: ID dibuat otomatis, contoh AB-BTN-PC-0001.', false],
            ['Kategori', implode(', ', self::CATEGORIES), false],
            ['Jenis (khusus Lainnya)', 'Wajib bila Kategori = Lainnya, contoh Proyektor.', false],
            ['IP Address', 'Wajib untuk PC Desktop, Mini PC, CCTV, Perangkat Jaringan, dan Server. Monitor dan Scanner Dokumen tidak memakai IP dan MAC.', false],
            ['Status', 'Aktif, Cadangan, atau Rusak. Kosong berarti Aktif.', false],
            ['Tanggal Pembelian', 'Format YYYY-MM-DD (2026-01-31) atau DD/MM/YYYY (31/01/2026).', false],
            ['Hostname s.d. Penyimpanan', 'Untuk PC Desktop, Mini PC, Laptop, dan Server.', false],
            ['Ukuran Layar', 'Untuk Monitor. Cukup angka, contoh 23.8.', false],
            ['', '', false],
            ['Contoh baris', '', true],
            ['Barcode / ID Aset', 'ITEM-00049900', false],
            ['Kategori', 'PC Desktop', false],
            ['Nama Perangkat', 'PC Admisi 1', false],
            ['Departemen', 'Admisi & Pendaftaran', false],
            ['Merek / Model', 'Dell / OptiPlex 7010', false],
            ['IP Address', '192.168.10.21', false],
        ];
    }

    /**
     * What the writer has no option for, added to the sheet's XML: dropdowns of Kategori and Status, and text format
     * on the columns, so Excel keeps a long serial number and a barcode as they are typed.
     */
    private static function finishSheet(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return;
        }
        $name = 'xl/worksheets/sheet1.xml';
        $xml = (string) $zip->getFromName($name);
        $styles = (string) $zip->getFromName('xl/styles.xml');
        if (preg_match('/<cellXfs count="(\d+)">/', $styles, $found) === 1) {
            $text = (int) $found[1];
            $styles = str_replace([$found[0], '</cellXfs>'], ['<cellXfs count="' . ($text + 1) . '">', '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'], $styles);
            $zip->addFromString('xl/styles.xml', $styles);
            $xml = str_replace(' customWidth="true"/>', ' customWidth="true" style="' . $text . '"/>', $xml);
        }
        $last = self::TEMPLATE_ROWS + 1;
        $rule = static fn (string $column, array $values): string => '<dataValidation type="list" allowBlank="1" showErrorMessage="1" errorTitle="Pilihan tidak ada" error="Pilih dari daftar." sqref="' . $column . '2:' . $column . $last . '"><formula1>"' . htmlspecialchars(implode(',', $values), ENT_XML1) . '"</formula1></dataValidation>';
        $keys = array_keys(self::COLUMNS);
        $letter = static fn (string $key): string => chr(65 + (int) array_search($key, $keys, true));
        $validations = '<dataValidations count="2">' . $rule($letter('category'), array_values(self::CATEGORIES)) . $rule($letter('status'), ['Aktif', 'Cadangan', 'Rusak']) . '</dataValidations>';
        $zip->addFromString($name, str_replace('</sheetData>', '</sheetData>' . $validations, $xml));
        $zip->close();
    }

    /**
     * The filled rows of the first sheet, with their row numbers in Excel.
     * @return list<array{row: int, values: array<string, string>}>
     */
    public static function read(string $path): array
    {
        $options = new Options();
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true; // keeps the row numbers the same as in Excel
        $reader = new Reader($options);
        try {
            $reader->open($path);
        } catch (Throwable) {
            throw new RuntimeException('File tidak dapat dibaca. Pastikan file Excel .xlsx yang valid.');
        }
        $rows = [];
        $map = null;
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $number => $row) {
                    $cells = array_map([self::class, 'cellText'], $row->toArray());
                    if ($map === null) {
                        if (implode('', $cells) !== '') {
                            $map = self::headerMap($cells);
                        }
                        continue;
                    }
                    $values = [];
                    foreach ($map as $index => $key) {
                        $values[$key] = $cells[$index] ?? '';
                    }
                    if (implode('', $values) === '') {
                        continue;
                    }
                    $rows[] = ['row' => (int) $number, 'values' => $values];
                    if (count($rows) > self::MAX_ROWS) {
                        throw new RuntimeException('Maksimal ' . self::MAX_ROWS . ' perangkat per import.');
                    }
                }
                break; // only the first sheet holds data
            }
        } finally {
            $reader->close();
        }
        if ($map === null) {
            throw new RuntimeException('File kosong. Gunakan template yang disediakan.');
        }
        if ($rows === []) {
            throw new RuntimeException('Belum ada baris perangkat yang diisi.');
        }
        return $rows;
    }

    /** Column index => field, from the header row; a required column that is missing refuses the file. */
    private static function headerMap(array $cells): array
    {
        $byHeader = [];
        foreach (self::COLUMNS as $key => $header) {
            $byHeader[self::plain($header)] = $key;
        }
        $map = [];
        foreach ($cells as $index => $cell) {
            $key = $byHeader[self::plain($cell)] ?? null;
            if ($key !== null) {
                $map[$index] = $key;
            }
        }
        foreach (self::REQUIRED as $key) {
            if (!in_array($key, $map, true)) {
                throw new RuntimeException('Kolom "' . rtrim(self::COLUMNS[$key], ' *') . '" tidak ditemukan. Gunakan template yang disediakan.');
            }
        }
        return $map;
    }

    private static function plain(string $text): string
    {
        return mb_strtolower(trim(str_replace('*', '', $text)));
    }

    private static function cellText(mixed $value): string
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            is_float($value) && floor($value) === $value && abs($value) < 1e15 => (string) (int) $value,
            is_bool($value) => $value ? '1' : '0',
            is_scalar($value) => trim((string) $value),
            default => '',
        };
    }

    /**
     * One row as the input of AssetLedger::register(). Checks what only the form checks; the ledger checks the rest.
     * @param array<string, string> $v
     */
    public static function toDevice(array $v): array
    {
        $get = static fn (string $key): string => trim($v[$key] ?? '');
        $category = self::category($get('category'));
        if ($category === null) {
            throw new RuntimeException($get('category') === '' ? 'Kategori wajib diisi.' : 'Kategori "' . $get('category') . '" tidak dikenal.');
        }
        if ($get('brand') === '' || $get('model') === '') {
            throw new RuntimeException('Isi merek dan model perangkat.');
        }
        $status = mb_strtolower($get('status')) ?: 'aktif';
        if (!in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Status "' . $get('status') . '" tidak dikenal. Pilih Aktif, Cadangan, atau Rusak.');
        }
        $network = !in_array($category, self::NO_NETWORK, true);
        if (in_array($category, self::NEEDS_IP, true) && $get('ip') === '') {
            throw new RuntimeException('IP Address wajib untuk ' . self::CATEGORIES[$category] . '.');
        }
        $date = self::date($get('purchaseDate'));
        if ($date === null) {
            throw new RuntimeException('Tanggal Pembelian "' . $get('purchaseDate') . '" tidak dikenali. Gunakan YYYY-MM-DD atau DD/MM/YYYY.');
        }
        $spec = [];
        foreach (self::SPECS[$category] ?? [] as $key) {
            if ($get($key) !== '') {
                $spec[$key] = $key === 'size' && preg_match('/^\d+([.,]\d+)?$/', $get($key)) === 1 ? str_replace(',', '.', $get($key)) . ' inch' : $get($key);
            }
        }
        if ($category === 'other' && $get('kind') !== '') {
            $spec['kind'] = $get('kind');
        }
        return ['id' => $get('id'), 'autoId' => $get('id') === '', 'category' => $category, 'name' => $get('name'), 'department' => $get('department'), 'pic' => $get('pic'), 'brand' => $get('brand'), 'model' => $get('model'),
            'serial' => $get('serial'), 'ip' => $network ? $get('ip') : '', 'mac' => $network ? $get('mac') : '', 'status' => $status, 'purchaseDate' => $date, 'notes' => $get('notes'), 'spec' => $spec];
    }

    private static function category(string $text): ?string
    {
        $plain = str_replace(' ', '', mb_strtolower($text));
        foreach (self::CATEGORIES as $key => $name) {
            if ($plain === str_replace(' ', '', mb_strtolower($name))) {
                return $key;
            }
        }
        return self::CATEGORY_ALIASES[$plain] ?? null;
    }

    /** YYYY-MM-DD of a date as typed, '' for none, null when it is not a date. */
    private static function date(string $text): ?string
    {
        if ($text === '') {
            return '';
        }
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $m) === 1) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{4})$#', $text, $m) === 1) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }
        return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
    }

    /**
     * Registers the rows one by one: a broken rule goes to that row's error, and later rows see the earlier ones
     * (the same barcode twice in the file is caught).
     * @return list<array{row: int, id: string, name: string, category: string, department: string, error: string}>
     */
    public static function apply(AssetLedger $ledger, array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $values = $row['values'];
            try {
                $device = $ledger->register(self::toDevice($values));
                $result[] = ['row' => $row['row'], 'id' => $device['id'], 'name' => $device['name'], 'category' => self::CATEGORIES[$device['category']], 'department' => $device['department'], 'error' => ''];
            } catch (RuntimeException $exception) {
                $result[] = ['row' => $row['row'], 'id' => trim($values['id'] ?? ''), 'name' => trim($values['name'] ?? ''), 'category' => trim($values['category'] ?? ''), 'department' => trim($values['department'] ?? ''), 'error' => $exception->getMessage()];
            }
        }
        return $result;
    }
}
