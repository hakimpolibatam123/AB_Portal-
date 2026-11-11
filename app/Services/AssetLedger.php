<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Aset IT (IT Asset Management): the rules of public/assets/aset/aset-store.js, checked again on the server.
 * The browser runs each operation on its copy for an immediate answer and sends the list of operations; the
 * server replays them here on the state read from the database (App\Models\AssetRepository), as the signed-in
 * account and at the server's clock, and stores only what they changed. The state keeps the browser's shape:
 * devices keyed by barcode, moves, services, disposals and activity as lists.
 *
 * Rules (ASET-IT-README): an open service sets the device to "servis" and its end status is chosen when done; a
 * disposal is done in one step and sets "disposed" for good (nothing is hard-deleted); cancelling an older open
 * request restores the previous status. One open service per device; no disposal while a service is open.
 * Barcodes are unique; IP, MAC and serial are not used by another active device.
 */
final class AssetLedger
{
    public const CATEGORIES = ['pc', 'minipc', 'laptop', 'monitor', 'printer', 'scanner', 'cctv', 'network', 'server', 'ups', 'other'];
    public const STATUS = ['aktif' => 'Aktif', 'cadangan' => 'Cadangan', 'rusak' => 'Rusak', 'servis' => 'Dalam Servis', 'disposal' => 'Proses Disposal', 'disposed' => 'Disposed'];
    public const SVC_STATUS = ['reported' => 'Dilaporkan', 'progress' => 'Dalam Proses', 'parts' => 'Menunggu Suku Cadang', 'done' => 'Selesai', 'rejected' => 'Ditolak'];
    /** A service ends as done or rejected (e.g. warranty claim refused, with its berita acara); both free the device. */
    public const SVC_CLOSED = ['done', 'rejected'];
    private const PREFIX = ['pc' => 'PC', 'minipc' => 'MPC', 'laptop' => 'LTP', 'monitor' => 'MON', 'printer' => 'PRN', 'scanner' => 'SCN', 'cctv' => 'CCTV', 'network' => 'NET', 'server' => 'SRV', 'ups' => 'UPS', 'other' => 'OTH'];
    private const FIELD_LABEL = ['name' => 'Nama', 'department' => 'Departemen', 'category' => 'Kategori', 'brand' => 'Merek', 'model' => 'Model', 'serial' => 'Serial Number', 'ip' => 'IP Address', 'mac' => 'MAC Address', 'pic' => 'Penanggung Jawab', 'purchaseDate' => 'Tanggal Pembelian', 'warrantyEnd' => 'Garansi', 'notes' => 'Catatan'];
    /** Accepted asset codes (same list as CODE_FORMATS in aset-store.js). */
    private const CODE_FORMAT = '/^(ITEM-\d{8}|AB-BTN-(PC|MPC|LTP|MON|PRN|SCN|CCTV|NET|SRV|UPS|OTH)-\d{4})$/';
    /** Operations the browser may send, with how many arguments each takes. */
    public const OPERATIONS = ['register' => 1, 'update' => 2, 'place' => 2, 'move' => 2, 'addService' => 1, 'updateService' => 2, 'dispose' => 1, 'rejectDisposal' => 2, 'handover' => 1, 'signHandover' => 2];

    /** @var array<string, true> */
    private array $touchedDevices = [];
    /** @var array<string, true> */
    private array $touchedServices = [];
    /** @var array<string, true> */
    private array $touchedDisposals = [];
    private array $touchedHandovers = [];
    private int $movesBefore;
    private int $activityBefore;

    /**
     * $files: the uploaded asset files the operations may attach (attachment id => [name, type, size], read by
     * the controller from the attachments table), so a document list only ever holds files that exist.
     */
    public function __construct(private array $state, private readonly string $by, private readonly string $now, private readonly array $files = [])
    {
        $this->state += ['devices' => [], 'moves' => [], 'services' => [], 'disposals' => [], 'handovers' => [], 'activity' => [], 'seq' => []];
        $this->state['seq'] += ['srv' => 0, 'dsp' => 0, 'mv' => 0, 'act' => 0, 'bast' => 0];
        $this->movesBefore = count($this->state['moves']);
        $this->activityBefore = count($this->state['activity']);
    }

    public function state(): array
    {
        return $this->state;
    }

    /** What the operations changed, for the repository. */
    public function changes(): array
    {
        return [
            'devices' => array_values(array_intersect_key($this->state['devices'], $this->touchedDevices)),
            'moves' => array_slice($this->state['moves'], $this->movesBefore),
            'services' => array_values(array_filter($this->state['services'], fn (array $s): bool => isset($this->touchedServices[$s['id']]))),
            'disposals' => array_values(array_filter($this->state['disposals'], fn (array $r): bool => isset($this->touchedDisposals[$r['id']]))),
            'handovers' => array_values(array_filter($this->state['handovers'], fn (array $r): bool => isset($this->touchedHandovers[$r['id']]))),
            'activity' => array_slice($this->state['activity'], $this->activityBefore),
        ];
    }

    /** One operation as the browser sent it ({op, args}); a broken rule throws with its message. */
    public function apply(mixed $command): void
    {
        $op = is_array($command) && is_string($command['op'] ?? null) ? $command['op'] : '';
        $args = is_array($command) && is_array($command['args'] ?? null) ? array_values($command['args']) : [];
        if (!isset(self::OPERATIONS[$op]) || count($args) < self::OPERATIONS[$op]) {
            throw new RuntimeException('Perintah aset tidak dikenal.');
        }
        $a = static fn (int $i): array => is_array($args[$i] ?? null) ? $args[$i] : [];
        $id = static fn (int $i): string => is_string($args[$i] ?? null) ? $args[$i] : '';
        match ($op) {
            'register' => $this->register($a(0)),
            'update' => $this->update($id(0), $a(1)),
            'place' => $this->place($id(0), $a(1)),
            'move' => $this->move($id(0), $a(1)),
            'addService' => $this->addService($a(0)),
            'updateService' => $this->updateService($id(0), $a(1)),
            'dispose' => $this->dispose($a(0)),
            'rejectDisposal' => $this->rejectDisposal($id(0), $a(1)),
            'handover' => $this->handover($a(0)),
            'signHandover' => $this->signHandover(is_string($args[0] ?? null) ? $args[0] : '', $a(1)),
        };
    }

    /* ---------------- devices ---------------- */

    public function register(array $in): array
    {
        $category = self::text($in['category'] ?? '', 20);
        if (!in_array($category, self::CATEGORIES, true)) {
            throw new RuntimeException('Pilih kategori perangkat.');
        }
        // A device without a barcode label (e.g. a laser pointer) gets the portal's next free ID, AB-BTN-<kategori>-NNNN,
        // chosen here so two admins registering at once never get the same one.
        $id = !empty($in['autoId']) ? self::nextId($this->state['devices'], $category) : self::normId($in['id'] ?? '');
        if ($id === '') {
            throw new RuntimeException('Barcode / ID aset wajib diisi.');
        }
        $problem = self::codeProblem($id);
        if ($problem !== null) {
            throw new RuntimeException($problem);
        }
        if (isset($this->state['devices'][$id])) {
            throw new RuntimeException('Barcode ' . $id . ' sudah terdaftar.');
        }
        $status = self::text($in['status'] ?? 'aktif', 20) ?: 'aktif';
        if (!in_array($status, ['aktif', 'cadangan', 'rusak'], true)) {
            throw new RuntimeException('Status awal perangkat tidak valid.');
        }
        $d = [
            'id' => $id, 'name' => self::text($in['name'] ?? '', 150), 'department' => self::text($in['department'] ?? '', 100), 'category' => $category,
            'brand' => self::text($in['brand'] ?? '', 100), 'model' => self::text($in['model'] ?? '', 150), 'serial' => self::text($in['serial'] ?? '', 100),
            'ip' => self::text($in['ip'] ?? '', 45), 'mac' => self::normMac($in['mac'] ?? ''), 'location' => self::location($in['location'] ?? null),
            'pic' => self::text($in['pic'] ?? '', 150), 'status' => $status,
            'purchaseDate' => self::date($in['purchaseDate'] ?? ''), 'warrantyEnd' => self::date($in['warrantyEnd'] ?? ''),
            'notes' => self::text($in['notes'] ?? '', 2000, true), 'spec' => self::spec($in['spec'] ?? null),
            'invoices' => $this->attach($in['invoices'] ?? null, [], 5),
            'createdAt' => $this->now, 'createdBy' => $this->by, 'updatedAt' => $this->now, 'updatedBy' => $this->by,
        ];
        if ($d['name'] === '') {
            throw new RuntimeException('Nama perangkat wajib diisi.');
        }
        if ($d['department'] === '') {
            throw new RuntimeException('Isi departemen perangkat.');
        }
        // A device that fits no category is "Lainnya" with its own type (spec.kind), e.g. Remote Laser.
        if ($category === 'other' && ($d['spec']['kind'] ?? '') === '') {
            throw new RuntimeException('Isi jenis perangkat (kategori Lainnya), mis. Remote Laser.');
        }
        $this->checkNetwork($d, null);
        $this->state['devices'][$id] = $d;
        $this->touchedDevices[$id] = true;
        $this->log($id, 'register', self::placed($d) ? 'Perangkat diregistrasi di ' . self::locText($d['location']) : 'Perangkat diregistrasi');
        return $d;
    }

    public function update(string $id, array $patch): array
    {
        $d = $this->device($id);
        if ($d['status'] === 'disposed') {
            throw new RuntimeException('Perangkat sudah dihapuskan dan tidak dapat diubah.');
        }
        $id = $d['id'];
        $changed = [];
        $limits = ['name' => 150, 'department' => 100, 'category' => 20, 'brand' => 100, 'model' => 150, 'serial' => 100, 'ip' => 45, 'mac' => 17, 'pic' => 150, 'purchaseDate' => 10, 'warrantyEnd' => 10, 'notes' => 2000];
        $next = $d;
        foreach ($limits as $key => $max) {
            if (!array_key_exists($key, $patch)) {
                continue;
            }
            $value = match ($key) {
                'mac' => self::normMac($patch[$key]),
                'purchaseDate', 'warrantyEnd' => self::date($patch[$key]),
                'notes' => self::text($patch[$key], $max, true),
                default => self::text($patch[$key], $max),
            };
            if ($value !== ($d[$key] ?? '')) {
                $changed[] = self::FIELD_LABEL[$key];
                $next[$key] = $value;
            }
        }
        if (!in_array($next['category'], self::CATEGORIES, true)) {
            throw new RuntimeException('Pilih kategori perangkat.');
        }
        if (trim($next['name']) === '') {
            throw new RuntimeException('Nama perangkat wajib diisi.');
        }
        // Devices registered before the question existed have none; an edit that sends it must fill it in.
        if (array_key_exists('department', $patch) && trim((string) ($next['department'] ?? '')) === '') {
            throw new RuntimeException('Isi departemen perangkat.');
        }
        if (array_key_exists('spec', $patch)) {
            $spec = self::spec($patch['spec']);
            // Older "Lainnya" devices have no type; an edit that sends the details must fill it in.
            if ($next['category'] === 'other' && ($spec['kind'] ?? '') === '') {
                throw new RuntimeException('Isi jenis perangkat (kategori Lainnya), mis. Remote Laser.');
            }
            if ($spec != ($d['spec'] ?? [])) {
                $next['spec'] = $spec;
                $changed[] = 'Spesifikasi';
            }
        }
        if (array_key_exists('invoices', $patch)) {
            // The list as sent: invoices kept from before plus new uploads; one left out is removed from the list.
            $invoices = $this->attach($patch['invoices'], $d['invoices'] ?? [], 5);
            if (array_column($invoices, 'id') !== array_column($d['invoices'] ?? [], 'id')) {
                $next['invoices'] = $invoices;
                $changed[] = 'Invoice Pembelian';
            }
        }
        $this->checkNetwork($next, $id);
        $status = is_string($patch['status'] ?? null) ? $patch['status'] : '';
        if ($status !== '' && $status !== $d['status']) {
            if (in_array($d['status'], ['servis', 'disposal', 'disposed'], true)) {
                throw new RuntimeException('Status sedang dikunci oleh proses ' . self::STATUS[$d['status']] . '.');
            }
            if (!in_array($status, ['aktif', 'cadangan', 'rusak'], true)) {
                throw new RuntimeException('Status perangkat tidak valid.');
            }
            $this->log($id, 'status', 'Status diubah: ' . self::STATUS[$d['status']] . ' → ' . self::STATUS[$status]);
            $next['status'] = $status;
        }
        $next['updatedAt'] = $this->now;
        $next['updatedBy'] = $this->by;
        $this->state['devices'][$id] = $next;
        $this->touchedDevices[$id] = true;
        if ($changed !== []) {
            $this->log($id, 'edit', 'Data diperbarui: ' . implode(', ', $changed));
        }
        return $next;
    }

    /** Sets or changes where a device is and who is in charge of it. */
    public function place(string $id, array $in): ?array
    {
        $d = $this->device($id);
        if ($d['status'] === 'disposed') {
            throw new RuntimeException('Perangkat sudah dihapuskan.');
        }
        $id = $d['id'];
        $location = self::location($in['location'] ?? null);
        if ($location['dept'] === '' && $location['room'] === '') {
            throw new RuntimeException('Isi unit kerja atau ruangan penempatan.');
        }
        $pic = self::text($in['pic'] ?? '', 150);
        if (!self::placed($d)) {
            $d['location'] = $location;
            if ($pic !== '') {
                $d['pic'] = $pic;
            }
            $d['updatedAt'] = $this->now;
            $d['updatedBy'] = $this->by;
            $this->state['devices'][$id] = $d;
            $this->touchedDevices[$id] = true;
            $this->log($id, 'move', 'Ditempatkan di ' . self::locText($location));
            return null;
        }
        if (!self::sameLoc($d['location'], $location)) {
            return $this->move($id, ['to' => $location, 'reason' => 'Penempatan', 'pic' => $pic]);
        }
        if ($pic !== ($d['pic'] ?? '')) {
            $this->log($id, 'edit', 'Penanggung jawab diubah: ' . (($d['pic'] ?? '') ?: '—') . ' → ' . ($pic ?: '—'));
            $d['pic'] = $pic;
            $d['updatedAt'] = $this->now;
            $d['updatedBy'] = $this->by;
            $this->state['devices'][$id] = $d;
            $this->touchedDevices[$id] = true;
        }
        return null;
    }

    public function move(string $id, array $in): array
    {
        $d = $this->device($id);
        if ($d['status'] === 'disposed') {
            throw new RuntimeException('Perangkat sudah dihapuskan; lokasi tidak dapat diubah.');
        }
        $id = $d['id'];
        $to = self::location($in['to'] ?? null);
        if ($to['dept'] === '' && $to['room'] === '') {
            throw new RuntimeException('Isi unit kerja atau ruangan tujuan.');
        }
        if (self::sameLoc($d['location'], $to)) {
            throw new RuntimeException('Lokasi baru sama dengan lokasi saat ini.');
        }
        $reason = self::text($in['reason'] ?? '', 150);
        if ($reason === '') {
            throw new RuntimeException('Pilih alasan perpindahan.');
        }
        $pic = self::text($in['pic'] ?? '', 150);
        $this->state['seq']['mv']++;
        $m = [
            'id' => 'MV' . str_pad((string) $this->state['seq']['mv'], 5, '0', STR_PAD_LEFT), 'deviceId' => $id,
            'from' => $d['location'], 'to' => $to, 'at' => $this->now, 'by' => $this->by, 'reason' => $reason,
            'note' => self::text($in['note'] ?? '', 1000, true), 'picFrom' => $d['pic'] ?? '', 'picTo' => $pic !== '' ? $pic : ($d['pic'] ?? ''),
        ];
        $this->state['moves'][] = $m;
        $d['location'] = $to;
        $d['pic'] = $m['picTo'];
        $d['updatedAt'] = $this->now;
        $d['updatedBy'] = $this->by;
        $this->state['devices'][$id] = $d;
        $this->touchedDevices[$id] = true;
        $this->log($id, 'move', 'Dipindahkan ke ' . self::locText($to) . ' — ' . $reason);
        return $m;
    }

    /* ---------------- services ---------------- */

    public function addService(array $in): array
    {
        $d = $this->device((string) ($in['deviceId'] ?? ''));
        if ($d['status'] === 'disposed') {
            throw new RuntimeException('Perangkat sudah dihapuskan.');
        }
        if ($d['status'] === 'disposal') {
            throw new RuntimeException('Perangkat sedang dalam proses disposal.');
        }
        if ($this->openService($d['id']) !== null) {
            throw new RuntimeException('Perangkat ini masih memiliki laporan servis yang belum selesai.');
        }
        $type = self::text($in['type'] ?? '', 100);
        $problem = self::text($in['problem'] ?? '', 2000, true);
        if ($type === '' || $problem === '') {
            throw new RuntimeException('Isi jenis servis dan uraian masalah.');
        }
        $status = is_string($in['status'] ?? null) && isset(self::SVC_STATUS[$in['status']]) ? $in['status'] : 'reported';
        $reportDate = self::date($in['reportDate'] ?? '') ?: substr($this->now, 0, 10);
        $notes = self::text($in['notes'] ?? '', 2000, true);
        $dueDate = self::dueDate($in['dueDate'] ?? '', $reportDate);
        $this->state['seq']['srv']++;
        $t = [
            'id' => 'SRV-' . substr($reportDate, 0, 4) . '-' . str_pad((string) $this->state['seq']['srv'], 4, '0', STR_PAD_LEFT),
            'deviceId' => $d['id'], 'deviceName' => $d['name'], 'location' => $d['location'], 'type' => $type, 'problem' => $problem,
            'reportDate' => $reportDate, 'dueDate' => $dueDate, 'technician' => self::text($in['technician'] ?? '', 150), 'status' => $status, 'notes' => $notes,
            'completedAt' => self::date($in['completedAt'] ?? ''), 'prevStatus' => $d['status'], 'createdAt' => $this->now, 'createdBy' => $this->by,
            'docs' => $this->attach($in['docs'] ?? null, [], 10),
            'log' => [['at' => $this->now, 'by' => $this->by, 'status' => $status, 'note' => 'Laporan dibuat' . ($notes !== '' ? ': ' . $notes : '')]],
        ];
        $this->needReport($t, $status, is_string($in['afterStatus'] ?? null) ? $in['afterStatus'] : $t['prevStatus']);
        if (in_array($status, self::SVC_CLOSED, true) && $t['completedAt'] === '') {
            $t['completedAt'] = substr($this->now, 0, 10);
        }
        $this->state['services'][] = $t;
        $this->touchedServices[$t['id']] = true;
        $this->log($d['id'], 'service', 'Laporan servis ' . $t['id'] . ' dibuat (' . $type . ')');
        if (in_array($status, self::SVC_CLOSED, true)) {
            $this->finishService($t['id'], is_string($in['afterStatus'] ?? null) ? $in['afterStatus'] : $t['prevStatus']);
        } else {
            $this->setStatus($d['id'], 'servis');
        }
        return $this->service($t['id']);
    }

    /** Progress of an open service: status, notes, technician and documents (e.g. the berita acara of a rejection). */
    public function updateService(string $id, array $in): array
    {
        $t = $this->service($id);
        if (in_array($t['status'], self::SVC_CLOSED, true)) {
            throw new RuntimeException('Laporan servis sudah ' . ($t['status'] === 'done' ? 'selesai' : 'ditolak') . '.');
        }
        $prev = $t['status'];
        $added = $this->attach($in['docs'] ?? null, [], 10);
        $t['docs'] = array_slice([...($t['docs'] ?? []), ...$added], 0, 20);
        if (is_string($in['technician'] ?? null) && trim($in['technician']) !== '') {
            $t['technician'] = self::text($in['technician'], 150);
        }
        if (array_key_exists('notes', $in) && $in['notes'] !== null) {
            $t['notes'] = self::text($in['notes'], 2000, true);
        }
        // The vendor's deadline can be set, moved or cleared ('') while the service is open; null leaves it.
        $dueChange = '';
        if (array_key_exists('dueDate', $in) && $in['dueDate'] !== null) {
            $due = self::dueDate($in['dueDate'], (string) $t['reportDate']);
            if ($due !== ($t['dueDate'] ?? '')) {
                $dueChange = $due === '' ? 'Tenggat vendor dihapus' : 'Tenggat vendor: ' . self::dateText($due);
                $t['dueDate'] = $due;
            }
        }
        $status = is_string($in['status'] ?? null) && isset(self::SVC_STATUS[$in['status']]) ? $in['status'] : $t['status'];
        $this->needReport($t, $status, is_string($in['afterStatus'] ?? null) ? $in['afterStatus'] : '');
        $t['status'] = $status;
        $note = self::text($in['note'] ?? '', 1000, true);
        $files = ($dueChange !== '' && $note !== '' ? ' · ' . $dueChange : '') . ($added !== [] ? ' (lampiran: ' . implode(', ', array_column($added, 'name')) . ')' : '');
        if ($dueChange !== '' && $note === '') {
            $note = $dueChange;
        }
        $t['log'][] = ['at' => $this->now, 'by' => $this->by, 'status' => $status,
            'note' => ($note !== '' ? $note : ($prev !== $status ? 'Status: ' . self::SVC_STATUS[$prev] . ' → ' . self::SVC_STATUS[$status] : ($added !== [] ? 'Dokumen ditambahkan' : 'Catatan ditambahkan'))) . $files];
        if (in_array($status, self::SVC_CLOSED, true)) {
            $t['completedAt'] = self::date($in['completedAt'] ?? '') ?: substr($this->now, 0, 10);
        }
        $this->putService($t);
        if (in_array($status, self::SVC_CLOSED, true)) {
            $this->finishService($t['id'], is_string($in['afterStatus'] ?? null) ? $in['afterStatus'] : '');
        } elseif ($prev !== $status) {
            $this->log($t['deviceId'], 'service', 'Servis ' . $t['id'] . ': ' . self::SVC_STATUS[$status]);
        }
        return $this->service($t['id']);
    }

    /** The device status after a closed service: the one chosen, or aktif when done and rusak when rejected. */
    private static function endStatus(string $status, string $afterStatus): string
    {
        return in_array($afterStatus, ['aktif', 'cadangan', 'rusak'], true) ? $afterStatus : ($status === 'rejected' ? 'rusak' : 'aktif');
    }

    /**
     * A service may only close as rejected, or with the device found broken (Rusak), when a berita acara is attached
     * (photo or PDF): the ticket's documents, including the ones added by this update.
     */
    private function needReport(array $t, string $status, string $afterStatus): void
    {
        if (!in_array($status, self::SVC_CLOSED, true) || ($t['docs'] ?? []) !== []) {
            return;
        }
        if ($status === 'rejected') {
            throw new RuntimeException('Unggah berita acara penolakan servis.');
        }
        if (self::endStatus($status, $afterStatus) === 'rusak') {
            throw new RuntimeException('Perangkat dinyatakan rusak: unggah berita acara kerusakan (foto atau PDF).');
        }
    }

    /** A closed service frees the device with the end status chosen (default: aktif when done, rusak when rejected). */
    private function finishService(string $id, string $afterStatus): void
    {
        $t = $this->service($id);
        $next = self::endStatus($t['status'], $afterStatus);
        $t['afterStatus'] = $next;
        $this->putService($t);
        $d = $this->state['devices'][$t['deviceId']] ?? null;
        if ($d !== null && !in_array($d['status'], ['disposed', 'disposal'], true)) {
            $this->setStatus($d['id'], $next);
            $this->log($d['id'], 'service', 'Servis ' . $t['id'] . ($t['status'] === 'rejected' ? ' ditolak' : ' selesai') . ' — status perangkat: ' . self::STATUS[$next]);
        }
    }

    /* ---------------- disposals ---------------- */

    /**
     * Disposal in one step (no request / approval): the device is archived at once. An older request still open
     * is finished by this same record.
     */
    public function dispose(array $in): array
    {
        $d = $this->device((string) ($in['deviceId'] ?? ''));
        if ($d['status'] === 'disposed') {
            throw new RuntimeException('Perangkat sudah dihapuskan.');
        }
        $open = $this->openService($d['id']);
        if ($open !== null) {
            throw new RuntimeException('Selesaikan dulu laporan servis ' . $open['id'] . ' sebelum disposal.');
        }
        $reason = self::text($in['reason'] ?? '', 150);
        $condition = self::text($in['condition'] ?? '', 150);
        if ($reason === '' || $condition === '') {
            throw new RuntimeException('Isi alasan dan kondisi perangkat.');
        }
        $method = self::text($in['method'] ?? '', 150);
        if ($method === '') {
            throw new RuntimeException('Pilih metode penghapusan.');
        }
        $r = $this->openDisposal($d['id']);
        if ($r === null) {
            $this->state['seq']['dsp']++;
            $r = [
                'id' => 'DSP-' . substr($this->now, 0, 4) . '-' . str_pad((string) $this->state['seq']['dsp'], 4, '0', STR_PAD_LEFT),
                'deviceId' => $d['id'], 'deviceName' => $d['name'], 'category' => $d['category'], 'lastLocation' => $d['location'],
                'disposalDate' => '', 'docs' => [], 'requestedBy' => $this->by, 'requestedAt' => $this->now, 'prevStatus' => $d['status'],
                'approvedBy' => '', 'approverRole' => '', 'approvedAt' => '', 'decisionNote' => '',
            ];
            $this->state['disposals'][] = $r;
        }
        $r['reason'] = $reason;
        $r['reasonDetail'] = self::text($in['reasonDetail'] ?? '', 2000, true);
        $r['condition'] = $condition;
        $r['status'] = 'disposed';
        $r['disposedAt'] = self::date($in['date'] ?? '') ?: substr($this->now, 0, 10);
        $r['method'] = $method;
        $r['reference'] = self::text($in['reference'] ?? '', 100);
        $docs = $this->attach($in['docs'] ?? null, [], 10);
        if ($docs !== []) {
            $r['docs'] = array_slice([...($r['docs'] ?? []), ...$docs], 0, 20);
        }
        $this->putDisposal($r);
        $d['status'] = 'disposed';
        $d['disposedAt'] = $r['disposedAt'];
        $d['disposalId'] = $r['id'];
        $d['updatedAt'] = $this->now;
        $d['updatedBy'] = $this->by;
        $this->state['devices'][$d['id']] = $d;
        $this->touchedDevices[$d['id']] = true;
        $this->log($d['id'], 'disposal', 'Perangkat dihapuskan (' . $method . ') — ' . $reason . ($r['reference'] !== '' ? ' — BA ' . $r['reference'] : '') . '. Riwayat diarsipkan.');
        return $r;
    }

    /** Cancels an older request still open (the only kind left: new disposals are done in one step). */
    public function rejectDisposal(string $id, array $in): array
    {
        $r = $this->disposal($id);
        if (!in_array($r['status'], ['pending', 'approved'], true)) {
            throw new RuntimeException('Pengajuan ini sudah selesai.');
        }
        $who = self::text($in['approvedBy'] ?? '', 150);
        if ($who === '') {
            throw new RuntimeException('Isi nama yang membatalkan.');
        }
        $r['status'] = 'rejected';
        $r['approvedBy'] = $who;
        $r['approverRole'] = self::text($in['role'] ?? '', 150);
        $r['approvedAt'] = $this->now;
        $r['decisionNote'] = self::text($in['note'] ?? '', 1000, true);
        $this->putDisposal($r);
        $d = $this->state['devices'][$r['deviceId']] ?? null;
        if ($d !== null && $d['status'] === 'disposal') {
            $this->setStatus($d['id'], ($r['prevStatus'] ?? '') !== '' && $r['prevStatus'] !== 'disposal' ? $r['prevStatus'] : 'aktif');
        }
        $this->log($r['deviceId'], 'disposal', 'Pengajuan disposal ' . $r['id'] . ' dibatalkan oleh ' . $r['approvedBy'] . ($r['decisionNote'] !== '' ? ' — ' . $r['decisionNote'] : ''));
        return $r;
    }

    /* ---------------- serah terima ---------------- */

    /**
     * Serah Terima Perangkat: one berita acara for one or more devices. The receiver becomes each device's person
     * in charge and its department the device's department. Signatures may come now or later (signHandover).
     */
    public function handover(array $in): array
    {
        $ids = [];
        foreach (is_array($in['deviceIds'] ?? null) ? $in['deviceIds'] : [] as $value) {
            $id = self::normId($value);
            if ($id !== '') {
                $ids[$id] = true;
            }
        }
        if ($ids === []) {
            throw new RuntimeException('Pilih minimal satu perangkat.');
        }
        if (count($ids) > 30) {
            throw new RuntimeException('Maksimal 30 perangkat per serah terima.');
        }
        $fromName = self::text($in['fromName'] ?? '', 150);
        $toName = self::text($in['toName'] ?? '', 150);
        $toDept = self::text($in['toDept'] ?? '', 100);
        $condition = self::text($in['condition'] ?? '', 150);
        if ($fromName === '' || $toName === '' || $toDept === '' || $condition === '') {
            throw new RuntimeException('Isi nama yang menyerahkan, nama dan departemen yang menerima, serta kondisi perangkat.');
        }
        $devices = [];
        foreach (array_keys($ids) as $id) {
            $d = $this->device((string) $id);
            if (in_array($d['status'], ['servis', 'disposal', 'disposed'], true)) {
                throw new RuntimeException($d['name'] . ' berstatus ' . self::STATUS[$d['status']] . ', tidak dapat diserahterimakan.');
            }
            $devices[] = $d;
        }
        $this->state['seq']['bast']++;
        $r = [
            'id' => 'BAST-' . substr($this->now, 0, 4) . '-' . str_pad((string) $this->state['seq']['bast'], 4, '0', STR_PAD_LEFT),
            'date' => self::date($in['date'] ?? '') ?: substr($this->now, 0, 10),
            'fromName' => $fromName, 'fromRole' => self::text($in['fromRole'] ?? '', 150), 'toName' => $toName, 'toRole' => self::text($in['toRole'] ?? '', 150), 'toDept' => $toDept,
            'condition' => $condition, 'note' => self::text($in['note'] ?? '', 2000, true),
            'devices' => array_map(static fn (array $d): array => ['id' => $d['id'], 'name' => $d['name'], 'brand' => $d['brand'] ?? '', 'model' => $d['model'] ?? '', 'serial' => $d['serial'] ?? ''], $devices),
            'signFrom' => $this->attach($in['signFrom'] ?? null, [], 1)[0] ?? null, 'signTo' => $this->attach($in['signTo'] ?? null, [], 1)[0] ?? null,
            'createdAt' => $this->now, 'createdBy' => $this->by,
        ];
        $this->state['handovers'][] = $r;
        $this->touchedHandovers[$r['id']] = true;
        foreach ($devices as $d) {
            $location = ['building' => '', 'floor' => '', 'dept' => $toDept, 'room' => ''];
            if (self::placed($d) && !self::sameLoc($d['location'], $location)) {
                $this->move($d['id'], ['to' => $location, 'reason' => 'Serah terima', 'pic' => $toName]);
            } else {
                $this->place($d['id'], ['location' => self::placed($d) ? $d['location'] : $location, 'pic' => $toName]);
            }
            $this->state['devices'][$d['id']]['department'] = $toDept;
            $this->state['devices'][$d['id']]['updatedAt'] = $this->now;
            $this->state['devices'][$d['id']]['updatedBy'] = $this->by;
            $this->touchedDevices[$d['id']] = true;
            $this->log($d['id'], 'handover', 'Serah terima ' . $r['id'] . ': ' . $fromName . ' → ' . $toName . ' (' . $toDept . ')');
        }
        return $r;
    }

    /** Adds a signature not given yet ($in: role "from" or "to", file). A signature already given stays as it is. */
    public function signHandover(string $id, array $in): array
    {
        foreach ($this->state['handovers'] as $i => $r) {
            if ($r['id'] !== $id) {
                continue;
            }
            $field = ['from' => 'signFrom', 'to' => 'signTo'][$in['role'] ?? ''] ?? null;
            if ($field === null) {
                throw new RuntimeException('Pihak yang menandatangani tidak dikenal.');
            }
            if (!empty($r[$field])) {
                throw new RuntimeException('Tanda tangan pihak ini sudah ada.');
            }
            $file = $this->attach($in['file'] ?? null, [], 1)[0] ?? null;
            if ($file === null) {
                throw new RuntimeException('Tanda tangan belum diunggah.');
            }
            $r[$field] = $file;
            $this->state['handovers'][$i] = $r;
            $this->touchedHandovers[$id] = true;
            foreach ($r['devices'] as $d) {
                $this->log($d['id'], 'handover', 'Tanda tangan ' . ($in['role'] === 'from' ? 'yang menyerahkan' : 'yang menerima') . ' ditambahkan pada ' . $id);
            }
            return $r;
        }
        throw new RuntimeException('Serah terima tidak ditemukan.');
    }

    /* ---------------- state helpers ---------------- */

    private function device(string $id): array
    {
        $d = $this->state['devices'][self::normId($id)] ?? null;
        if ($d === null) {
            throw new RuntimeException('Perangkat tidak ditemukan.');
        }
        return $d;
    }

    private function setStatus(string $id, string $status): void
    {
        $this->state['devices'][$id]['status'] = $status;
        $this->state['devices'][$id]['updatedAt'] = $this->now;
        $this->state['devices'][$id]['updatedBy'] = $this->by;
        $this->touchedDevices[$id] = true;
    }

    private function service(string $id): array
    {
        foreach ($this->state['services'] as $t) {
            if ($t['id'] === $id) {
                return $t;
            }
        }
        throw new RuntimeException('Laporan servis tidak ditemukan.');
    }

    private function putService(array $t): void
    {
        foreach ($this->state['services'] as $i => $x) {
            if ($x['id'] === $t['id']) {
                $this->state['services'][$i] = $t;
            }
        }
        $this->touchedServices[$t['id']] = true;
    }

    private function openService(string $deviceId): ?array
    {
        foreach ($this->state['services'] as $t) {
            if ($t['deviceId'] === $deviceId && !in_array($t['status'], self::SVC_CLOSED, true)) {
                return $t;
            }
        }
        return null;
    }

    private function disposal(string $id): array
    {
        foreach ($this->state['disposals'] as $r) {
            if ($r['id'] === $id) {
                return $r;
            }
        }
        throw new RuntimeException('Pengajuan tidak ditemukan.');
    }

    private function putDisposal(array $r): void
    {
        foreach ($this->state['disposals'] as $i => $x) {
            if ($x['id'] === $r['id']) {
                $this->state['disposals'][$i] = $r;
            }
        }
        $this->touchedDisposals[$r['id']] = true;
    }

    private function openDisposal(string $deviceId): ?array
    {
        foreach ($this->state['disposals'] as $r) {
            if ($r['deviceId'] === $deviceId && in_array($r['status'], ['pending', 'approved'], true)) {
                return $r;
            }
        }
        return null;
    }

    private function log(string $deviceId, string $type, string $text): void
    {
        $this->state['seq']['act']++;
        $this->state['activity'][] = ['id' => 'A' . $this->state['seq']['act'], 'deviceId' => $deviceId, 'type' => $type,
            'text' => mb_substr($text, 0, 500), 'at' => $this->now, 'by' => $this->by];
    }

    /** IP, MAC and serial not used by another active device. IP and MAC are free text (no format check). */
    private function checkNetwork(array $d, ?string $exceptId): void
    {
        $labels = ['ip' => 'IP', 'mac' => 'MAC', 'serial' => 'Serial number'];
        foreach ($labels as $field => $label) {
            $value = strtoupper(trim((string) $d[$field]));
            if ($value === '') {
                continue;
            }
            foreach ($this->state['devices'] as $other) {
                if ($other['id'] !== $d['id'] && $other['id'] !== $exceptId && $other['status'] !== 'disposed' && strtoupper(trim((string) ($other[$field] ?? ''))) === $value) {
                    throw new RuntimeException($label . ' sudah dipakai oleh ' . $other['name'] . ' (' . $other['id'] . ').');
                }
            }
        }
    }

    /* ---------------- value helpers (same as aset-store.js) ---------------- */

    /**
     * Upper-case barcode. A label QR holds a link to the inventory site (https://…/history/ITEM-00049895) and a
     * scanner may drop its start (ASSET-INVENTARIS/HISTORY/ITEM-00049895): the code is the last path segment.
     */
    public static function normId(mixed $s): string
    {
        if (!is_string($s)) {
            return '';
        }
        $value = (string) preg_replace('/[?#].*$/s', '', trim((string) preg_replace('/[\x00-\x1F\x7F]/u', '', $s)));
        if (str_contains($value, '/')) {
            $parts = array_values(array_filter(explode('/', $value), 'strlen'));
            $value = rawurldecode($parts === [] ? '' : end($parts));
        }
        return strtoupper(trim($value));
    }

    /** Why a code is not an asset code (hospital label ITEM-00049895, or the portal's AB-BTN-PC-0001), or null. */
    public static function codeProblem(mixed $s): ?string
    {
        $code = self::normId($s);
        if ($code === '') {
            return 'Barcode / ID aset wajib diisi.';
        }
        if (preg_match(self::CODE_FORMAT, $code) === 1) {
            return null;
        }
        if (preg_match('/^(ITEM-?\d*|AB-BTN-.*)$/', $code) === 1) {
            return 'Barcode "' . $code . '" tidak lengkap. Scan ulang label perangkat.';
        }
        return 'Format barcode "' . $code . '" tidak dikenali. Gunakan label aset (contoh ITEM-00049895) atau ID AB-BTN-PC-0001.';
    }

    public static function normMac(mixed $s): string
    {
        // Free text (up to the 17 characters of asset_devices.mac); only a plain 12-hex-digit MAC is formatted.
        $raw = trim(str_replace(["\r", "\n", "\t"], ' ', is_string($s) ? $s : ''));
        $hex = strtoupper((string) preg_replace('/[^0-9a-f]/i', '', $raw));
        return strlen($hex) === 12 && preg_match('/^[0-9a-f:.\-\s]+$/i', $raw) === 1 ? implode(':', str_split($hex, 2)) : mb_strtoupper(mb_substr($raw, 0, 17));
    }

    public static function placed(array $d): bool
    {
        return ($d['location']['dept'] ?? '') !== '' || ($d['location']['room'] ?? '') !== '';
    }

    /** A location is its department / unit; Ruangan, Gedung and Lantai are no longer asked (older records may still have them). */
    public static function sameLoc(array $a, array $b): bool
    {
        return ($a['dept'] ?? '') === ($b['dept'] ?? '');
    }

    public static function locText(array $l): string
    {
        if (($l['dept'] ?? '') === '' && ($l['room'] ?? '') === '') {
            return '—';
        }
        $where = implode(', ', array_filter([$l['building'] ?? '', $l['floor'] ?? ''], 'strlen'));
        return implode(' · ', array_filter([$l['dept'] ?? '', $l['room'] ?? ''], 'strlen')) . ($where !== '' ? ' (' . $where . ')' : '');
    }

    /** The next free barcode of a category, AB-BTN-<prefix>-NNNN. */
    public static function nextId(array $devices, string $category): string
    {
        $pre = 'AB-BTN-' . (self::PREFIX[$category] ?? 'OTH') . '-';
        $max = 0;
        foreach (array_keys($devices) as $id) {
            if (str_starts_with((string) $id, $pre)) {
                $max = max($max, (int) substr((string) $id, strlen($pre)));
            }
        }
        return $pre . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    private static function text(mixed $value, int $max, bool $multiline = false): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return '';
        }
        $value = str_replace("\r\n", "\n", (string) $value);
        $value = (string) preg_replace($multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]+/u', $multiline ? '' : ' ', $value);
        return mb_substr(trim($value), 0, $max);
    }

    /** The vendor's deadline (Tenggat Vendor): an optional date, not before the report date. */
    private static function dueDate(mixed $value, string $reportDate): string
    {
        if (is_string($value) && trim($value) !== '' && self::date($value) === '') {
            throw new RuntimeException('Tanggal tenggat vendor tidak valid.');
        }
        $due = self::date($value);
        if ($due !== '' && $reportDate !== '' && $due < $reportDate) {
            throw new RuntimeException('Tenggat vendor tidak boleh sebelum tanggal laporan.');
        }
        return $due;
    }

    /** 2026-11-07 → 7 Nov 2026 */
    public static function dateText(string $ymd): string
    {
        $m = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return self::date($ymd) === '' ? $ymd : (int) substr($ymd, 8, 2) . ' ' . $m[(int) substr($ymd, 5, 2)] . ' ' . substr($ymd, 0, 4);
    }

    private static function date(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4)) ? $value : '';
    }

    private static function location(mixed $l): array
    {
        $l = is_array($l) ? $l : [];
        return ['building' => self::text($l['building'] ?? '', 100), 'floor' => self::text($l['floor'] ?? '', 100),
            'dept' => self::text($l['dept'] ?? '', 100), 'room' => self::text($l['room'] ?? '', 150)];
    }

    private static function spec(mixed $spec): array
    {
        $out = [];
        foreach (array_slice(is_array($spec) ? $spec : [], 0, 30, true) as $key => $value) {
            if (is_string($key) && preg_match('/^[A-Za-z0-9_]{1,40}$/', $key) === 1) {
                $out[$key] = self::text($value, 200);
            }
        }
        return $out;
    }

    /**
     * Uploaded files of a document list ([{id}, …] as sent): an id must be one of $this->files (uploaded asset
     * files) or already in $existing; name, type and size come from the server, never from the browser.
     * @return list<array{id: int, name: string, type: string, size: int}>
     */
    private function attach(mixed $docs, array $existing, int $max): array
    {
        $known = [];
        foreach ($existing as $doc) {
            if (is_array($doc) && isset($doc['id'])) {
                $known[(int) $doc['id']] = $doc;
            }
        }
        $out = [];
        foreach (is_array($docs) ? $docs : [] as $doc) {
            $id = is_array($doc) ? (int) ($doc['id'] ?? 0) : (int) $doc;
            $file = $known[$id] ?? (isset($this->files[$id]) ? ['id' => $id] + $this->files[$id] : null);
            if ($file !== null && !isset($out[$id])) {
                $out[$id] = ['id' => $id, 'name' => (string) $file['name'], 'type' => (string) $file['type'], 'size' => (int) $file['size']];
            }
        }
        if (count($out) > $max) {
            throw new RuntimeException('Maksimal ' . $max . ' berkas.');
        }
        return array_values($out);
    }
}
