<?php
$filterSelect = static fn (string $label, string $key, array $options): string => partial('field', [
    'class' => 'relative', 'label' => $label, 'labelClass' => 'block text-label-sm text-on-surface-variant uppercase mb-1 tracking-[.04em]',
    'control' => 'select', 'id' => 'filter-' . $key, 'options' => $options, 'boxClass' => 'relative', 'attrs' => ['data-filter' => $key],
    'controlClass' => 'w-full h-10 pl-3 pr-8 rounded-lg bg-surface-container-lowest text-on-surface text-body-sm focus:outline-none focus:ring-2 focus:ring-secondary appearance-none cursor-pointer',
    'iconRight' => ['chevron-down', 'absolute right-2.5 top-2.5 pointer-events-none text-on-surface-variant text-[20px]'],
]);
$statCard = static fn (string $body): string => '<div class="p-space-lg rounded-xl bg-surface-container-lowest flex flex-col justify-between shadow-rs">' . $body . '</div>';
ob_start(); ?>
<div class="flex items-center gap-4 flex-wrap sm:flex-nowrap">
    <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-tertiary-container/25 backdrop-blur-xs border-0">
        <div class="w-10 h-10 rounded-lg bg-surface-container-lowest/15 flex items-center justify-center text-on-tertiary"><?= icon('calendar-check', 'text-[24px]') ?></div>
        <div class="flex flex-col text-left">
            <span class="text-label-sm text-surface-container-high/80 uppercase tracking-wider">PERIODE AUDIT</span>
            <span class="text-headline-sm text-on-tertiary font-bold mt-1">Oktober 2026 (Aktif)</span>
        </div>
    </div>
    <?= partial('button', ['label' => 'Input Form Baru', 'href' => url('/form/cctv'), 'icon' => 'circle-check', 'iconClass' => 'text-[20px]', 'class' => 'inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-tertiary-container text-on-tertiary hover:bg-tertiary-container/90 transition-all text-headline-sm active:scale-95 whitespace-nowrap shadow-rs']) ?>
</div>
<?php $heroAside = ob_get_clean(); ?>
<main id="main-content" class="w-full bg-background flex-1">
<div class="flex flex-col w-full">
    <div class="w-full bg-surface-container-low py-space-sm px-margin">
        <div class="max-w-[1680px] mx-auto flex items-center justify-between text-on-surface-variant text-label-md">
            <?= partial('breadcrumb', [
                'tag' => 'div', 'class' => 'flex items-center gap-space-xs flex-wrap',
                'separator' => ['text' => '/', 'class' => 'text-outline-variant'],
                'items' => [
                    ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'icon' => 'layout-grid', 'iconClass' => 'text-[16px] text-tertiary', 'class' => 'hover:text-primary transition-colors flex items-center gap-1'],
                    ['label' => 'Monitoring Harian', 'class' => 'text-on-surface-variant'],
                    ['label' => 'CCTV', 'href' => url('/form/cctv'), 'class' => 'text-on-surface-variant'],
                    ['label' => 'Rekap Hasil Inputan', 'class' => 'text-primary font-semibold'],
                ],
            ]) ?>
            <div class="hidden sm:flex items-center gap-space-sm text-label-sm">
                <?= partial('badge', ['label' => 'AUDIT COMPLIANCE: 99.1%', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-tertiary-container animate-ping', 'class' => 'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container whitespace-nowrap']) ?>
                <span class="text-outline-variant">|</span>
                <span>HIS GATEWAY: <span class="font-bold text-on-surface">NODE-BTC-NVR01</span></span>
            </div>
        </div>
    </div>

    <div class="w-full px-margin py-space-lg space-y-space-lg max-w-[1680px] mx-auto">
        <?= partial('page-header', [
            'class' => 'w-full rounded-xl p-space-lg relative overflow-hidden',
            'innerClass' => 'flex flex-col xl:flex-row xl:items-center justify-between gap-space-lg', 'introClass' => 'space-y-space-xs max-w-3xl',
            'eyebrow' => partial('badge', ['tag' => 'div', 'label' => 'AUDIT KEAMANAN & FASILITAS IT • RS AWAL BROS BOTANIA', 'icon' => 'shield-check', 'iconClass' => 'text-[14px]', 'class' => 'inline-flex items-center gap-2 px-2.5 py-1 rounded-sm bg-tertiary-container/40 text-on-tertiary-container text-label-sm uppercase tracking-wider']),
            'title' => 'Rekap & Riwayat Hasil Monitoring CCTV',
            'description' => 'Log rekapitulasi harian kondisi kamera pengawas, status IP kamera bermasalah, dan audit sinkronisasi jam CCTV untuk kepatuhan operasional RS Awal Bros.',
            'descriptionClass' => 'text-body-md text-surface-container-high/90',
            'aside' => $heroAside,
        ]) ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
            <?= $statCard('<div class="flex items-start justify-between"><div class="space-y-1"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">TOTAL TITIK CCTV TERPANTAU</span><div class="flex items-baseline gap-2 mt-1"><span class="text-headline-xl text-on-surface font-bold">' . e($module['cameras']['total']) . '</span><span class="text-label-lg text-on-surface-variant font-medium">Unit</span></div></div><div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center text-secondary">' . icon('video', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between text-on-surface-variant text-body-sm"><span class="flex items-center gap-1 text-secondary font-medium">' . icon('building-2', 'text-[16px]') . 'Akumulasi 4 Lantai &amp; Area Luar</span><span class="text-label-sm px-1.5 py-0.5 rounded-sm bg-surface-container">100% Covered</span></div>') ?>
            <?= $statCard('<div class="flex items-start justify-between"><div class="space-y-1"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">KAMERA BERFUNGSI NORMAL</span><div class="flex items-baseline gap-2 mt-1"><span class="text-headline-xl text-secondary font-bold">' . e($module['cameras']['active']) . '</span><span class="text-label-lg text-on-surface-variant font-medium">Unit</span></div></div><div class="w-10 h-10 rounded-lg bg-secondary-container flex items-center justify-center text-on-secondary-container">' . icon('circle-check', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between text-body-sm"><span class="text-tertiary-container font-semibold flex items-center gap-1">' . icon('trending-up', 'text-[16px]') . e($module['cameras']['online']) . ' Uptime Operasional</span><span class="text-label-sm text-on-surface-variant">Live Feed OK</span></div>') ?>
            <?= $statCard('<div class="flex items-start justify-between"><div class="space-y-1"><div class="flex items-center gap-2"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">TEMUAN RUSAK / KENDALA IP</span></div><div class="flex items-baseline gap-2 mt-1"><span class="text-headline-xl text-primary font-bold">4</span><span class="text-label-lg text-on-surface-variant font-medium">Insiden</span></div></div><div class="w-10 h-10 rounded-lg bg-error-container flex items-center justify-center text-on-error-container">' . icon('circle-alert', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between text-body-sm"><span class="text-on-surface-variant truncate pr-2">2 Offline, 1 Lensa, 1 Flap</span>' . partial('badge', ['label' => 'Perlu Tindak Lanjut', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-error animate-pulse', 'class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-on-error-container text-label-sm whitespace-nowrap']) . '</div>') ?>
            <?= $statCard('<div class="flex items-start justify-between"><div class="space-y-1"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">KESESUAIAN JAM HARIAN</span><div class="flex items-baseline gap-2 mt-1"><span class="text-headline-xl text-on-surface font-bold">98.5%</span><span class="text-label-md text-secondary font-semibold">Terkalibrasi</span></div></div><div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center text-secondary">' . icon('refresh-ccw', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between text-body-sm text-on-surface-variant"><span class="truncate flex items-center gap-1">' . icon('refresh-cw', 'text-[16px] text-tertiary') . 'Tersinkronisasi NTP Server RS</span><span class="text-label-sm text-on-surface font-medium whitespace-nowrap">1 Drift Fix</span></div>') ?>
        </div>

        <div class="w-full bg-surface-container-lowest rounded-xl overflow-hidden shadow-rs" data-filter-scope>
            <div class="p-space-lg flex flex-col lg:flex-row lg:items-center justify-between gap-space-md bg-surface-bright">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-2"><?= icon('table-2', 'text-secondary text-[24px]') ?><h2 class="text-headline-lg text-on-surface">Log Riwayat Pemeriksaan CCTV Harian</h2></div>
                    <p class="text-body-md text-on-surface-variant">Daftar data hasil input pemeriksaan kamera CCTV per sesi dinas, unit ruangan, dan catatan teknis.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    <?= partial('button', ['label' => 'Export Excel (.xlsx)', 'icon' => 'sheet', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-low text-secondary text-headline-sm hover:bg-surface-container transition-colors rsab-action shadow-rs', 'attrs' => ['data-export' => 'xlsx']]) ?>
                    <?= partial('button', ['label' => 'Export PDF Rekap Bulanan', 'icon' => 'file-text', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-low text-primary text-headline-sm hover:bg-surface-container transition-colors rsab-action shadow-rs', 'attrs' => ['data-export' => 'pdf']]) ?>
                    <?= partial('button', ['label' => 'Cetak Rekap', 'icon' => 'printer', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container text-on-surface text-headline-sm hover:bg-surface-container-high transition-colors rsab-action shadow-rs', 'attrs' => ['data-print' => true]]) ?>
                </div>
            </div>
            <div class="p-space-md bg-surface-container-low/60 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-space-sm items-center">
                <?= $filterSelect('Periode Bulan', 'month', $module['filters']['month']) ?>
                <?= $filterSelect('Kesesuaian Jam', 'ntp', $module['filters']['ntp']) ?>
                <?= $filterSelect('Kondisi Kamera', 'camera', $module['filters']['camera']) ?>
                <?= $filterSelect('Zona Gedung', 'zone', $module['filters']['zone']) ?>
                <?= partial('field', [
                    'class' => 'relative', 'label' => 'Pencarian Cepat', 'labelClass' => 'block text-label-sm text-on-surface-variant uppercase mb-1 tracking-[.04em]',
                    'id' => 'filter-search', 'placeholder' => 'Cari IP, lokasi, nama petugas...', 'boxClass' => 'relative', 'attrs' => ['data-filter-search' => true],
                    'controlClass' => 'w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-lowest text-on-surface text-body-sm focus:outline-none focus:ring-2 focus:ring-secondary',
                    'iconRight' => ['search', 'absolute left-2.5 top-2.5 text-on-surface-variant text-[20px]'],
                ]) ?>
            </div>
            <?= partial('table', [
                'wrapClass' => 'w-full overflow-x-auto rsab-table-wrap', 'class' => 'w-full text-left border-collapse min-w-[1200px]',
                'theadRowClass' => 'bg-surface-container-high text-on-surface',
                'columns' => array_map(static fn (string $label): array => ['label' => $label, 'class' => 'py-3 px-4 text-label-md uppercase tracking-wider'], ['No & Waktu Pemeriksaan', 'Zona & Lokasi Pemeriksaan', 'Temuan CCTV Rusak & IP', 'Kesesuaian Jam CCTV', 'Lampiran Bukti', 'Status NVR & Catatan Teknis', 'Petugas Verifikator']),
                'rows' => $module['entries'], 'row' => 'rows/rekap-cctv', 'empty' => 'Tidak ada data pemeriksaan CCTV untuk filter ini.',
            ]) ?>
            <div class="p-space-md bg-surface-bright flex flex-col sm:flex-row items-center justify-between gap-space-sm text-label-sm text-on-surface-variant">
                <div class="flex items-center gap-2"><span>Menampilkan <strong class="text-on-surface font-semibold" data-filter-count>1 - <?= e(count($module['entries'])) ?></strong> dari <strong class="text-on-surface font-semibold"><?= e($module['total']) ?></strong> data rekaman monitoring CCTV</span></div>
                <div class="flex items-center gap-1">
                    <button class="px-2.5 py-1.5 rounded-lg bg-surface-container text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface transition-colors disabled:opacity-50" disabled type="button"><?= icon('chevron-left', 'text-[16px] align-middle') ?> <span class="align-middle">Sebelumnya</span></button>
                    <button class="w-8 h-8 rounded-lg bg-primary text-on-primary font-bold" type="button" aria-current="page">1</button>
                    <?php foreach ([2, 3, 4] as $pageNo): ?>
                    <button class="w-8 h-8 rounded-lg bg-surface-container-low text-on-surface hover:bg-surface-container transition-colors" type="button" data-page-btn><?= e($pageNo) ?></button>
                    <?php endforeach; ?>
                    <span class="px-1 text-outline-variant">...</span>
                    <button class="px-2.5 py-1.5 rounded-lg bg-surface-container text-on-surface hover:bg-surface-container-high transition-colors" type="button" data-page-btn><span class="align-middle">Selanjutnya</span> <?= icon('chevron-right', 'text-[16px] align-middle') ?></button>
                </div>
            </div>
        </div>
        <div class="w-full py-space-sm text-center text-label-md text-on-surface-variant">&copy; <?= e(date('Y')) ?> RS Awal Bros Botania</div>
    </div>
</div>
</main>
