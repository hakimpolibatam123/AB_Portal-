<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Clearance;
use App\Services\ClearanceExcel;
use App\Services\ClearancePdf;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ClearanceTest extends TestCase
{
    private const NOW = '2026-11-12T01:00:00.000Z';

    private function input(array $extra = []): array
    {
        return $extra + [
            'name' => '  Putri  Ayu ', 'nik' => '12345', 'position' => 'Admission', 'resignDate' => '2026-11-30',
            'it' => [
                'access' => ['sirs' => 'ok', 'door_loker' => 'issue', 'bogus' => 'ok', 'emedrec' => 'maybe'],
                'notes' => ['door_loker' => '  kartu   belum kembali ', 'bogus' => 'x', 'sirs' => str_repeat('panjang ', 20)],
                'sign' => ['file' => ['id' => 7], 'name' => 'Ka IT A'],
            ],
        ];
    }

    public function testNormalizeKeepsOnlyTheEmployeeAndTheUnitItPart(): void
    {
        $r = Clearance::normalize($this->input(), null, 'CLR-2026-0001', 'Admin IT', self::NOW, [7 => 'ttd.png']);
        self::assertSame('Putri Ayu', $r['name']);
        self::assertSame(array_keys(Clearance::IT_ACCESS), array_keys($r['it']['access']));
        self::assertSame(array_keys(Clearance::IT_ACCESS), array_keys($r['it']['notes']));
        self::assertSame(['ok', '', 'issue'], [$r['it']['access']['sirs'], $r['it']['access']['emedrec'], $r['it']['access']['door_loker']]);
        self::assertSame('kartu belum kembali', $r['it']['notes']['door_loker']);
        self::assertSame(Clearance::NOTE_MAX, mb_strlen($r['it']['notes']['sirs']), 'a note per access is one short line');
        self::assertSame(['id' => 7, 'name' => 'ttd.png'], $r['it']['sign']['file']);
        self::assertSame(['Ka IT A', self::NOW], [$r['it']['sign']['name'], $r['it']['sign']['at']]);
        self::assertArrayNotHasKey('divisions', $r);
        self::assertSame(['Admin IT', self::NOW], [$r['createdBy'], $r['createdAt']]);
    }

    public function testRulesNameDateAndSignatureFile(): void
    {
        $this->expectRule('Nama karyawan wajib', fn () => Clearance::normalize(['name' => '  '], null, 'X', 'A', self::NOW, []));
        $this->expectRule('Tanggal', fn () => Clearance::normalize(['name' => 'B', 'resignDate' => '2026-02-30'], null, 'X', 'A', self::NOW, []));
        // A file that is not an uploaded clearance signature is refused.
        $this->expectRule('tanda tangan', fn () => Clearance::normalize($this->input(), null, 'X', 'A', self::NOW, []));
        self::assertNull(Clearance::normalize(['name' => 'B'], null, 'X', 'A', self::NOW, [])['it']['sign']);
    }

    public function testTheSignatureCanBeChangedRenamedOrRemoved(): void
    {
        $first = Clearance::normalize($this->input(), null, 'CLR-2026-0001', 'Admin IT', self::NOW, [7 => 'ttd.png']);
        $later = '2026-11-20T03:00:00.000Z';
        $in = $this->input();
        $in['it']['sign']['name'] = 'Ka IT B';
        $same = Clearance::normalize($in, $first, 'CLR-2026-0001', 'Hakim', $later, []);
        self::assertSame([self::NOW, 'Ka IT B'], [$same['it']['sign']['at'], $same['it']['sign']['name']], 'same file: signed at stays');
        self::assertSame([self::NOW, 'Admin IT', $later, 'Hakim'], [$same['createdAt'], $same['createdBy'], $same['updatedAt'], $same['updatedBy']]);
        $in['it']['sign']['file']['id'] = 9;
        $changed = Clearance::normalize($in, $first, 'CLR-2026-0001', 'Hakim', $later, [9 => 'baru.png']);
        self::assertSame([9, $later], [$changed['it']['sign']['file']['id'], $changed['it']['sign']['at']]);
        $in['it']['sign'] = null;
        self::assertNull(Clearance::normalize($in, $first, 'CLR-2026-0001', 'Hakim', $later, [])['it']['sign']);
    }

    public function testTheEmployeeSignsToo(): void
    {
        $in = $this->input(['employeeSign' => ['file' => ['id' => 8], 'name' => 'nama lain']]);
        $r = Clearance::normalize($in, null, 'CLR-2026-0001', 'Admin IT', self::NOW, [7 => 'ttd.png', 8 => 'ttd-karyawan.png']);
        self::assertSame(['id' => 8, 'name' => 'ttd-karyawan.png'], $r['employeeSign']['file']);
        self::assertSame(['Putri Ayu', self::NOW], [$r['employeeSign']['name'], $r['employeeSign']['at']], 'the name is the employee\'s own');
        self::assertNull(Clearance::normalize($this->input(), null, 'X', 'A', self::NOW, [7 => 'ttd.png'])['employeeSign'], 'optional');
        $this->expectRule('tanda tangan', fn () => Clearance::normalize($in, null, 'X', 'A', self::NOW, [7 => 'ttd.png']));
        $later = Clearance::normalize($in, $r, 'CLR-2026-0001', 'Hakim', '2026-11-20T03:00:00.000Z', [7 => 'ttd.png']);
        self::assertSame(self::NOW, $later['employeeSign']['at'], 'same file: signed at stays');
    }

    public function testTheExportFollowsTheFilters(): void
    {
        $a = Clearance::normalize($this->input(), null, 'CLR-2026-0001', 'A', self::NOW, [7 => 'ttd.png']); // 30 Nov, signed, an issue
        $b = Clearance::normalize(['name' => 'Budi', 'resignDate' => '2026-10-05'], null, 'CLR-2026-0002', 'A', self::NOW, []);
        $c = Clearance::normalize(['name' => 'Citra'], null, 'CLR-2026-0003', 'A', self::NOW, []);
        $ids = static fn (array $q): array => array_column(Clearance::filter([$a, $b, $c], $q), 'id');
        self::assertSame(['CLR-2026-0001', 'CLR-2026-0002', 'CLR-2026-0003'], $ids([]));
        self::assertSame(['CLR-2026-0001'], $ids(['dari' => '2026-11-01', 'sampai' => '2026-11-30']));
        self::assertSame(['CLR-2026-0002'], $ids(['sampai' => '2026-10-31']), 'no resignation date: left out once a date is set');
        self::assertSame(['CLR-2026-0002', 'CLR-2026-0003'], $ids(['status' => 'unsigned']));
        self::assertSame(['CLR-2026-0001'], $ids(['status' => 'issue']));
        self::assertSame(['CLR-2026-0003'], $ids(['q' => 'CITRA']));
        self::assertSame(['CLR-2026-0001', 'CLR-2026-0002', 'CLR-2026-0003'], $ids(['status' => 'bogus', 'dari' => '2026-02-30']), 'unknown values do not filter');
        self::assertSame('Semua karyawan', Clearance::filterText([]));
        self::assertSame('Tgl. pengunduran diri 1 Nov 2026 – 30 Nov 2026 · Belum TTD Ka. Unit IT',
            Clearance::filterText(['dari' => '2026-11-01', 'sampai' => '2026-11-30', 'status' => 'unsigned']));
    }

    public function testNextIdCountsPerYear(): void
    {
        self::assertSame('CLR-2026-0001', Clearance::nextId([], '2026'));
        self::assertSame('CLR-2026-0004', Clearance::nextId(['CLR-2026-0003', 'CLR-2025-0009', 'CLR-2026-0001'], '2026'));
    }

    public function testProgressOfTheItPart(): void
    {
        $r = Clearance::normalize($this->input(), null, 'X', 'A', self::NOW, [7 => 'ttd.png']);
        self::assertSame('Ada akses bermasalah', Clearance::progress($r)['label']);
        $r['it']['access']['door_loker'] = 'ok';
        self::assertSame('Sudah TTD IT', Clearance::progress($r)['label']);
        $r['it']['sign'] = null;
        self::assertSame('Belum TTD IT', Clearance::progress($r)['label']);
    }

    public function testPdfIsARecapOfEveryEmployee(): void
    {
        $r = Clearance::normalize($this->input(), null, 'CLR-2026-0001', 'A', self::NOW, [7 => 'ttd.png']);
        $pdf = ClearancePdf::render([$r, ['id' => 'CLR-2026-0002', 'name' => 'Budi'] + $r], static fn (): ?string => null, '12 Nov 2026, 08:00 WIB');
        self::assertStringStartsWith('%PDF', $pdf);
        self::assertSame(1, preg_match_all('#/Type /Page\b(?!s)#', $pdf));
        // 40 employees run over several pages (header and table head repeat); none is still one page.
        self::assertGreaterThan(1, preg_match_all('#/Type /Page\b(?!s)#', ClearancePdf::render(array_fill(0, 40, $r), static fn (): ?string => null, 'x')));
        self::assertSame(1, preg_match_all('#/Type /Page\b(?!s)#', ClearancePdf::render([], static fn (): ?string => null, 'x')));
    }

    public function testExcelHasOneRowPerEmployeeAndTheTotals(): void
    {
        $r = Clearance::normalize($this->input(), null, 'CLR-2026-0002', 'A', self::NOW, [7 => 'ttd.png']);
        $path = sys_get_temp_dir() . '/clearance-test-' . bin2hex(random_bytes(4)) . '.xlsx';
        ClearanceExcel::write([$r, ['id' => 'CLR-2026-0001', 'name' => 'Budi', 'it' => []]], $path, '12 Nov 2026, 08:00 WIB');
        $rows = [];
        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($path);
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[$sheet->getName()][] = $row->toArray();
            }
        }
        $reader->close();
        @unlink($path);
        self::assertCount(3, $rows['Data'], 'header + one row per employee');
        self::assertSame(['Budi', 'Putri Ayu'], [$rows['Data'][1][2], $rows['Data'][2][2]], 'sorted by number');
        $col = array_flip($rows['Data'][0]);
        self::assertSame(['Belum diperiksa', 'Bermasalah'], [$rows['Data'][1][$col['Akses Door Loker']], $rows['Data'][2][$col['Akses Door Loker']]]);
        self::assertSame('Akses Door Loker: kartu belum kembali', explode("\n", $rows['Data'][2][$col['Keterangan']])[1]);
        self::assertSame(['Belum ditandatangani', 'Ka IT A'], [$rows['Data'][1][$col['TTD Ka. Unit IT']], $rows['Data'][2][$col['TTD Ka. Unit IT']]]);
        self::assertInstanceOf(\DateTimeImmutable::class, $rows['Data'][2][$col['Tgl. Pengunduran Diri']]);
        self::assertContains(['Ada akses bermasalah', 1], $rows['Ringkasan']);
        self::assertSame(['Belum ditandatangani', 'Belum ditandatangani'], [$rows['Data'][1][$col['TTD Karyawan']], $rows['Data'][2][$col['TTD Karyawan']]]);
        self::assertContains(['Sudah TTD Karyawan', 0], $rows['Ringkasan']);
    }

    private function expectRule(string $message, callable $action): void
    {
        try {
            $action();
            self::fail('Expected: ' . $message);
        } catch (RuntimeException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
    }
}
