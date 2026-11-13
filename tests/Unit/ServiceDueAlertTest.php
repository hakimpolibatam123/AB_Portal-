<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Mailer;
use App\Services\ServiceDueAlert;
use PHPUnit\Framework\TestCase;

final class ServiceDueAlertTest extends TestCase
{
    private static function svc(string $id, string $due, string $status = 'progress'): array
    {
        return ['id' => $id, 'deviceId' => 'ITEM-0000000' . substr($id, -1), 'deviceName' => 'PC <' . $id . '>', 'type' => 'Perbaikan',
            'technician' => 'Vendor A', 'status' => $status, 'dueDate' => $due];
    }

    public function testRemindsOpenServicesDueTomorrowOrEarlierOncePerDeadline(): void
    {
        $services = [
            self::svc('SRV-1', '2026-11-06'),            // tomorrow (H-1)
            self::svc('SRV-2', '2026-11-07'),            // H-2: not yet
            self::svc('SRV-3', '2026-11-04'),            // already late, never reminded
            self::svc('SRV-4', '2026-11-06', 'done'),    // closed
            self::svc('SRV-5', ''),                      // no deadline
            self::svc('SRV-6', '2026-11-05'),            // today, already reminded for this deadline
        ];
        $due = ServiceDueAlert::due($services, '2026-11-05', ['SRV-6|2026-11-05' => true]);
        self::assertSame(['SRV-3', 'SRV-1'], array_column($due, 'id'));
        // A moved deadline is a new reminder.
        self::assertSame(['SRV-6'], array_column(ServiceDueAlert::due([self::svc('SRV-6', '2026-11-06')], '2026-11-05', ['SRV-6|2026-11-05' => true]), 'id'));
    }

    public function testEmailNamesTheDeadlineEscapesTextAndLinksToTheService(): void
    {
        self::assertSame('besok', ServiceDueAlert::when('2026-11-06', '2026-11-05'));
        self::assertSame('hari ini', ServiceDueAlert::when('2026-11-05', '2026-11-05'));
        self::assertSame('lewat 2 hari', ServiceDueAlert::when('2026-11-03', '2026-11-05'));
        $mail = ServiceDueAlert::email([self::svc('SRV-1', '2026-11-06')], '2026-11-05', 'http://localhost/AB-Portal');
        self::assertSame('Pengingat tenggat vendor besok: PC <SRV-1> (SRV-1)', $mail['subject']);
        self::assertStringContainsString('PC &lt;SRV-1&gt;', $mail['html']);
        self::assertStringContainsString('6 Nov 2026', $mail['html']);
        self::assertStringContainsString('http://localhost/AB-Portal/aset/progres-servis?id=SRV-1', $mail['text']);
        self::assertSame('Pengingat: 2 servis mendekati tenggat vendor',
            ServiceDueAlert::email([self::svc('SRV-1', '2026-11-06'), self::svc('SRV-2', '2026-11-05')], '2026-11-05', '')['subject']);
    }

    public function testRecipientListKeepsValidUniqueAddresses(): void
    {
        self::assertSame(['it@rs.id', 'kepala@rs.id'], Mailer::addresses('it@rs.id, kepala@rs.id;IT@rs.id bukan-email'));
        self::assertFalse((new Mailer(['host' => '', 'from_address' => '']))->configured());
    }
}
