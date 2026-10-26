<?php
$stats = $module['stats'];
$filterSelect = static fn (string $label, string $key, array $options): string => partial('field', [
    'class' => 'flex flex-col space-y-1', 'label' => $label, 'labelClass' => 'font-semibold uppercase text-on-surface-variant tracking-[.04em]',
    'control' => 'select', 'id' => 'filter-' . $key, 'options' => $options, 'boxClass' => 'relative', 'attrs' => ['data-filter' => $key],
    'controlClass' => 'w-full bg-surface-container-lowest text-on-surface rounded-lg py-2 pl-3 pr-8 focus:ring-2 focus:ring-secondary/40 focus:outline-none appearance-none cursor-pointer',
    'iconRight' => ['chevron-down', 'absolute right-2.5 top-2.5 pointer-events-none text-on-surface-variant text-[18px]'],
]);
ob_start(); ?>
<div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
    <div class="flex items-center gap-2.5 px-4 py-2.5 rounded-lg bg-inverse-surface/40 backdrop-blur-md text-on-secondary border-0 shadow-rs">
        <?= icon('calendar-days', 'text-tertiary-fixed text-[20px]') ?>
        <div class="flex flex-col text-left">
            <span class="text-secondary-fixed uppercase tracking-wider">Periode Audit</span>
            <span class="font-bold text-on-secondary">Oktober 2026 (Aktif)</span>
        </div>
    </div>
    <?= partial('button', ['label' => 'Input Form Baru', 'href' => url('/form/suhu'), 'icon' => 'circle-plus', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-tertiary-container hover:bg-tertiary text-on-tertiary font-semibold transition-all active:scale-[0.98] shadow-rs']) ?>
</div>
<?php $heroAside = ob_get_clean(); ?>
<main id="main-content" class="max-w-[1720px] w-full mx-auto px-4 lg:px-8 py-6 space-y-6 min-h-[calc(100vh-80px)]">
<div class="flex flex-col w-full space-y-6">
    <?= partial('breadcrumb', [
        'class' => 'flex items-center space-x-2 text-on-surface-variant',
        'separator' => ['icon' => 'chevron-right', 'class' => 'text-outline text-[16px]'],
        'items' => [
            ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'class' => 'hover:text-primary transition-colors cursor-pointer'],
            ['label' => 'Monitoring Harian', 'class' => 'hover:text-primary transition-colors cursor-pointer'],
            ['label' => 'Suhu Server', 'href' => url('/form/suhu'), 'class' => 'hover:text-primary transition-colors cursor-pointer'],
            ['label' => 'Rekap Hasil Inputan', 'class' => 'text-secondary font-semibold'],
        ],
    ]) ?>

    <?= partial('page-header', [
        'tag' => 'section', 'class' => 'relative overflow-hidden rounded-xl p-6 lg:p-8',
        'innerClass' => 'flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6', 'introClass' => 'space-y-3 max-w-3xl',
        'eyebrow' => partial('badge', ['tag' => 'div', 'label' => 'Sensor Data Center • RS Awal Bros Botania', 'dot' => 'w-2 h-2 rounded-full bg-tertiary-fixed animate-ping', 'class' => 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-lowest/15 backdrop-blur-md text-secondary-fixed uppercase tracking-[.04em] whitespace-nowrap']),
        'title' => 'Rekap & Riwayat Rata - Rata Suhu Server Perbulan',
        'description' => 'Log rekapitulasi data suhu server bulanan, tren fluktuasi pendingin data center, dan audit fasilitas IT rumah sakit untuk menjamin kestabilan layanan HIS & Telemetri klinis.',
        'descriptionClass' => 'text-on-secondary/90 max-w-2xl',
        'aside' => $heroAside,
    ]) ?>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" aria-label="Ringkasan rekap suhu">
        <div class="bg-surface-container-lowest rounded-xl p-5 transition-shadow relative overflow-hidden flex flex-col justify-between shadow-rs">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <span class="uppercase text-on-surface-variant font-semibold tracking-wider">Rata-rata Suhu Bulan Ini</span>
                    <div class="flex items-baseline gap-2 mt-1"><span class="text-on-surface font-bold tracking-tight"><?= e($stats['average']) ?></span><span class="text-secondary font-semibold">°C</span></div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-secondary-container/40 text-tertiary flex items-center justify-center"><?= icon('thermometer', 'text-[22px]') ?></div>
            </div>
            <div class="flex items-center justify-between pt-2">
                <?= partial('badge', ['label' => 'Normal (18 - 24°C)', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-tertiary', 'class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary-container/50 text-tertiary font-semibold whitespace-nowrap']) ?>
                <span class="text-on-surface-variant">Akumulasi 30 Hari</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full mt-3 overflow-hidden"><div class="bg-tertiary h-full rounded-full" style="width: 58%"></div></div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-5 transition-shadow relative overflow-hidden flex flex-col justify-between shadow-rs">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <span class="uppercase text-on-surface-variant font-semibold tracking-wider">Suhu Minimum Tercatat</span>
                    <div class="flex items-baseline gap-2 mt-1"><span class="text-on-surface font-bold tracking-tight"><?= e($stats['minimum']) ?></span><span class="text-secondary font-semibold">°C</span></div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-surface-container text-secondary flex items-center justify-center"><?= icon('thermometer', 'text-[22px]') ?></div>
            </div>
            <div class="flex items-center justify-between pt-2">
                <?= partial('badge', ['label' => 'Sensors Data Center', 'class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-sm bg-surface-container text-on-surface']) ?>
                <span class="text-secondary font-semibold">Kondisi Optimal</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full mt-3 overflow-hidden"><div class="bg-secondary h-full rounded-full" style="width: 32%"></div></div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-5 transition-shadow relative overflow-hidden flex flex-col justify-between shadow-rs">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <span class="uppercase text-on-surface-variant font-semibold tracking-wider">Suhu Maksimum Tercatat</span>
                    <div class="flex items-baseline gap-2 mt-1"><span class="text-primary font-bold tracking-tight"><?= e($stats['maximum']) ?></span><span class="text-primary font-semibold">°C</span></div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-error-container text-on-error-container flex items-center justify-center"><?= icon('triangle-alert', 'text-[22px]') ?></div>
            </div>
            <div class="flex items-center justify-between pt-2">
                <?= partial('badge', ['label' => '1 Insiden Spike', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-error', 'class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-semibold whitespace-nowrap']) ?>
                <span class="text-on-surface-variant">Durasi ±15 Menit</span>
            </div>
            <div class="w-full bg-surface-container h-1 rounded-full mt-3 overflow-hidden"><div class="bg-primary h-full rounded-full" style="width: 82%"></div></div>
        </div>
        <div class="bg-surface-container-lowest rounded-xl p-5 transition-shadow relative overflow-hidden flex flex-col justify-between shadow-rs">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <span class="uppercase text-on-surface-variant font-semibold tracking-wider">Pencatatan Terakhir</span>
                    <div class="flex flex-col mt-1"><span class="text-on-surface font-bold tracking-tight"><?= e($stats['last_date']) ?></span><span class="text-secondary font-semibold"><?= e($stats['last_time']) ?></span></div>
                </div>
                <div class="w-10 h-10 rounded-lg bg-secondary-container/30 text-secondary flex items-center justify-center"><?= icon('clock', 'text-[22px]') ?></div>
            </div>
            <div class="flex items-center justify-between pt-2 bg-surface-container-low px-2.5 py-1.5 rounded-lg">
                <span class="text-on-surface-variant">Oleh:</span>
                <span class="font-bold text-on-surface"><?= e($stats['last_by']) ?></span>
            </div>
        </div>
    </section>

    <section class="bg-surface-container-lowest rounded-xl overflow-hidden space-y-4 shadow-rs" data-filter-scope aria-labelledby="rekap-title">
        <div class="p-5 lg:p-6 pb-2 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <span class="w-2.5 h-2.5 rounded-full bg-tertiary shadow-rs"></span>
                <div>
                    <h2 class="text-on-surface font-bold" id="rekap-title">Log Data Riwayat Suhu Server Bulanan</h2>
                    <p class="text-on-surface-variant">Daftar data hasil input suhu server per sesi dinas, unit pendingin, dan lampiran berkas.</p>
                </div>
            </div>
            <div class="flex items-center flex-wrap gap-2">
                <?= partial('button', ['label' => 'Export Excel (.xlsx)', 'icon' => 'sheet', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-low hover:bg-secondary-container/40 text-secondary font-semibold transition-colors rsab-action', 'attrs' => ['data-export' => 'xlsx']]) ?>
                <?= partial('button', ['label' => 'Export PDF Rekap Bulanan', 'icon' => 'file-text', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-error-container/60 hover:bg-error-container text-on-error-container font-semibold transition-colors rsab-action', 'attrs' => ['data-export' => 'pdf']]) ?>
                <?= partial('button', ['label' => 'Cetak Rekap', 'icon' => 'printer', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface-variant font-semibold transition-colors rsab-action', 'attrs' => ['data-print' => true]]) ?>
            </div>
        </div>
        <div class="px-5 lg:px-6">
            <div class="bg-surface-container-low p-4 rounded-xl grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <?= $filterSelect('Periode Bulan', 'month', $module['filters']['month']) ?>
                <?= $filterSelect('Status Suhu', 'status', $module['filters']['status']) ?>
                <?= $filterSelect('Lokasi Sensor', 'location', $module['filters']['location']) ?>
                <?= partial('field', [
                    'class' => 'flex flex-col space-y-1', 'label' => 'Pencarian Cepat', 'labelClass' => 'font-semibold uppercase text-on-surface-variant tracking-[.04em]',
                    'id' => 'filter-search', 'placeholder' => 'Cari petugas, tanggal, lokasi...', 'boxClass' => 'relative', 'attrs' => ['data-filter-search' => true, 'type' => 'text'],
                    'controlClass' => 'w-full bg-surface-container-lowest text-on-surface rounded-lg py-2 pl-9 pr-3 focus:ring-2 focus:ring-secondary/40 focus:outline-none',
                    'iconRight' => ['search', 'absolute left-2.5 top-2.5 text-on-surface-variant text-[18px]'],
                ]) ?>
            </div>
        </div>
        <?= partial('table', [
            'wrapClass' => 'overflow-x-auto px-5 lg:px-6 rsab-table-wrap', 'class' => 'w-full text-left border-collapse',
            'theadRowClass' => 'bg-surface-container-low text-on-surface-variant uppercase tracking-wider', 'tbodyClass' => 'text-on-surface',
            'columns' => [
                ['label' => 'No & Waktu Pencatatan', 'class' => 'py-3 px-4 rounded-l-lg font-semibold'],
                ['label' => 'Lokasi Sensor / Rak', 'class' => 'py-3 px-4 font-semibold'],
                ['label' => 'Rata-rata Suhu', 'class' => 'py-3 px-4 font-semibold text-center'],
                ['label' => 'Lampiran Bukti', 'class' => 'py-3 px-4 font-semibold'],
                ['label' => 'Kondisi Pendingin & Catatan', 'class' => 'py-3 px-4 font-semibold'],
                ['label' => 'Petugas IT', 'class' => 'py-3 px-4 rounded-r-lg font-semibold'],
            ],
            'rows' => $module['entries'], 'row' => 'rows/rekap-suhu', 'empty' => 'Tidak ada data suhu untuk filter ini.',
        ]) ?>
        <div class="p-4 lg:p-6 pt-3 flex flex-col sm:flex-row items-center justify-between gap-4">
            <span class="text-on-surface-variant">Menampilkan <strong class="text-on-surface font-semibold" data-filter-count>1 - <?= e(count($module['entries'])) ?></strong> dari <strong class="text-on-surface font-semibold"><?= e($module['total']) ?></strong> data rekaman suhu bulanan</span>
            <div class="flex items-center [&>:not(:first-child)]:ml-1">
                <button class="px-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface-variant hover:bg-surface-container font-semibold transition-colors disabled:opacity-50" disabled type="button">Sebelumnya</button>
                <button class="w-8 h-8 rounded-lg bg-secondary text-on-secondary font-bold" type="button" aria-current="page">1</button>
                <?php foreach ([2, 3, 4] as $pageNo): ?>
                <button class="w-8 h-8 rounded-lg bg-surface-container-low hover:bg-surface-container text-on-surface font-medium transition-colors" type="button" data-page-btn><?= e($pageNo) ?></button>
                <?php endforeach; ?>
                <button class="px-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface hover:bg-surface-container font-semibold transition-colors" type="button" data-page-btn>Selanjutnya</button>
            </div>
        </div>
    </section>
</div>
</main>
