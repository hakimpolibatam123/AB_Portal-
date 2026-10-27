<?php
declare(strict_types=1);

// Dummy data for the dashboard ("/"): sections, cards, schedules and one example dynamic form.
// Frontend only: the browser keeps changes in sessionStorage (public/assets/js/dashboard.js);
// nothing is saved on the server. Icon names are Lucide names (app/Helpers/icons.php), colours
// are the .tone-* classes. Card kinds: builtin = an existing module page, form = a dynamic form,
// link = an external website ('builtin' => true marks the links that came with the portal).
//
// Schedule: type daily | weekly (days: 0 = Minggu … 6 = Sabtu) | monthly (month_day: 1–31 or 'last')
// | yearly (year_md: 'MM-DD') | dates (list of 'YYYY-MM-DD'); start/end are 'YYYY-MM-DD' or '';
// deadline is 'HH:MM' or '' and reminder shows "Belum Diisi" after the deadline.

$daily = ['type' => 'daily', 'days' => [], 'month_day' => 1, 'year_md' => '', 'dates' => [], 'start' => '', 'end' => '', 'deadline' => '', 'reminder' => false];

return [
    'sections' => [
        ['id' => 'harian', 'title' => 'Monitoring Harian', 'icon' => 'calendar-days', 'kind' => 'forms', 'custom' => false],
        ['id' => 'laporan', 'title' => 'Monitoring & Laporan', 'icon' => 'chart-pie', 'kind' => 'links', 'custom' => false],
        ['id' => 'sistem', 'title' => 'Sistem', 'icon' => 'monitor', 'kind' => 'links', 'custom' => false],
    ],
    'cards' => [
        ['id' => 'hardware-server', 'section' => 'harian', 'kind' => 'builtin', 'title' => 'Form Monitoring Hardware Server IT', 'description' => 'Status & pemeliharaan server IT', 'icon' => 'server', 'color' => 'emerald', 'url' => '/forms/hardware-server', 'rekap' => '/reports/hardware-server', 'schedule' => $daily, 'done_today' => true, 'has_data' => true],
        ['id' => 'kesesuaian-user', 'section' => 'harian', 'kind' => 'builtin', 'title' => 'Kesesuaian User', 'description' => 'Verifikasi & kesesuaian akses user', 'icon' => 'user-check', 'color' => 'teal', 'url' => '/forms/kesesuaian-user', 'rekap' => '/reports/kesesuaian-user', 'schedule' => $daily, 'done_today' => true, 'has_data' => true],
        ['id' => 'suhu-server', 'section' => 'harian', 'kind' => 'builtin', 'title' => 'Rata-rata Suhu Server Perbulan', 'description' => 'Tren suhu server bulanan', 'icon' => 'thermometer', 'color' => 'amber', 'url' => '/form/suhu', 'rekap' => '/rekap/suhu', 'schedule' => ['type' => 'monthly', 'month_day' => 1] + $daily, 'done_today' => false, 'has_data' => true],
        ['id' => 'cctv', 'section' => 'harian', 'kind' => 'builtin', 'title' => 'Monitoring CCTV', 'description' => 'Akses layanan monitoring CCTV', 'icon' => 'video', 'color' => 'sky', 'url' => '/form/cctv', 'rekap' => '/rekap/cctv', 'schedule' => ['deadline' => '14:00', 'reminder' => true] + $daily, 'done_today' => false, 'has_data' => true],
        ['id' => 'clear-desk', 'section' => 'harian', 'kind' => 'builtin', 'title' => 'Clear Desk and Screen', 'description' => 'Inspeksi & kepatuhan clean desk', 'icon' => 'monitor', 'color' => 'slate', 'url' => '/form/cleardesk', 'rekap' => '/rekap/cleardesk', 'schedule' => $daily, 'done_today' => true, 'has_data' => true],
        ['id' => 'antrian-online', 'section' => 'harian', 'kind' => 'builtin', 'title' => 'Antrian Online', 'description' => 'Status & kuota tiket antrian', 'icon' => 'ticket', 'color' => 'cyan', 'url' => '/forms/antrian-online', 'rekap' => '/reports/antrian-online', 'schedule' => $daily, 'done_today' => false, 'has_data' => true],
        ['id' => 'f-cek-ups', 'section' => 'harian', 'kind' => 'form', 'title' => 'Cek UPS Ruang Server', 'description' => 'Kondisi baterai & beban UPS', 'icon' => 'battery-charging', 'color' => 'purple', 'url' => '/form-dinamis?id=f-cek-ups', 'rekap' => '/form-dinamis/respons?id=f-cek-ups', 'schedule' => ['type' => 'weekly', 'days' => [1, 4], 'deadline' => '16:00'] + $daily, 'done_today' => false, 'has_data' => false],

        ['id' => 'it-online', 'section' => 'laporan', 'kind' => 'link', 'builtin' => true, 'title' => 'IT Online', 'description' => 'Status konektivitas & server IT online', 'icon' => 'globe', 'color' => 'teal', 'url' => null],
        ['id' => 'monitoring-dashboard', 'section' => 'laporan', 'kind' => 'link', 'builtin' => true, 'title' => 'Dashboard', 'description' => 'Visualisasi & analitik sistem terpusat', 'icon' => 'layout-dashboard', 'color' => 'cyan', 'url' => null],
        ['id' => 'monitoring-his', 'section' => 'laporan', 'kind' => 'link', 'builtin' => true, 'title' => 'HIS', 'description' => 'Hospital Information System', 'icon' => 'building-2', 'color' => 'emerald', 'url' => null],
        ['id' => 'abe-office', 'section' => 'sistem', 'kind' => 'link', 'builtin' => true, 'title' => 'ABE Office', 'description' => 'IM & SPPD', 'icon' => 'file-text', 'color' => 'rose', 'url' => null],
        ['id' => 'kpi-dashboard', 'section' => 'sistem', 'kind' => 'link', 'builtin' => true, 'title' => 'Dashboard KPI', 'description' => 'Akses menggunakan jaringan RS / VPN', 'icon' => 'mail', 'color' => 'purple', 'url' => null],
        ['id' => 'monitoring-setting', 'section' => 'sistem', 'kind' => 'link', 'builtin' => true, 'title' => 'Monitoring Setting', 'description' => 'Perbandingan sys_setting antar RS', 'icon' => 'target', 'color' => 'amber', 'url' => null],
        ['id' => 'chatbot-ai', 'section' => 'sistem', 'kind' => 'link', 'builtin' => true, 'title' => 'Chatbot AI', 'description' => 'Akses layanan asisten AI', 'icon' => 'bot', 'color' => 'orange', 'url' => null],
        ['id' => 'systems-dashboard', 'section' => 'sistem', 'kind' => 'link', 'builtin' => true, 'title' => 'Dashboard', 'description' => 'Visualisasi & analitik sistem terpusat', 'icon' => 'layout-dashboard', 'color' => 'cyan', 'url' => null],
        ['id' => 'systems-his', 'section' => 'sistem', 'kind' => 'link', 'builtin' => true, 'title' => 'HIS', 'description' => 'Hospital Information System', 'icon' => 'building-2', 'color' => 'emerald', 'url' => null],
    ],
    // Definitions of the dynamic forms (editor page and fill-in page, later milestones).
    'forms' => [
        'f-cek-ups' => [
            'title' => 'Cek UPS Ruang Server',
            'description' => 'Pemeriksaan rutin kondisi baterai, beban, dan alarm UPS di ruang server IT RS Awal Bros Botania.',
            'questions' => [
                ['id' => 'q-unit', 'type' => 'radio', 'title' => 'Unit UPS yang diperiksa', 'required' => true, 'options' => ['UPS Rak Server 1', 'UPS Rak Server 2', 'UPS Ruang Jaringan']],
                ['id' => 'q-baterai', 'type' => 'number', 'title' => 'Kapasitas baterai (%)', 'required' => true, 'min' => 0, 'max' => 100],
                ['id' => 'q-alarm', 'type' => 'checkbox', 'title' => 'Alarm yang muncul', 'required' => false, 'options' => ['Tidak ada alarm', 'Overload', 'Baterai lemah', 'Bypass aktif']],
                ['id' => 'q-catatan', 'type' => 'paragraph', 'title' => 'Catatan pemeriksaan', 'required' => false],
            ],
        ],
    ],
];
