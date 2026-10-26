<?php
$profile = config('dashboard')['profile'];
$circumference = 2 * M_PI * 30;
ob_start(); ?>
<div class="flex flex-col sm:flex-row items-center gap-5 bg-[#022c22]/80 border border-teal-500/30 rounded-xl p-3.5 sm:p-4 backdrop-blur-xs shadow-[inset_0_2px_4px_0_rgb(0_0_0/0.05)]" aria-label="Ringkasan progres harian">
    <div class="flex items-center gap-4">
        <div class="relative flex items-center justify-center">
            <svg class="w-20 h-20 -rotate-90" viewBox="0 0 72 72" aria-hidden="true">
                <circle cx="36" cy="36" fill="transparent" r="30" stroke="rgba(20, 184, 166, 0.2)" stroke-width="6"></circle>
                <circle class="transition-all duration-1000" cx="36" cy="36" fill="transparent" r="30" stroke="#10b981" stroke-dasharray="<?= e(number_format($circumference, 1, '.', '')) ?>" stroke-dashoffset="<?= e(number_format($circumference * (1 - $progress['ratio']), 1, '.', '')) ?>" stroke-linecap="round" stroke-width="6"></circle>
            </svg>
            <div class="absolute flex flex-col items-center justify-center text-center">
                <span class="text-lg font-bold text-white leading-none"><?= e($progress['percentage']) ?>%</span>
                <span class="text-[11px] font-bold text-teal-300 uppercase tracking-tighter mt-0.5 leading-[1.45]">Terisi</span>
            </div>
        </div>
        <div class="flex flex-col">
            <div class="flex items-center space-x-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-bold text-teal-200 uppercase tracking-[.04em]">Progres Harian</span>
            </div>
            <div class="flex items-baseline space-x-1 mt-0.5">
                <span class="text-2xl font-bold text-white"><?= e($progress['completed']) ?></span>
                <span class="text-sm font-semibold text-teal-300/80">/ <?= e($progress['target']) ?> Form</span>
            </div>
            <p class="text-[11px] text-teal-200/70 leading-[1.45]"><?= $progress['remaining'] > 0 ? e($progress['remaining']) . ' Form belum diinput hari ini' : 'Semua form hari ini sudah diisi' ?></p>
        </div>
    </div>
    <div class="h-12 w-px bg-teal-800/60 hidden sm:block"></div>
    <div class="flex flex-col justify-center space-y-1.5 w-full sm:w-auto">
        <div class="flex items-center justify-between gap-4 text-[12px] leading-[1.5]">
            <span class="text-teal-200/90 font-medium">Capaian Target</span>
            <span class="text-emerald-400 font-bold font-mono"><?= e($progress['completed']) ?>/<?= e($progress['target']) ?> Selesai</span>
        </div>
        <div class="w-full sm:w-44 bg-teal-950 rounded-full h-2 overflow-hidden border border-teal-700/50" role="progressbar" aria-label="Capaian pengisian form" aria-valuemin="0" aria-valuemax="<?= e(max(1, $progress['target'])) ?>" aria-valuenow="<?= e($progress['completed']) ?>">
            <div class="bg-linear-to-r from-teal-400 to-emerald-400 h-full rounded-full" style="width: <?= e(number_format($progress['ratio'] * 100, 2, '.', '')) ?>%"></div>
        </div>
        <div class="flex items-center gap-1.5 pt-0.5">
            <span class="px-1.5 py-0.5 rounded-sm bg-emerald-950/80 border border-emerald-500/40 text-[11px] font-semibold text-emerald-300 leading-[1.45]">Target: 100% Hari Ini</span>
        </div>
    </div>
</div>
<?php $progressPanel = ob_get_clean(); ?>
<main id="main-content" class="grow max-w-[1720px] w-full mx-auto px-4 lg:px-8 py-4 space-y-5">
    <div class="bg-[#e6f7f5] border border-[#a2e6df] text-[#0d6e66] px-4 py-2.5 rounded-lg flex items-center space-x-3 text-xs shadow-rs" role="note">
        <?= icon('shield-check', 'text-base shrink-0 text-[#0d9488]') ?>
        <p><strong class="font-semibold text-[#09524c]">Terima kasih telah menjaga kerahasiaan data internal RS Awal Bros Botania.</strong> Sistem ini berisi informasi sensitif pasien, finansial, dan operasional rumah sakit. Mohon gunakan akses Anda dengan bijak dan segera laporkan ke IT apabila menemukan aktivitas yang mencurigakan.</p>
    </div>

    <?= partial('page-header', [
        'class' => 'relative rounded-2xl p-6 sm:p-8 md:p-10 overflow-hidden',
        'innerClass' => 'flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6',
        'introClass' => 'space-y-1',
        'eyebrow' => '<div class="flex items-center space-x-2 text-teal-300/90 text-xs font-semibold tracking-wider uppercase"><span>— RS AWAL BROS BOTANIA</span></div>',
        'title' => 'Selamat Datang, ' . $profile['name'],
        'titleHtml' => 'Selamat Datang,<br><span class="text-teal-200">' . e($profile['name']) . '</span>',
        'description' => 'Sistem Monitoring Terpadu • ' . date('l, j F Y'),
        'descriptionClass' => 'text-teal-200/80 text-xs sm:text-sm font-normal pt-1',
        'aside' => $progressPanel,
    ]) ?>

    <?php foreach ($sections as $key => $section): ?>
    <section class="space-y-3 <?= e($section['class']) ?>" aria-labelledby="section-<?= e($key) ?>">
        <div class="flex items-center space-x-2 text-xs font-bold text-slate-500 uppercase tracking-wider" id="section-<?= e($key) ?>">
            <?= icon($section['icon'], 'text-slate-400 text-sm') ?>
            <span><?= e($section['title']) ?></span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 <?= e($section['grid']) ?> gap-3.5">
            <?php foreach ($section['cards'] as $card): ?>
            <a class="monitor-card group" href="<?= e($card['href'] !== null ? url($card['href']) : '#') ?>"<?= attrs(['data-unavailable' => $card['href'] === null ? $card['title'] : null]) ?>>
                <span aria-label="<?= $card['badge'] === 'live' ? 'LIVE' : 'NEW' ?>" class="rsab-badge rsab-badge--<?= e($card['badge']) ?>"><?= $card['badge'] === 'live' ? 'LIVE' : 'NEW' ?></span>
                <div class="monitor-card__icon"><?= icon($card['icon']) ?></div>
                <h3><?= e($card['title']) ?></h3>
                <p class="font-medium"><?= e($card['description']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>
