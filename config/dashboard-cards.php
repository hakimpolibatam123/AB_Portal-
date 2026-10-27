<?php
declare(strict_types=1);

// Choices of the dashboard card dialog. Icon names are Lucide names from app/Helpers/icons.php;
// the words after each name are what the icon search also matches (from the design's icon picker).
return [
    'icons' => [
        'server' => 'server rak', 'user-check' => 'user akun verifikasi', 'thermometer' => 'suhu temperatur',
        'video' => 'cctv kamera video', 'monitor' => 'monitor layar komputer', 'ticket' => 'antrian tiket',
        'globe' => 'online internet web', 'layout-dashboard' => 'dashboard', 'building-2' => 'gedung rumah sakit his',
        'file-text' => 'dokumen surat', 'mail' => 'surat email', 'target' => 'target setting',
        'bot' => 'chatbot ai robot', 'clipboard-list' => 'form daftar checklist', 'clipboard-check' => 'checklist selesai',
        'hard-drive' => 'storage disk', 'database' => 'database data', 'wifi' => 'wifi jaringan',
        'network' => 'jaringan network lan', 'router' => 'router jaringan', 'shield-check' => 'keamanan security',
        'lock' => 'kunci keamanan', 'key-round' => 'kunci password', 'printer' => 'printer cetak',
        'scan' => 'scan radiologi', 'stethoscope' => 'dokter medis', 'hospital' => 'rumah sakit',
        'pill' => 'farmasi obat', 'bed-single' => 'bed tempat tidur rawat inap', 'ambulance' => 'ambulans ugd',
        'heart-pulse' => 'jantung pasien', 'activity' => 'aktivitas monitoring', 'chart-column' => 'grafik laporan',
        'chart-line' => 'grafik tren', 'chart-pie' => 'grafik laporan', 'calendar-days' => 'jadwal kalender',
        'clock' => 'jam waktu', 'bell' => 'pengingat notifikasi', 'phone' => 'telepon',
        'users' => 'pengguna tim', 'folder-open' => 'folder arsip', 'archive' => 'arsip',
        'cpu' => 'cpu hardware', 'battery-charging' => 'ups baterai', 'plug' => 'listrik power',
        'zap' => 'listrik daya', 'camera' => 'kamera foto', 'cloud' => 'cloud',
        'settings' => 'pengaturan', 'wrench' => 'perbaikan maintenance', 'link' => 'tautan link',
        'book-open' => 'panduan buku', 'receipt' => 'kasir billing', 'wallet' => 'keuangan',
        'truck' => 'logistik', 'fan' => 'pendingin ac',
    ],
    // First twelve icons offered for a new category.
    'category_icons' => ['folder-open', 'server', 'network', 'globe', 'monitor', 'shield-check', 'database', 'hospital', 'chart-pie', 'settings', 'link', 'archive'],
    // Accent colours = the existing .tone-* classes (resources/css/app.css).
    'colors' => [
        'teal' => 'Teal', 'emerald' => 'Hijau', 'sky' => 'Biru', 'cyan' => 'Sian', 'amber' => 'Kuning',
        'orange' => 'Oranye', 'rose' => 'Merah muda', 'purple' => 'Ungu', 'slate' => 'Abu-abu',
    ],
];
