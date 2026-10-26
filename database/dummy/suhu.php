<?php
declare(strict_types=1);

// Dummy data for the server-temperature module (form + rekap), taken from the design prototype.
return [
    'months' => ['2026-10' => 'Oktober 2026', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026', '2026-06' => 'Juni 2026'],
    'captured_at' => '2026-10-15T10:18',
    'locations' => [
        'dc-a' => ['name' => 'Ruang Server Utama (Data Center A)', 'sensor' => 'SNS-DCA-01'],
        'rack-1' => ['name' => 'Rak Server 1 (Core Switch & Storage)', 'sensor' => 'SNS-RK1-04'],
        'rack-2' => ['name' => 'Rak Server 2 (Virtualization & DB)', 'sensor' => 'SNS-RK2-09'],
        'ups-room' => ['name' => 'Ruang UPS & Backup Power', 'sensor' => 'SNS-UPS-02'],
    ],
    // Latest readings shown beside the form
    'recent' => [
        ['location' => 'Ruang Server Utama', 'time' => '14 Okt 2026 • 09:30 WIB', 'icon' => 'server', 'temp' => '21.4°C', 'status' => 'Normal', 'warning' => false],
        ['location' => 'Rak Server 1', 'time' => '13 Okt 2026 • 15:45 WIB', 'icon' => 'box', 'temp' => '22.1°C', 'status' => 'Normal', 'warning' => false],
        ['location' => 'Ruang UPS & Battery', 'time' => '12 Okt 2026 • 11:20 WIB', 'icon' => 'plug', 'temp' => '24.3°C', 'status' => 'Perhatian', 'warning' => true],
    ],
    'filters' => [
        'month' => ['2026-10' => 'Oktober 2026 (Aktif)', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026'],
        'status' => ['' => 'Semua Status Suhu', 'normal' => 'Normal (18°C - 24°C)', 'warning' => 'Perhatian (> 24°C)', 'critical' => 'Kritis (> 27°C)'],
        'location' => ['' => 'Semua Lokasi Ruangan', 'main' => 'Ruang Server Utama (NOC)', 'rack-1' => 'Rak Server 1 - Core Switch', 'rack-2' => 'Rak Server 2 - Database HIS', 'ups' => 'Ruang UPS & Power Inverter'],
    ],
    'stats' => ['average' => '21.6', 'minimum' => '19.8', 'maximum' => '24.8', 'last_date' => '15 Okt 2026', 'last_time' => '10:18 WIB • Shift Pagi', 'last_by' => 'IT botania (Superadmin)'],
    'total' => 30,
    'entries' => [
        ['no' => '#001', 'date' => '15 Okt 2026', 'time' => '10:18 WIB', 'shift' => 'Shift Pagi (Dinas 1)', 'month' => '2026-10', 'location_key' => 'rack-1', 'location' => 'Rak Server 1 - Core Switch', 'sensor' => 'Sensor RT-A1 (Inlet)', 'icon' => 'server', 'temp' => 21.4, 'status' => 'Normal', 'file' => 'Log_Suhu_15Okt.pdf', 'file_icon' => 'file-text', 'note' => 'PAC Unit 1 & 2 beroperasi normal gilir ganti, airflow plenum stabil tanpa hambatan.', 'officer' => 'ib'],
        ['no' => '#002', 'date' => '14 Okt 2026', 'time' => '22:30 WIB', 'shift' => 'Shift Malam (Dinas 3)', 'month' => '2026-10', 'location_key' => 'rack-2', 'location' => 'Rak Server 2 - Database HIS', 'sensor' => 'Sensor RT-B2 (Exhaust)', 'icon' => 'server', 'temp' => 24.8, 'status' => 'Perhatian', 'file' => 'Defrost_Log_PAC2.jpg', 'file_icon' => 'camera', 'note' => 'Kompresor PAC 2 siklus defrost otomatis selama 15 menit, suhu kembali turun ke 22.1°C pasca reset siklus.', 'officer' => 'af'],
        ['no' => '#003', 'date' => '14 Okt 2026', 'time' => '14:05 WIB', 'shift' => 'Shift Siang (Dinas 2)', 'month' => '2026-10', 'location_key' => 'main', 'location' => 'Ruang Server Utama (NOC)', 'sensor' => 'Sensor Ambient Tengah', 'icon' => 'door-open', 'temp' => 20.9, 'status' => 'Normal', 'file' => 'Checklist_NOC_14Okt.pdf', 'file_icon' => 'file-text', 'note' => 'Kelembaban ruangan 48% RH, filter udara PAC dibersihkan sesuai agenda preventif mingguan.', 'officer' => 'rh'],
        ['no' => '#004', 'date' => '13 Okt 2026', 'time' => '09:45 WIB', 'shift' => 'Shift Pagi (Dinas 1)', 'month' => '2026-10', 'location_key' => 'ups', 'location' => 'Ruang UPS & Power Inverter', 'sensor' => 'Sensor UPS Battery Bank', 'icon' => 'plug', 'temp' => 22.0, 'status' => 'Normal', 'file' => 'UPS_Battery_Temp.png', 'file_icon' => 'camera', 'note' => 'AC Split inverter cadangan 2 PK beroperasi paralel, exhaust fan ventilasi bekerja baik.', 'officer' => 'ib'],
        ['no' => '#005', 'date' => '12 Okt 2026', 'time' => '16:15 WIB', 'shift' => 'Shift Siang (Dinas 2)', 'month' => '2026-10', 'location_key' => 'rack-3', 'location' => 'Rak Server 3 - Storage PACS', 'sensor' => 'Sensor RT-C1 (SAN Storage)', 'icon' => 'server', 'temp' => 21.8, 'status' => 'Normal', 'file' => 'PACS_TempLog_12Okt.pdf', 'file_icon' => 'file-text', 'note' => 'Disipasi panas storage array stabil, kipas sasis otomatis berputar pada efisiensi 62%.', 'officer' => 'af'],
        ['no' => '#006', 'date' => '11 Okt 2026', 'time' => '04:10 WIB', 'shift' => 'Shift Malam (Dinas 3)', 'month' => '2026-10', 'location_key' => 'rack-1', 'location' => 'Rak Server 1 - Core Switch', 'sensor' => 'Sensor RT-A2 (Inlet Bawah)', 'icon' => 'server', 'temp' => 19.8, 'status' => 'Optimal Min', 'file' => 'Sens_Therm_Night.jpg', 'file_icon' => 'camera', 'note' => 'Beban komputasi malam rendah, suhu ruangan sejuk merata di seluruh rak jaringan spine-leaf.', 'officer' => 'rh'],
        ['no' => '#007', 'date' => '10 Okt 2026', 'time' => '11:20 WIB', 'shift' => 'Shift Pagi (Dinas 1)', 'month' => '2026-10', 'location_key' => 'main', 'location' => 'Ruang Server Utama (NOC)', 'sensor' => 'Sensor Cold Aisle Center', 'icon' => 'door-open', 'temp' => 21.5, 'status' => 'Normal', 'file' => 'Rekap_Suhu_10Okt.pdf', 'file_icon' => 'file-text', 'note' => 'Pemeriksaan rutin mingguan kepala instalasi IT. Tidak ditemukan kondensasi atau getaran blower.', 'officer' => 'ib'],
    ],
    'officers' => [
        'ib' => ['initials' => 'IB', 'name' => 'IT botania', 'role' => 'Superadmin'],
        'af' => ['initials' => 'AF', 'name' => 'Ahmad Faisal', 'role' => 'Staf IT Server'],
        'rh' => ['initials' => 'RH', 'name' => 'Rian Hidayat', 'role' => 'Teknisi Jaringan'],
    ],
];
