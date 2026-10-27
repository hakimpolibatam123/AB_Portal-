<?php
declare(strict_types=1);

// Seed of the dashboard_sections and dashboard_cards tables (database/migrations), and the read-only
// fallback when the database is not ready. Cards with 'available' => true link to their form; 'rekap' is its recap page.
return [
    'categories' => [
        'daily' => ['title' => 'Monitoring Harian', 'icon' => 'calendar'],
        'monitoring' => ['title' => 'Monitoring & Laporan', 'icon' => 'chart'],
        'systems' => ['title' => 'Sistem', 'icon' => 'monitor'],
    ],
    'cards' => [
        ['key' => 'hardware-server', 'title' => 'Form Monitoring Hardware Server IT', 'description' => 'Status & pemeliharaan server IT', 'icon' => 'server', 'tone' => 'emerald', 'category' => 'daily', 'type' => 'internal', 'target' => '/forms/hardware-server', 'rekap' => '/reports/hardware-server', 'form_slug' => 'hardware-server', 'available' => true, 'enabled' => true, 'sort_order' => 10],
        ['key' => 'kesesuaian-user', 'title' => 'Kesesuaian User', 'description' => 'Verifikasi & kesesuaian akses user', 'icon' => 'user-check', 'tone' => 'teal', 'category' => 'daily', 'type' => 'internal', 'target' => '/forms/kesesuaian-user', 'rekap' => '/reports/kesesuaian-user', 'form_slug' => 'kesesuaian-user', 'available' => true, 'enabled' => true, 'sort_order' => 20],
        ['key' => 'suhu-server', 'title' => 'Rata-rata Suhu Server Perbulan', 'description' => 'Tren suhu server bulanan', 'icon' => 'thermometer', 'tone' => 'amber', 'category' => 'daily', 'type' => 'internal', 'target' => '/form/suhu', 'rekap' => '/rekap/suhu', 'form_slug' => 'suhu-server', 'available' => true, 'enabled' => true, 'sort_order' => 30],
        ['key' => 'cctv', 'title' => 'Monitoring CCTV', 'description' => 'Akses layanan monitoring CCTV', 'icon' => 'video', 'tone' => 'sky', 'category' => 'daily', 'type' => 'internal', 'target' => '/form/cctv', 'rekap' => '/rekap/cctv', 'form_slug' => 'cctv', 'available' => true, 'enabled' => true, 'sort_order' => 40],
        ['key' => 'clear-desk', 'title' => 'Clear Desk and Screen', 'description' => 'Inspeksi & kepatuhan clean desk', 'icon' => 'monitor', 'tone' => 'slate', 'category' => 'daily', 'type' => 'internal', 'target' => '/form/cleardesk', 'rekap' => '/rekap/cleardesk', 'form_slug' => 'clear-desk', 'available' => true, 'enabled' => true, 'sort_order' => 50],
        ['key' => 'antrian-online', 'title' => 'Antrian Online', 'description' => 'Status & kuota tiket antrian', 'icon' => 'ticket', 'tone' => 'cyan', 'category' => 'daily', 'type' => 'internal', 'target' => '/forms/antrian-online', 'rekap' => '/reports/antrian-online', 'form_slug' => 'antrian-online', 'available' => true, 'enabled' => true, 'sort_order' => 60],
        ['key' => 'it-online', 'title' => 'IT Online', 'description' => 'Status konektivitas & server IT online', 'icon' => 'globe', 'tone' => 'teal', 'category' => 'monitoring', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 70],
        ['key' => 'monitoring-dashboard', 'title' => 'Dashboard', 'description' => 'Visualisasi & analitik sistem terpusat', 'icon' => 'dashboard', 'tone' => 'cyan', 'category' => 'monitoring', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 80],
        ['key' => 'monitoring-his', 'title' => 'HIS', 'description' => 'Hospital Information System', 'icon' => 'hospital', 'tone' => 'emerald', 'category' => 'monitoring', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 90],
        ['key' => 'abe-office', 'title' => 'ABE Office', 'description' => 'IM & SPPD', 'icon' => 'file', 'tone' => 'rose', 'category' => 'systems', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 100],
        ['key' => 'kpi-dashboard', 'title' => 'Dashboard KPI', 'description' => 'Akses menggunakan jaringan RS / VPN', 'icon' => 'mail', 'tone' => 'purple', 'category' => 'systems', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 110],
        ['key' => 'monitoring-setting', 'title' => 'Monitoring Setting', 'description' => 'Perbandingan sys_setting antar RS', 'icon' => 'target', 'tone' => 'amber', 'category' => 'systems', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 120],
        ['key' => 'chatbot-ai', 'title' => 'Chatbot AI', 'description' => 'Akses layanan asisten AI', 'icon' => 'bot', 'tone' => 'orange', 'category' => 'systems', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 130],
        ['key' => 'systems-dashboard', 'title' => 'Dashboard', 'description' => 'Visualisasi & analitik sistem terpusat', 'icon' => 'dashboard', 'tone' => 'cyan', 'category' => 'systems', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 140],
        ['key' => 'systems-his', 'title' => 'HIS', 'description' => 'Hospital Information System', 'icon' => 'hospital', 'tone' => 'emerald', 'category' => 'systems', 'type' => 'external', 'target' => null, 'form_slug' => null, 'enabled' => true, 'sort_order' => 150],
    ],
];
