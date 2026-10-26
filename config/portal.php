<?php
declare(strict_types=1);

// Portal cards per section. A card with a null href is not available yet and shows a notice when clicked.
return [
    'sections' => [
        'daily' => ['title' => 'MONITORING HARIAN', 'icon' => 'calendar-days', 'class' => 'pt-2', 'grid' => 'lg:grid-cols-6'],
        'monitoring' => ['title' => 'MONITORING & LAPORAN', 'icon' => 'chart-pie', 'class' => 'pt-4', 'grid' => 'lg:grid-cols-7'],
        'systems' => ['title' => 'SISTEM', 'icon' => 'monitor', 'class' => 'pt-4 pb-8', 'grid' => 'lg:grid-cols-7'],
    ],
    'cards' => [
        ['section' => 'daily', 'badge' => 'live', 'icon' => 'portal-thermometer', 'title' => 'RATA - RATA SUHU SERVER PERBULAN', 'description' => 'Tren suhu server bulanan', 'href' => '/form/suhu'],
        ['section' => 'daily', 'badge' => 'live', 'icon' => 'portal-video', 'title' => 'MONITORING CCTV', 'description' => 'Live stream & status CCTV', 'href' => '/form/cctv'],
        ['section' => 'daily', 'badge' => 'live', 'icon' => 'monitor', 'title' => 'CLEAR DESK AND SCREEN', 'description' => 'Inspeksi & kepatuhan clean desk', 'href' => '/form/cleardesk'],
        ['section' => 'monitoring', 'badge' => 'new', 'icon' => 'portal-globe', 'title' => 'IT - ONLINE', 'description' => 'Status konektivitas & server IT online', 'href' => null],
        ['section' => 'monitoring', 'badge' => 'new', 'icon' => 'portal-dashboard', 'title' => 'Dashboard', 'description' => 'Visualisasi & analitik sistem terpusat', 'href' => null],
        ['section' => 'monitoring', 'badge' => 'new', 'icon' => 'portal-hospital', 'title' => 'HIS', 'description' => 'Hospital Information System', 'href' => null],
        ['section' => 'systems', 'badge' => 'new', 'icon' => 'file-text', 'title' => 'ABE OFFICE', 'description' => 'IM & SPPD', 'href' => null],
        ['section' => 'systems', 'badge' => 'new', 'icon' => 'mail', 'title' => 'Dashboard KPI', 'description' => 'Akses Menggunakan Jaringan RS/ VPN', 'href' => null],
        ['section' => 'systems', 'badge' => 'new', 'icon' => 'target', 'title' => 'Monitoring Setting', 'description' => 'Perbandingan sys_setting antar RS', 'href' => null],
        ['section' => 'systems', 'badge' => 'new', 'icon' => 'bot', 'title' => 'Chatbot AI', 'description' => 'AI', 'href' => null],
        ['section' => 'systems', 'badge' => 'new', 'icon' => 'portal-dashboard', 'title' => 'Dashboard', 'description' => 'Visualisasi & analitik sistem terpusat', 'href' => null],
        ['section' => 'systems', 'badge' => 'new', 'icon' => 'portal-hospital', 'title' => 'HIS', 'description' => 'Hospital Information System', 'href' => null],
    ],
];
