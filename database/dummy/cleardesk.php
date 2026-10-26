<?php
declare(strict_types=1);

// Dummy data for the Clear Desk & Clear Screen module (form + rekap), taken from the design prototype.
return [
    'inspected_at' => '15/10/2026 10:18 WIB',
    'shifts' => ['Shift Pagi (07:00 - 14:00)', 'Shift Siang (14:00 - 21:00)', 'Shift Malam (21:00 - 07:00)'],
    'quick_locations' => ['UGD & Triase', 'Poli Rawat Jalan', 'Farmasi Sentral', 'Laboratorium', 'Kasir / Billing', 'Ruang Server IT'],
    'os_options' => [
        ['value' => 'Windows 11 Pro', 'label' => 'Windows 11 Pro', 'note' => 'Standar IT Hospital', 'highlight' => true],
        ['value' => 'Windows 10 Enterprise', 'label' => 'Windows 10 Enterprise', 'note' => 'Legacy Workstation'],
        ['value' => 'Linux Workstation', 'label' => 'Linux / Ubuntu Workstation', 'note' => 'Lab & PACS Thin Client'],
        ['value' => 'macOS', 'label' => 'macOS Terminal', 'note' => 'Direksi / Manajemen'],
        ['value' => 'Other', 'label' => 'Sistem Operasi Lainnya', 'note' => 'Embedded Alat Medis / Mesin Antrean', 'wide' => true],
    ],
    'checks' => [
        ['name' => 'chk_desk', 'title' => 'Kepatuhan Clear Desk (Meja Bebas Dokumen Sensitif)', 'description' => 'Dokumen rahasia pasien, rekam medis fisik, formulir resep, dan password bertuliskan sticky note tidak tergeletak sembarangan saat meja ditinggalkan.'],
        ['name' => 'chk_screen', 'title' => 'Kepatuhan Clear Screen (Penguncian Layar Monitor)', 'description' => 'Layar komputer terkunci otomatis (Win + L / Inactivity screen-lock timeout ≤ 3-5 menit) saat ditinggalkan oleh staf unit kerja.'],
        ['name' => 'chk_media', 'title' => 'Keamanan Media Penyimpanan Eksternal', 'description' => 'Flashdisk, hard disk eksternal, atau token USB tidak tercolok di workstation tanpa izin resmi IT Security RS Awal Bros.'],
    ],
    'daily' => ['rate' => '96.2%', 'inspected' => 48, 'target' => 50, 'remaining' => 2, 'ring_offset' => 12, 'findings' => 2],
    'findings_today' => [
        ['location' => 'Meja Kasir Poli Lt. 2', 'note' => 'Sticky note kredensial login SIMRS tertempel di bezel monitor. Kertas diamankan verifikator.', 'time' => 'Pukul 08:42 WIB', 'auditor' => 'Audit: Verifikator IT 01'],
        ['location' => 'Nurse Station 3 (Rawat Inap)', 'note' => 'Layar komputer rekam medis aktif ditinggalkan >7 menit tanpa lock screen saat operan jaga.', 'time' => 'Pukul 09:15 WIB', 'auditor' => 'Audit: Verifikator IT 02'],
    ],
    'filters' => [
        'month' => ['2026-10' => 'Oktober 2026 (Aktif)', '2026-09' => 'September 2026', '2026-08' => 'Agustus 2026', '2026-07' => 'Juli 2026'],
        'status' => ['' => 'Semua Status (Patuh / Temuan)', 'patuh' => '✓ Patuh Lengkap', 'temuan' => '⚠️ Temuan Catatan Fisik', 'screen' => '⚠️ Layar Belum Terkunci'],
        'unit' => ['' => 'Semua Unit Kerja', 'ugd' => 'Instalasi Gawat Darurat (UGD)', 'poli' => 'Poliklinik Spesialis Lt. 2', 'nurse' => 'Nurse Station Rawat Inap', 'farmasi' => 'Farmasi Sentral', 'rekam-medis' => 'Instalasi Rekam Medis', 'radiologi' => 'Radiologi & PACS', 'lab' => 'Laboratorium Sentral'],
        'os' => ['' => 'Semua OS', 'win11' => 'Windows 11 Pro 64-bit', 'win10' => 'Windows 10 Enterprise LTSC', 'ubuntu' => 'Ubuntu 22.04 LTS Desktop'],
    ],
    'total' => 142,
    // status: patuh | temuan | screen (screen not locked)
    'entries' => [
        ['no' => '#001', 'when' => '15 Okt 10:18 WIB • Pagi', 'month' => '2026-10', 'unit' => 'ugd', 'location' => 'Meja Pendaftaran UGD Lt. 1', 'detail' => 'Front Office • Client WS-UGD-01', 'icon' => 'siren', 'mac' => '00:1A:2B:44:88:91', 'ip' => '192.168.10.45', 'os' => 'win11', 'os_label' => 'Windows 11 Pro 64-bit', 'os_icon' => 'app-window', 'status' => 'patuh', 'status_label' => '✓ Patuh (Clear Desk & Screen)', 'file' => 'Desk_UGD_01.jpg', 'note' => 'Meja steril bebas dari rekam medis pasien fisik, screen saver lockout aktif dalam 3 menit.', 'auditor' => 'ib'],
        ['no' => '#002', 'when' => '15 Okt 09:45 WIB • Pagi', 'month' => '2026-10', 'unit' => 'poli', 'location' => 'Poli Spesialis Anak Lt. 2', 'detail' => 'Ruang Konsultasi Dokter 3', 'icon' => 'stethoscope', 'mac' => 'D4:5D:64:12:3C:FE', 'ip' => '192.168.10.82', 'os' => 'win10', 'os_label' => 'Windows 10 Ent LTSC', 'os_icon' => 'app-window', 'status' => 'temuan', 'status_label' => '⚠️ Temuan: Sticky Note Password', 'file' => 'Temuan_Poli_03.jpg', 'note' => 'Sticky note kredensial SIMRS langsung dimusnahkan via shredder. Edukasi keamanan ISO 27001 diberikan ke perawat jaga.', 'auditor' => 'af'],
        ['no' => '#003', 'when' => '15 Okt 09:20 WIB • Pagi', 'month' => '2026-10', 'unit' => 'nurse', 'location' => 'Nurse Station Rawat Inap Lt. 3', 'detail' => 'Gedung Melati • Counter Barat', 'icon' => 'hospital', 'mac' => '50:76:AF:33:9A:02', 'ip' => '192.168.10.115', 'os' => 'win11', 'os_label' => 'Windows 11 Pro 64-bit', 'os_icon' => 'app-window', 'status' => 'patuh', 'status_label' => '✓ Patuh (Clear Desk & Screen)', 'file' => 'Desk_Nurse_L3.jpg', 'note' => 'Status layar terkunci sempurna (Win+L) saat petugas visite. Meja bersih rapi tanpa berkas medis terbuka.', 'auditor' => 'ib'],
        ['no' => '#004', 'when' => '15 Okt 08:50 WIB • Pagi', 'month' => '2026-10', 'unit' => 'farmasi', 'location' => 'Farmasi Sentral Kasir 2', 'detail' => 'Lantai 1 • Loket Dispensing Obat', 'icon' => 'pill', 'mac' => '28:D2:44:A9:01:BC', 'ip' => '192.168.10.60', 'os' => 'win10', 'os_label' => 'Windows 10 Ent LTSC', 'os_icon' => 'app-window', 'status' => 'patuh', 'status_label' => '✓ Patuh (Clear Desk & Screen)', 'file' => 'Farmasi_Kasir2.jpg', 'note' => 'Thermal printer resep tertata tertutup, monitor privasi terpasang menghadap petugas kasir.', 'auditor' => 'rh'],
        ['no' => '#005', 'when' => '14 Okt 16:30 WIB • Sore', 'month' => '2026-10', 'unit' => 'rekam-medis', 'location' => 'Rekam Medis Filing', 'detail' => 'Basement 1 • Ruang Scanning DRM', 'icon' => 'folder-open', 'mac' => '1C:69:7A:88:E1:90', 'ip' => '192.168.10.140', 'os' => 'win11', 'os_label' => 'Windows 11 Pro 64-bit', 'os_icon' => 'app-window', 'status' => 'screen', 'status_label' => '⚠️ Temuan: Screen Belum Terkunci', 'file' => 'RM_Filing_01.jpg', 'note' => 'Komputer ditinggal istirahat dalam status login SIMRS. Timeout GPO group policy disetel ulang ke 3 menit paksa.', 'auditor' => 'af'],
        ['no' => '#006', 'when' => '14 Okt 15:15 WIB • Sore', 'month' => '2026-10', 'unit' => 'lab', 'location' => 'Laboratorium Patologi', 'detail' => 'Lantai 2 • Workstation Analyzer LIS', 'icon' => 'microscope', 'mac' => '90:1B:0E:31:AA:5D', 'ip' => '192.168.10.18', 'os' => 'ubuntu', 'os_label' => 'Ubuntu 22.04 LTS', 'os_icon' => 'terminal', 'status' => 'patuh', 'status_label' => '✓ Patuh (Clear Desk & Screen)', 'file' => 'Lab_Patologi.jpg', 'note' => 'Semua tabung reagen tersimpan di rak, meja bersih dari barcode sampel bekas, screen auto-lock prima.', 'auditor' => 'ib'],
        ['no' => '#007', 'when' => '14 Okt 14:10 WIB • Siang', 'month' => '2026-10', 'unit' => 'radiologi', 'location' => 'Radiologi Workstation PACS', 'detail' => 'Lantai 1 • Diagnostic Reading Room', 'icon' => 'scan', 'mac' => '3C:7C:3F:8A:22:9C', 'ip' => '192.168.10.201', 'os' => 'win11', 'os_label' => 'Windows 11 Pro 64-bit', 'os_icon' => 'app-window', 'status' => 'patuh', 'status_label' => '✓ Patuh (Clear Desk & Screen)', 'file' => 'Rad_PACS_01.jpg', 'note' => 'Monochrome medical grade display standby normal, meja dokter baca bersih dari catatan nama pasien fisik.', 'auditor' => 'rh'],
    ],
    'auditors' => [
        'ib' => ['initials' => 'IB', 'name' => 'IT Botania Superadmin', 'avatar' => 'bg-tertiary text-on-tertiary'],
        'af' => ['initials' => 'AF', 'name' => 'Ahmad Faisal', 'avatar' => 'bg-secondary text-on-secondary'],
        'rh' => ['initials' => 'RH', 'name' => 'Rian Hidayat', 'avatar' => 'bg-secondary-container text-on-secondary-container'],
    ],
];
