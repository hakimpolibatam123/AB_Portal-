<?php
declare(strict_types=1);

// Dummy data for the CCTV module (form + rekap), taken from the design prototype.
return [
    'inspected_at' => '15/10/2026 10:18 WIB',
    'cameras' => ['active' => 64, 'total' => 68, 'online' => '94.1%', 'ring' => 94],
    'issues' => [
        ['label' => '2 Unit Offline / Putus Jaringan', 'zone' => 'UGD & Parkir Timur', 'tone' => 'red'],
        ['label' => '1 Unit Buram / Kaca Berembun', 'zone' => 'Lobby Farmasi', 'tone' => 'amber'],
    ],
    'shifts' => [
        ['name' => 'Shift Pagi', 'icon' => 'notebook-pen', 'meta' => 'SEDANG DIINPUT', 'note' => 'Petugas: IT Botania (07:00 - 14:00 WIB)', 'current' => true],
        ['name' => 'Shift Siang', 'icon' => 'clock', 'meta' => '14:00 WIB', 'note' => 'Menunggu rotasi personel', 'current' => false],
        ['name' => 'Shift Malam', 'icon' => 'moon', 'meta' => '21:00 WIB', 'note' => 'Pemeriksaan perimeter & area parkir', 'current' => false],
    ],
    'filters' => [
        'month' => ['2026-10' => 'Oktober 2026 (Aktif)', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026'],
        'ntp' => ['' => 'Semua Status Jam', 'sesuai' => 'Sesuai NTP Server (Tepat)', 'drift' => 'Tidak Sesuai / Drift Waktu', 'kalibrasi' => 'Sudah Dikalibrasi Ulang'],
        'camera' => ['' => 'Semua Kondisi', 'nihil' => 'Normal / Nihil Rusak', 'issue' => 'Ada Kerusakan / Kendala IP', 'offline' => 'Status Offline (Ping Loss)'],
        'zone' => ['' => 'Semua Zona Gedung', 'ugd' => 'UGD & Triase', 'rawat-inap' => 'Rawat Inap Lt. 1-4', 'farmasi' => 'Farmasi Sentral & Kasir', 'poliklinik' => 'Poliklinik Rawat Jalan', 'parkir' => 'Area Parkir & Drop Zone'],
    ],
    'total' => 42,
    // camera: null = all cameras fine; otherwise the broken camera row. ntp: sesuai | kalibrasi.
    'entries' => [
        ['no' => '#001', 'when' => '15 Okt 2026 • 10:18 WIB', 'shift' => 'Shift Pagi', 'month' => '2026-10', 'zone' => 'ugd', 'location' => 'Area UGD & Ruang Triase', 'detail' => 'Gedung A • Lantai 1 (8 Titik)',
            'camera' => null, 'ntp' => 'sesuai', 'ntp_label' => 'Sesuai NTP',
            'file' => 'UGD_NVR_Log.png', 'file_icon' => 'image', 'file_tone' => 'text-secondary', 'file_action' => 'eye',
            'nvr' => 'HDD NVR: 84% Free (Normal)', 'nvr_tone' => 'text-tertiary', 'note' => 'Pemeriksaan fisik lensa & PoE switch aman.', 'officer' => 'ib'],
        ['no' => '#002', 'when' => '15 Okt 2026 • 09:45 WIB', 'shift' => 'Shift Pagi', 'month' => '2026-10', 'zone' => 'rawat-inap', 'location' => 'Rawat Inap Lt. 3 Koridor Timur', 'detail' => 'Gedung B • Ruang 301 - 312',
            'camera' => ['name' => 'CCTV-RWI-304 (192.168.10.42)', 'status' => 'Status: Offline / RTO Ping 100%', 'icon' => 'video-off', 'kind' => 'offline', 'box' => 'bg-error-container/60'], 'ntp' => 'sesuai', 'ntp_label' => 'Sesuai NTP',
            'file' => 'Ping_Diagnostic.pdf', 'file_icon' => 'file-text', 'file_tone' => 'text-primary', 'file_action' => 'external-link',
            'nvr' => 'Kabel UTP ditarik ulang teknisi', 'nvr_tone' => 'text-error', 'note' => 'Konektor RJ45 korosi di drop ceiling.', 'officer' => 'af'],
        ['no' => '#003', 'when' => '15 Okt 2026 • 08:30 WIB', 'shift' => 'Shift Pagi', 'month' => '2026-10', 'zone' => 'parkir', 'location' => 'Parkir Barat & Drop Zone Ambulance', 'detail' => 'Outdoor Area • Tiang Utama',
            'camera' => ['name' => 'CCTV-PKR-05 (192.168.10.105)', 'status' => 'Kondisi: Lensa Buram Embun Pagi', 'icon' => 'aperture', 'kind' => 'issue', 'box' => 'bg-surface-container'], 'ntp' => 'kalibrasi', 'ntp_label' => 'Dikalibrasi Ulang (+3 Min)',
            'file' => 'Lens_Clean_Before.jpg', 'file_icon' => 'image', 'file_tone' => 'text-secondary', 'file_action' => 'eye',
            'nvr' => 'Selesai dibersihkan petugas IT', 'nvr_tone' => 'text-secondary', 'note' => 'Housing kamera disegel silicone ulang.', 'officer' => 'rh'],
        ['no' => '#004', 'when' => '14 Okt 2026 • 21:10 WIB', 'shift' => 'Shift Malam', 'month' => '2026-10', 'zone' => 'farmasi', 'location' => 'Ruang Farmasi Sentral & Kasir', 'detail' => 'Gedung A • Loket Kasir 1-4',
            'camera' => ['name' => 'CCTV-FARM-01 (192.168.10.77)', 'status' => 'Keterangan: Port Switch Flap / Intermittent', 'icon' => 'refresh-cw-off', 'kind' => 'issue', 'box' => 'bg-error-container/40'], 'ntp' => 'sesuai', 'ntp_label' => 'Sesuai NTP',
            'file' => 'Switch_Port_Logs.txt', 'file_icon' => 'chart-column', 'file_tone' => 'text-tertiary', 'file_action' => 'eye',
            'nvr' => 'Port switch dipindahkan ke Port 18', 'nvr_tone' => 'text-secondary', 'note' => 'Monitoring stabil 24 jam ke depan.', 'officer' => 'ib'],
        ['no' => '#005', 'when' => '14 Okt 2026 • 15:30 WIB', 'shift' => 'Shift Sore', 'month' => '2026-10', 'zone' => 'poliklinik', 'location' => 'Poliklinik Spesialis Lt. 2', 'detail' => 'Gedung B • Ruang Tunggu Poli Mata & Gigi',
            'camera' => null, 'ntp' => 'sesuai', 'ntp_label' => 'Sesuai NTP',
            'file' => 'Poli_Cam_Grid.png', 'file_icon' => 'image', 'file_tone' => 'text-secondary', 'file_action' => 'eye',
            'nvr' => 'HDD NVR: 86% Free', 'nvr_tone' => 'text-tertiary', 'note' => 'Feed video jernih, latency < 12ms.', 'officer' => 'af'],
        ['no' => '#006', 'when' => '14 Okt 2026 • 11:20 WIB', 'shift' => 'Shift Pagi', 'month' => '2026-10', 'zone' => 'icu', 'location' => 'Ruang ICU & Recovery Room', 'detail' => 'Gedung A • Lantai 2 (Zona Steril)',
            'camera' => null, 'ntp' => 'sesuai', 'ntp_label' => 'Sesuai NTP',
            'file' => 'ICU_Cam_Check.png', 'file_icon' => 'image', 'file_tone' => 'text-secondary', 'file_action' => 'eye',
            'nvr' => 'NVR Redundant Dual PSU OK', 'nvr_tone' => 'text-tertiary', 'note' => 'Pemeriksaan sensor IR Night Vision aktif normal.', 'officer' => 'ib'],
        ['no' => '#007', 'when' => '14 Okt 2026 • 07:15 WIB', 'shift' => 'Shift Pagi', 'month' => '2026-10', 'zone' => 'logistik', 'location' => 'Gedung Logistik & Loading Dock', 'detail' => 'Gedung Servis • Pintu Masuk Barang',
            'camera' => ['name' => 'CCTV-LOG-02 (192.168.10.88)', 'status' => 'Status: Power Adaptor PoE Mati', 'icon' => 'video-off', 'kind' => 'offline', 'box' => 'bg-error-container/60'], 'ntp' => 'sesuai', 'ntp_label' => 'Sesuai NTP',
            'file' => 'Form_SPK_Vendor.pdf', 'file_icon' => 'file-text', 'file_tone' => 'text-primary', 'file_action' => 'external-link',
            'nvr' => 'SPK diajukan ke vendor Hikvision', 'nvr_tone' => 'text-error', 'note' => 'Menunggu penggantian modul power supply cadangan.', 'officer' => 'rh'],
    ],
    'officers' => [
        'ib' => ['initials' => 'IB', 'name' => 'IT botania', 'role' => 'Superadmin', 'avatar' => 'bg-primary text-on-primary'],
        'af' => ['initials' => 'AF', 'name' => 'Ahmad Faisal', 'role' => 'Staf Jaringan & IT', 'avatar' => 'bg-secondary text-on-secondary'],
        'rh' => ['initials' => 'RH', 'name' => 'Rian Hidayat', 'role' => 'Teknisi Jaringan', 'avatar' => 'bg-secondary-container text-on-secondary-container'],
    ],
];
