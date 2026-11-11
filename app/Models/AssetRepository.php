<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * The Aset IT tables (migration 2026_11_10_0015) as the state App\Services\AssetLedger and aset-store.js work on:
 * devices keyed by barcode; moves, services, disposals and activity as lists, oldest first; seq holds the highest
 * number used so far for each kind of id, so the next id follows on.
 */
final class AssetRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function load(bool $forUpdate = false): array
    {
        $lock = $forUpdate ? ' FOR UPDATE' : '';
        $devices = [];
        foreach ($this->pdo->query('SELECT * FROM asset_devices ORDER BY created_at, id' . $lock)->fetchAll() as $row) {
            $devices[$row['id']] = [
                'id' => $row['id'], 'name' => $row['name'], 'department' => (string) ($row['department'] ?? ''), 'category' => $row['category'], 'brand' => $row['brand'], 'model' => $row['model'],
                'serial' => $row['serial'], 'ip' => $row['ip'], 'mac' => $row['mac'], 'location' => self::json($row['location_json']) + ['building' => '', 'floor' => '', 'dept' => '', 'room' => ''],
                'pic' => $row['pic'], 'status' => $row['status'], 'purchaseDate' => $row['purchase_date'], 'warrantyEnd' => $row['warranty_end'],
                'notes' => $row['notes'], 'spec' => self::json($row['spec_json']), 'invoices' => array_values(self::json($row['invoices_json'] ?? null)), 'disposedAt' => $row['disposed_at'], 'disposalId' => $row['disposal_id'],
                'createdAt' => $row['created_at'], 'createdBy' => $row['created_by'], 'updatedAt' => $row['updated_at'], 'updatedBy' => $row['updated_by'],
            ];
        }
        $moves = array_map(static fn (array $r): array => self::json($r['data_json']), $this->pdo->query('SELECT data_json FROM asset_moves ORDER BY at, id' . $lock)->fetchAll());
        $services = array_map(static fn (array $r): array => self::json($r['data_json']), $this->pdo->query('SELECT data_json FROM asset_services ORDER BY created_at, id' . $lock)->fetchAll());
        $disposals = array_map(static fn (array $r): array => self::json($r['data_json']), $this->pdo->query('SELECT data_json FROM asset_disposals ORDER BY requested_at, id' . $lock)->fetchAll());
        $handovers = array_map(static fn (array $r): array => self::json($r['data_json']), $this->pdo->query('SELECT data_json FROM asset_handovers ORDER BY created_at, id' . $lock)->fetchAll());
        $activity = array_map(static fn (array $r): array => ['id' => 'A' . $r['id'], 'deviceId' => $r['device_id'], 'type' => $r['type'], 'text' => $r['text'], 'at' => $r['at'], 'by' => $r['by_name']],
            $this->pdo->query('SELECT * FROM asset_activity ORDER BY id' . $lock)->fetchAll());
        $max = static function (array $ids): int {
            $n = 0;
            foreach ($ids as $id) {
                if (preg_match('/(\d+)$/', (string) $id, $m) === 1) {
                    $n = max($n, (int) $m[1]);
                }
            }
            return $n;
        };
        return [
            'v' => 1,
            'seq' => ['srv' => $max(array_column($services, 'id')), 'dsp' => $max(array_column($disposals, 'id')), 'mv' => $max(array_column($moves, 'id')), 'act' => $max(array_column($activity, 'id')), 'bast' => $max(array_column($handovers, 'id'))],
            'devices' => $devices, 'moves' => $moves, 'services' => $services, 'disposals' => $disposals, 'handovers' => $handovers, 'activity' => $activity,
        ];
    }

    /** Stores what AssetLedger::changes() lists; the caller holds the transaction. */
    public function save(array $changes): void
    {
        $device = $this->pdo->prepare('INSERT INTO asset_devices (id, name, department, category, brand, model, serial, ip, mac, location_json, pic, status, purchase_date, warranty_end, notes, spec_json, invoices_json, disposed_at, disposal_id, created_at, created_by, updated_at, updated_by)
            VALUES (:id, :name, :department, :category, :brand, :model, :serial, :ip, :mac, :location, :pic, :status, :purchase, :warranty, :notes, :spec, :invoices, :disposed_at, :disposal_id, :created_at, :created_by, :updated_at, :updated_by)
            ON DUPLICATE KEY UPDATE name = VALUES(name), department = VALUES(department), category = VALUES(category), brand = VALUES(brand), model = VALUES(model), serial = VALUES(serial), ip = VALUES(ip), mac = VALUES(mac),
            location_json = VALUES(location_json), pic = VALUES(pic), status = VALUES(status), purchase_date = VALUES(purchase_date), warranty_end = VALUES(warranty_end), notes = VALUES(notes),
            spec_json = VALUES(spec_json), invoices_json = VALUES(invoices_json), disposed_at = VALUES(disposed_at), disposal_id = VALUES(disposal_id), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)');
        foreach ($changes['devices'] ?? [] as $d) {
            $device->execute([
                'id' => $d['id'], 'name' => $d['name'], 'department' => $d['department'] ?? '', 'category' => $d['category'], 'brand' => $d['brand'] ?? '', 'model' => $d['model'] ?? '',
                'serial' => $d['serial'] ?? '', 'ip' => $d['ip'] ?? '', 'mac' => $d['mac'] ?? '', 'location' => self::encode($d['location'] ?? []),
                'pic' => $d['pic'] ?? '', 'status' => $d['status'], 'purchase' => $d['purchaseDate'] ?? '', 'warranty' => $d['warrantyEnd'] ?? '',
                'notes' => $d['notes'] ?? '', 'spec' => self::encode($d['spec'] ?? []),
                'invoices' => (string) json_encode(array_values($d['invoices'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), 'disposed_at' => $d['disposedAt'] ?? '', 'disposal_id' => $d['disposalId'] ?? '',
                'created_at' => $d['createdAt'], 'created_by' => $d['createdBy'] ?? '', 'updated_at' => $d['updatedAt'], 'updated_by' => $d['updatedBy'] ?? '',
            ]);
        }
        $move = $this->pdo->prepare('INSERT INTO asset_moves (id, device_id, data_json, at) VALUES (?, ?, ?, ?)');
        foreach ($changes['moves'] ?? [] as $m) {
            $move->execute([$m['id'], $m['deviceId'], self::encode($m), $m['at']]);
        }
        $service = $this->pdo->prepare('INSERT INTO asset_services (id, device_id, status, data_json, created_at) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), data_json = VALUES(data_json)');
        foreach ($changes['services'] ?? [] as $t) {
            $service->execute([$t['id'], $t['deviceId'], $t['status'], self::encode($t), $t['createdAt']]);
        }
        $disposal = $this->pdo->prepare('INSERT INTO asset_disposals (id, device_id, status, data_json, requested_at) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), data_json = VALUES(data_json)');
        foreach ($changes['disposals'] ?? [] as $r) {
            $disposal->execute([$r['id'], $r['deviceId'], $r['status'], self::encode($r), $r['requestedAt']]);
        }
        $handover = $this->pdo->prepare('INSERT INTO asset_handovers (id, data_json, created_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE data_json = VALUES(data_json)');
        foreach ($changes['handovers'] ?? [] as $h) {
            $handover->execute([$h['id'], self::encode($h), $h['createdAt']]);
        }
        $activity = $this->pdo->prepare('INSERT INTO asset_activity (device_id, type, text, at, by_name) VALUES (?, ?, ?, ?, ?)');
        foreach ($changes['activity'] ?? [] as $a) {
            $activity->execute([$a['deviceId'], $a['type'], $a['text'], $a['at'], $a['by']]);
        }
    }

    private static function json(?string $value): array
    {
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function encode(array $value): string
    {
        return (string) json_encode($value === [] ? new \stdClass() : $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
