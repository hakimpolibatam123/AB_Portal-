<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DataSnapshot;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DataSnapshotTest extends TestCase
{
    public function testSensitiveTablesNeverReachTheRepository(): void
    {
        foreach (['users', 'app_settings', 'login_attempts', 'audit_logs', 'attention_reads', 'asset_attention_reads', 'asset_service_alerts', 'schema_migrations'] as $table) {
            self::assertNotContains($table, DataSnapshot::TABLES);
        }
    }

    public function testValuesSurviveARoundTrip(): void
    {
        $tables = ['forms' => ['columns' => ['id', 'slug', 'note'], 'rows' => [
            [1, 'a;b', null],
            [2, "baris satu;\nbaris dua", 'Gedung "Utama" /é'],
        ]]];
        self::assertSame($tables, DataSnapshot::decode(DataSnapshot::encode($tables)));
    }

    public function testEachRowSitsOnItsOwnLine(): void
    {
        $json = DataSnapshot::encode(['forms' => ['columns' => ['id'], 'rows' => [[1], [2]]], 'attachments' => ['columns' => ['id'], 'rows' => []]]);
        self::assertStringContainsString("        [1],\n        [2]\n", $json);
        self::assertStringContainsString('"rows": []', $json);
    }

    public function testATableOutsideTheListIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        DataSnapshot::decode(DataSnapshot::encode(['users' => ['columns' => ['id'], 'rows' => [[1]]]]));
    }

    public function testAnUnsafeColumnNameIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        DataSnapshot::decode(DataSnapshot::encode(['forms' => ['columns' => ['id`) --'], 'rows' => [[1]]]]));
    }

    public function testARowOfTheWrongLengthIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        DataSnapshot::decode(DataSnapshot::encode(['forms' => ['columns' => ['id', 'slug'], 'rows' => [[1]]]]));
    }

    public function testBrokenJsonIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        DataSnapshot::decode('{bukan json');
    }
}
