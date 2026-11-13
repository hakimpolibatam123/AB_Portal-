<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AssetAttention;
use App\Services\Attention;
use PHPUnit\Framework\TestCase;

final class AttentionTest extends TestCase
{
    public function testReasonsNameEveryAnswerWithARedOption(): void
    {
        $definition = ['questions' => [
            ['id' => 'title', 'type' => 'section', 'title' => 'Bagian'],
            ['id' => 'cpu', 'type' => 'radio', 'title' => 'Kondisi CPU', 'options' => [['value' => 'ok', 'label' => 'Baik'], ['value' => 'bad', 'label' => 'Tidak baik', 'tone' => 'red']]],
            ['id' => 'cam', 'type' => 'checkbox', 'title' => 'Masalah', 'options' => ['Offline', ['value' => 'Buram', 'label' => 'Buram', 'tone' => 'red']]],
            ['id' => 'note', 'type' => 'short', 'title' => 'Catatan'],
            ['id' => 'ok', 'type' => 'confirm', 'title' => 'Saya menyatakan'],
        ]];
        $answers = ['cpu' => 'bad', 'cam' => ['Offline', 'Buram'], 'note' => 'teks', 'ok' => true];
        self::assertSame(['Kondisi CPU: Tidak baik', 'Masalah: Offline, Buram'], Attention::reasons($definition, $answers));
        self::assertSame([], Attention::reasons($definition, ['cpu' => 'ok', 'cam' => ['Offline']]));
        self::assertSame([], Attention::reasons([], $answers), 'a version without questions names nothing');
    }

    public function testAServiceWaitsFromTheStartOfItsLastSparePartsRun(): void
    {
        $service = ['createdAt' => '2026-11-01T01:00:00.000Z', 'log' => [
            ['at' => '2026-11-01T01:00:00.000Z', 'status' => 'reported'],
            ['at' => '2026-11-02T02:00:00.000Z', 'status' => 'parts'],
            ['at' => '2026-11-02T05:00:00.000Z', 'status' => 'progress'],
            ['at' => '2026-11-03T03:00:00.000Z', 'status' => 'parts'],
            ['at' => '2026-11-03T08:00:00.000Z', 'status' => 'parts'], // a note while still waiting
        ]];
        self::assertSame('2026-11-03T03:00:00.000Z', AssetAttention::since($service), 'waiting again is a new wait; a note keeps it');
        self::assertSame('2026-11-01T01:00:00.000Z', AssetAttention::since(['createdAt' => '2026-11-01T01:00:00.000Z', 'log' => []]));
        self::assertSame('2026-11-03 03:00:00', AssetAttention::utc('2026-11-03T03:00:00.000Z'));
    }

    public function testAWaitingServiceOpensItsProgresServisPageInTheBell(): void
    {
        $item = Attention::serviceItem(['id' => 'SRV-2026-0001', 'deviceId' => 'ITEM-00049898', 'deviceName' => 'Stand Holder TV', 'type' => 'Perbaikan',
            'problem' => 'Patah bracket', 'technician' => 'IT botania', 'note' => 'Pesan bracket baru', 'since' => '2026-11-03T03:00:00.000Z', 'read' => false]);
        self::assertSame('service', $item['kind']);
        self::assertSame('Aset IT · Menunggu Suku Cadang', $item['form']);
        self::assertSame(['Stand Holder TV (ITEM-00049898) — Perbaikan: Patah bracket', 'Pesan bracket baru'], $item['reasons']);
        self::assertStringEndsWith('/aset/progres-servis?id=SRV-2026-0001', $item['url']);
        self::assertSame('2026-11-03 03:00:00', $item['sort']);
    }
}
