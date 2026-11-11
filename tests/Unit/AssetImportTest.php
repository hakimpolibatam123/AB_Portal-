<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\AssetImport;
use App\Services\AssetLedger;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AssetImportTest extends TestCase
{
    private const HEADERS = ['Barcode / ID Aset', 'Kategori *', 'Nama Perangkat *', 'Departemen *', 'Merek *', 'Model *', 'IP Address', 'Status', 'Tanggal Pembelian', 'Serial Number', 'Ukuran Layar'];

    private function file(array $rows, array $headers = self::HEADERS): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($headers));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();
        return $path;
    }

    private function device(array $over = []): array
    {
        return $over + ['category' => 'Laptop', 'name' => 'Laptop A', 'department' => 'IT', 'brand' => 'Dell', 'model' => 'X1'];
    }

    #[RequiresPhpExtension('zip')]
    public function testTheTemplateReadsBackAsEmptyAndHasItsDropdowns(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        AssetImport::template($path);
        $zip = new \ZipArchive();
        $zip->open($path);
        $sheet = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        self::assertStringContainsString('<dataValidations count="2">', $sheet);
        self::assertMatchesRegularExpression('/<col [^>]*style="\d+"/', $sheet); // text columns
        self::assertStringContainsString('numFmtId="49"', (string) $zip->getFromName('xl/styles.xml'));
        $zip->close();
        try {
            AssetImport::read($path);
            self::fail('An empty template must be refused.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('Belum ada baris', $exception->getMessage());
        }
    }

    #[RequiresPhpExtension('zip')]
    public function testAFileWithoutTheColumnsIsRefused(): void
    {
        $this->expectExceptionMessage('tidak ditemukan');
        AssetImport::read($this->file([['a']], ['Nama', 'Harga']));
    }

    #[RequiresPhpExtension('zip')]
    public function testRowsAreReadWithTheirExcelRowNumbers(): void
    {
        $rows = AssetImport::read($this->file([['ITEM-00000001', 'Laptop', 'A', 'IT', 'Dell', 'X1', '', '', '', 123456789012.0, ''], ['', '', '', '', '', '', '', '', '', '', ''], ['', 'UPS', 'B', 'IT', 'APC', 'Z', '', '', '', '', '']]));
        self::assertSame([2, 4], array_column($rows, 'row'));
        self::assertSame('123456789012', $rows[0]['values']['serial']);
    }

    public function testToDeviceMapsAndChecksTheRow(): void
    {
        $d = AssetImport::toDevice($this->device(['category' => 'pc desktop', 'ip' => '10.0.0.1', 'status' => 'Cadangan', 'purchaseDate' => '31/01/2026', 'hostname' => 'H1']));
        self::assertSame(['pc', true, 'cadangan', '2026-01-31', ['hostname' => 'H1']], [$d['category'], $d['autoId'], $d['status'], $d['purchaseDate'], $d['spec']]);
        $monitor = AssetImport::toDevice($this->device(['category' => 'Monitor', 'ip' => '10.0.0.2', 'size' => '23,8']));
        self::assertSame(['', ['size' => '23.8 inch']], [$monitor['ip'], $monitor['spec']]);
        foreach ([['category' => 'Kulkas'], ['brand' => ''], ['status' => 'Hilang'], ['purchaseDate' => '31 Jan'], ['category' => 'Server']] as $bad) {
            try {
                AssetImport::toDevice($bad + $this->device());
                self::fail('Must be refused: ' . json_encode($bad));
            } catch (RuntimeException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testApplyKeepsTheValidRowsAndReportsTheOthers(): void
    {
        $ledger = new AssetLedger([], 'Admin IT', '2026-11-04T03:00:00.000Z');
        $row = fn (int $n, array $v): array => ['row' => $n, 'values' => $v];
        $result = AssetImport::apply($ledger, [
            $row(2, $this->device(['id' => 'ITEM-00000001'])),
            $row(3, $this->device(['id' => 'ITEM-00000001', 'name' => 'Kembar'])),
            $row(4, $this->device(['name' => 'Tanpa Barcode'])),
            $row(5, $this->device(['name' => ''])),
        ]);
        self::assertSame(['', 'Barcode ITEM-00000001 sudah terdaftar.', '', 'Nama perangkat wajib diisi.'], array_column($result, 'error'));
        self::assertSame('AB-BTN-LTP-0001', $result[2]['id']);
        self::assertCount(2, $ledger->changes()['devices']);
    }
}
