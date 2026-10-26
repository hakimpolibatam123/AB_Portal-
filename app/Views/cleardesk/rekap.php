<?php
$filterSelect = static fn (string $label, string $key, array $options): string => partial('field', [
    'class' => 'flex flex-col gap-1', 'label' => $label, 'labelClass' => 'text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold',
    'control' => 'select', 'id' => 'filter-' . $key, 'options' => $options, 'boxClass' => 'relative', 'attrs' => ['data-filter' => $key],
    'controlClass' => 'w-full bg-surface-container-low rounded-lg px-3 py-2 text-body-sm text-on-surface appearance-none focus:bg-surface-container-lowest focus:ring-2 focus:ring-tertiary/20 cursor-pointer',
    'iconRight' => ['chevron-down', 'absolute right-2.5 top-2.5 text-[18px] text-on-surface-variant pointer-events-none'],
]);
$statCard = static fn (string $body): string => '<div class="rounded-xl bg-surface-container-lowest p-space-md flex flex-col justify-between relative overflow-hidden group transition-shadow shadow-rs">' . $body . '</div>';
$progress = static fn (string $bar, string $width): string => '<div class="w-full bg-surface-container-high h-1.5 rounded-full mt-2 overflow-hidden"><div class="' . e($bar) . ' h-full rounded-full" style="width: ' . e($width) . '"></div></div>';
ob_start(); ?>
<div class="flex flex-wrap sm:flex-nowrap items-center gap-space-md shrink-0">
    <div class="flex items-center gap-3 px-space-md py-2.5 rounded-lg bg-surface-container-lowest/10 backdrop-blur-md text-on-tertiary">
        <div class="w-9 h-9 rounded-lg bg-surface-container-lowest/15 flex items-center justify-center"><?= icon('calendar', 'text-[20px] text-tertiary-fixed') ?></div>
        <div class="flex flex-col leading-tight">
            <span class="text-label-sm uppercase tracking-wider text-tertiary-fixed-dim">Periode Audit</span>
            <span class="text-headline-sm font-semibold">Oktober 2026 (Aktif)</span>
        </div>
    </div>
    <?= partial('button', ['label' => 'Input Form Baru', 'href' => url('/form/cleardesk'), 'icon' => 'list-checks', 'iconClass' => 'text-[20px]', 'class' => 'inline-flex items-center gap-2 px-space-md py-3 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed hover:bg-tertiary-fixed-dim transition-all text-headline-sm font-semibold active:scale-95 shadow-rs']) ?>
</div>
<?php $heroAside = ob_get_clean(); ?>
<main id="main-content" class="w-full bg-surface min-h-[calc(100vh-56px)]">
<div class="flex flex-col w-full">
    <div class="w-full px-margin py-space-md flex flex-col gap-space-lg">
        <?= partial('breadcrumb', [
            'class' => 'flex items-center gap-2 text-body-sm text-on-surface-variant',
            'separator' => ['icon' => 'chevron-right', 'class' => 'text-[14px]'],
            'items' => [
                ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'class' => 'hover:text-primary transition-colors'],
                ['label' => 'Monitoring Harian', 'href' => url('/'), 'class' => 'hover:text-primary transition-colors'],
                ['label' => 'Clear Desk & Screen', 'href' => url('/form/cleardesk'), 'class' => 'hover:text-primary transition-colors'],
                ['label' => 'Rekap Hasil Inspeksi', 'class' => 'font-semibold text-on-surface'],
            ],
        ]) ?>

        <?= partial('page-header', [
            'class' => 'relative overflow-hidden rounded-xl p-space-lg',
            'innerClass' => 'flex flex-col lg:flex-row lg:items-center justify-between gap-space-lg', 'introClass' => 'flex flex-col gap-space-xs max-w-3xl',
            'eyebrow' => partial('badge', ['tag' => 'div', 'label' => 'AUDIT KEAMANAN INFORMASI FISIK • RS AWAL BROS BOTANIA', 'dot' => 'w-1.5 h-1.5 rounded-full bg-tertiary-fixed animate-ping', 'class' => 'inline-flex items-center gap-2 self-start px-3 py-1 rounded-full bg-tertiary-container/30 border-0 text-on-tertiary-container text-label-sm tracking-wider uppercase backdrop-blur-xs whitespace-nowrap']),
            'title' => 'Rekap & Riwayat Hasil Clear Desk & Clear Screen', 'titleClass' => 'mt-1',
            'description' => 'Log rekapitulasi harian inspeksi kepatuhan penataan meja kerja (Clear Desk) dan penguncian layar monitor (Clear Screen) untuk pemenuhan standar ISO 27001 & Akreditasi KARS.',
            'descriptionClass' => 'text-body-md text-on-tertiary/85 max-w-2xl',
            'aside' => $heroAside,
        ]) ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md">
            <?= $statCard('<div class="flex items-start justify-between"><div class="flex flex-col"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">Total Workstation Terinspeksi</span><span class="text-headline-xl font-bold text-on-surface mt-1">' . e($module['total']) . ' <span class="text-body-md font-medium text-on-surface-variant">Unit</span></span></div><div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center text-secondary">' . icon('monitor', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between text-on-surface-variant text-body-sm"><span>Akumulasi Semua Lantai &amp; Poli</span><span class="text-label-md font-semibold text-secondary">100% Target</span></div>' . $progress('bg-secondary', '100%')) ?>
            <?= $statCard('<div class="flex items-start justify-between"><div class="flex flex-col"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">Tingkat Kepatuhan Total</span><span class="text-headline-xl font-bold text-tertiary mt-1">96.5%</span></div><div class="w-10 h-10 rounded-lg bg-secondary-container/40 flex items-center justify-center text-tertiary">' . icon('shield-check', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between text-on-surface-variant text-body-sm"><span>Standar ISO 27001 (Min. 95%)</span><span class="text-label-md font-semibold text-tertiary">+1.2% mo/mo</span></div>' . $progress('bg-tertiary', '96.5%')) ?>
            <?= $statCard('<div class="flex items-start justify-between"><div class="flex flex-col"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">Temuan Pelanggaran</span><span class="text-headline-xl font-bold text-primary mt-1">5 <span class="text-body-md font-medium text-on-surface-variant">Kasus</span></span></div><div class="w-10 h-10 rounded-lg bg-error-container/40 flex items-center justify-center text-primary">' . icon('triangle-alert', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between">' . partial('badge', ['label' => 'Semua Telah Ditegur & Selesai', 'icon' => 'check-check', 'iconClass' => 'text-[14px]', 'class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-fixed/40 text-tertiary text-label-sm font-medium whitespace-nowrap']) . '</div>' . $progress('bg-primary', '3.5%')) ?>
            <?= $statCard('<div class="flex items-start justify-between"><div class="flex flex-col"><span class="text-label-sm text-on-surface-variant uppercase tracking-wider">Audit Terakhir Dilakukan</span><span class="text-headline-md font-bold text-on-surface mt-2">15 Okt 2026</span><span class="text-label-sm text-secondary font-medium mt-0.5">10:18:45 WIB</span></div><div class="w-10 h-10 rounded-lg bg-surface-container-high flex items-center justify-center text-on-surface">' . icon('history', 'text-[22px]') . '</div></div>'
                . '<div class="mt-4 pt-3 flex items-center justify-between text-on-surface-variant text-body-sm"><span>Petugas Lapangan</span><span class="text-label-md font-semibold text-on-surface">IT Botania Superadmin</span></div>' . $progress('bg-tertiary', '100%')) ?>
        </div>

        <div class="contents" data-filter-scope>
            <div class="flex flex-col gap-space-md bg-surface-container-lowest p-space-md rounded-xl shadow-rs">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pb-space-sm">
                    <div>
                        <h2 class="text-headline-md font-bold text-on-surface">Log Riwayat Pemeriksaan Clear Desk &amp; Screen Harian</h2>
                        <p class="text-body-sm text-on-surface-variant mt-0.5">Daftar hasil inspeksi berkala per unit kerja, data MAC Address, IP, OS, dan status kepatuhan fisik.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <?= partial('button', ['label' => 'Export Excel (.xlsx)', 'icon' => 'sheet', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-tertiary text-body-sm font-semibold transition-colors rsab-action', 'attrs' => ['data-export' => 'xlsx']]) ?>
                        <?= partial('button', ['label' => 'Export PDF Rekap Bulanan', 'icon' => 'file-text', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-primary text-body-sm font-semibold transition-colors rsab-action', 'attrs' => ['data-export' => 'pdf']]) ?>
                        <?= partial('button', ['label' => 'Cetak Rekap', 'icon' => 'printer', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-on-surface text-body-sm font-semibold transition-colors rsab-action', 'attrs' => ['data-print' => true]]) ?>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-space-sm pt-space-xs">
                    <?= $filterSelect('Periode Bulan', 'month', $module['filters']['month']) ?>
                    <?= $filterSelect('Status Kepatuhan', 'status', $module['filters']['status']) ?>
                    <?= $filterSelect('Lokasi / Unit Kerja', 'unit', $module['filters']['unit']) ?>
                    <?= $filterSelect('Sistem Operasi', 'os', $module['filters']['os']) ?>
                    <?= partial('field', [
                        'class' => 'flex flex-col gap-1', 'label' => 'Pencarian Cepat', 'labelClass' => 'text-label-sm text-on-surface-variant uppercase tracking-wider font-semibold',
                        'id' => 'filter-search', 'placeholder' => 'Cari IP, MAC, Lokasi, Petugas...', 'boxClass' => 'relative', 'attrs' => ['data-filter-search' => true],
                        'controlClass' => 'w-full bg-surface-container-low rounded-lg pl-9 pr-3 py-2 text-body-sm text-on-surface focus:bg-surface-container-lowest focus:ring-2 focus:ring-tertiary/20 outline-none',
                        'iconLeft' => ['search', 'absolute left-3 top-2.5 text-[18px] text-on-surface-variant pointer-events-none'],
                    ]) ?>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-xl overflow-hidden flex flex-col shadow-rs">
                <?= partial('table', [
                    'wrapClass' => 'overflow-x-auto rsab-table-wrap', 'class' => 'w-full text-left border-collapse',
                    'theadRowClass' => 'bg-surface-container-low text-on-surface-variant text-label-md', 'tbodyClass' => 'text-body-sm text-on-surface',
                    'columns' => array_map(static fn (string $label): array => ['label' => $label, 'class' => 'py-3 px-space-md whitespace-nowrap'], ['NO & WAKTU AUDIT', 'LOKASI KOMPUTER', 'MAC ADDRESS', 'IP ADDRESS', 'SISTEM OPERASI', 'STATUS KEPATUHAN', 'BUKTI AUDIT', 'TINDAK LANJUT & OBSERVASI', 'AUDITOR IT']),
                    'rows' => $module['entries'], 'row' => 'rows/rekap-cleardesk', 'empty' => 'Tidak ada data inspeksi untuk filter ini.',
                ]) ?>
                <div class="flex flex-col sm:flex-row items-center justify-between gap-space-sm px-space-md py-space-sm bg-surface-container-low/40">
                    <div class="text-body-sm text-on-surface-variant">Menampilkan <span class="font-semibold text-on-surface" data-filter-count>1 - <?= e(count($module['entries'])) ?></span> dari <span class="font-semibold text-on-surface"><?= e($module['total']) ?></span> data rekaman kepatuhan</div>
                    <div class="flex items-center gap-1 text-body-sm">
                        <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-on-surface-variant hover:bg-surface-container-high disabled:opacity-40 disabled:pointer-events-none" disabled type="button" aria-label="Halaman sebelumnya"><?= icon('chevron-left', 'text-[18px]') ?></button>
                        <button class="w-8 h-8 rounded-lg font-semibold flex items-center justify-center bg-rs-teal-700 text-white border-rs-teal-700 hover:bg-rs-teal-800" type="button" aria-current="page">1</button>
                        <?php foreach ([2, 3, 4] as $pageNo): ?>
                        <button class="w-8 h-8 rounded-lg text-on-surface hover:bg-surface-container-high flex items-center justify-center" type="button" data-page-btn><?= e($pageNo) ?></button>
                        <?php endforeach; ?>
                        <span class="px-1 text-on-surface-variant">...</span>
                        <button class="w-8 h-8 rounded-lg text-on-surface hover:bg-surface-container-high flex items-center justify-center" type="button" data-page-btn>21</button>
                        <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-on-surface hover:bg-surface-container-high" type="button" data-page-btn aria-label="Halaman berikutnya"><?= icon('chevron-right', 'text-[18px]') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
