<main id="main-content" class="page-shell flex-1 space-y-5 py-4">
    <div class="security-notice flex items-start gap-3 rounded-lg border border-[#a2e6df] bg-[#e6f7f5] px-4 py-2.5 text-[11.7px] leading-relaxed text-[#0d6e66] shadow-sm" role="note">
        <?= icon('shield', 'mt-0.5 size-4 shrink-0 text-teal-600') ?>
        <p><strong class="font-semibold">Terima kasih telah menjaga kerahasiaan data internal RS Awal Bros Botania.</strong> Sistem ini berisi informasi sensitif pasien, finansial, dan operasional rumah sakit. Mohon gunakan akses Anda dengan bijak dan segera laporkan ke IT apabila menemukan aktivitas yang mencurigakan.</p>
    </div>

    <section aria-labelledby="welcome-title" class="welcome-hero hero-pattern overflow-hidden rounded-2xl border border-[#0d594e] bg-brand-dark p-6 text-white shadow-lg sm:p-8 md:p-10">
        <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
            <div class="min-w-0 space-y-1">
                <p class="flex items-center gap-2 text-[11.7px] font-semibold uppercase tracking-wider text-teal-300/90"><span class="h-px w-3 shrink-0 bg-teal-300/90" aria-hidden="true"></span>RS Awal Bros Botania</p>
                <h1 id="welcome-title" class="font-serif text-3xl font-bold leading-tight tracking-tight sm:text-4xl lg:text-[42px]">Selamat Datang,<br><span class="text-teal-200">IT Botania</span></h1>
                <p class="pt-1 text-xs font-normal text-teal-200/80 sm:text-sm">Sistem Monitoring Terpadu</p>
            </div>

            <?php require BASE_PATH . '/app/Views/components/progress-summary.php'; ?>
        </div>
    </section>

    <?php if ($editable): ?>
    <p class="demo-notice" role="note">
        <?= card_icon('info', 'size-4 shrink-0') ?>
        <span><strong>Data contoh.</strong> Perubahan di dashboard hanya tersimpan di tab browser ini dan hilang saat tab ditutup.</span>
        <button type="button" class="section-textbtn" data-reset-demo>Atur ulang</button>
    </p>
    <?php endif; ?>

    <div id="dashboard-sections" class="space-y-5">
    <?php foreach ($groups as $index => $group):
        $sectionId = 'section-' . e((string) ($group['id'] ?? $group['slug']));
        $isForms = $group['kind'] === 'forms';
        // First paint uses the default filter "Jadwal Hari Ini" (dashboard.js redraws with the remembered one).
        $visible = array_values(array_filter($group['cards'], static fn (array $card): bool => !$card['hidden']));
        $isDue = static fn (array $card): bool => $card['status'] !== null && $card['status']['due'];
        $dueCount = count(array_filter($visible, $isDue));
        $doneCount = count(array_filter($visible, static fn (array $card): bool => $isDue($card) && $card['done_today']));
        if ($isForms) {
            $group['cards'] = array_values(array_filter($group['cards'], static fn (array $card): bool => $card['hidden'] || $isDue($card)));
        }
        $filteredEmpty = $isForms && $visible !== [] && array_filter($group['cards'], static fn (array $card): bool => !$card['hidden']) === [];
        $hiddenCount = count(array_filter($group['cards'], static fn (array $card): bool => $card['hidden']));
        $spacing = $index === 0 ? 'pt-2' : ($index === count($groups) - 1 ? 'pb-8 pt-4' : 'pt-4');
    ?>
    <section aria-labelledby="<?= $sectionId ?>" class="portal-category space-y-3 <?= $spacing ?>" data-section>
        <div class="section-head">
            <h2 id="<?= $sectionId ?>" class="flex items-center gap-2 text-[11.7px] font-bold uppercase tracking-wider text-slate-500">
                <?= card_icon($group['icon'], 'size-3.5 text-slate-400') ?><?= e($group['title']) ?>
            </h2>
            <?php if ($editable && ($hiddenCount > 0 || ($isForms && $showDialogs))): ?>
            <div class="section-actions">
                <?php if ($hiddenCount > 0): ?>
                <button type="button" class="section-textbtn" data-toggle-hidden aria-pressed="false" data-label-show="<?= e('Tampilkan ' . $hiddenCount . ' tersembunyi') ?>" data-label-hide="<?= e('Sembunyikan ' . $hiddenCount . ' tersembunyi') ?>">
                    <?= card_icon('eye', 'size-3.5') ?><span data-toggle-label><?= e('Tampilkan ' . $hiddenCount . ' tersembunyi') ?></span>
                </button>
                <?php endif; ?>
                <?php if ($isForms && $showDialogs): ?>
                <button type="button" class="dash-btn dash-btn--primary dash-btn--sm" data-open-add aria-haspopup="dialog"><?= card_icon('plus', 'size-4') ?>Tambah</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($isForms): ?>
        <div class="section-filter">
            <div class="section-seg" role="group" aria-label="Filter form">
                <button type="button" data-filter="today" aria-pressed="true">Jadwal Hari Ini</button>
                <button type="button" data-filter="all" aria-pressed="false">Semua Form</button>
            </div>
            <span class="section-count"><i aria-hidden="true"></i><b><?= e($dueCount . ' dari ' . count($visible) . ' form dijadwalkan hari ini') ?></b><?= $dueCount > 0 ? e(' · ' . $doneCount . ' selesai') : '' ?></span>
        </div>
        <?php endif; ?>
        <?php if ($filteredEmpty): ?>
        <div class="section-empty">
            <?= card_icon('calendar-check', 'size-6 text-slate-400') ?>
            <b>Tidak ada form yang dijadwalkan hari ini</b>
            <span>Form lain tetap bisa dibuka dari tampilan Semua Form.</span>
            <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-filter="all"><?= card_icon('list-checks', 'size-4') ?>Lihat Semua Form</button>
        </div>
        <?php if ($group['cards'] !== []): ?>
        <div class="card-grid--forms grid grid-cols-1 gap-3.5">
            <?php foreach ($group['cards'] as $card) { require BASE_PATH . '/app/Views/components/portal-card.php'; } ?>
        </div>
        <?php endif; ?>
        <?php elseif ($group['cards'] === []): ?>
        <div class="section-empty">
            <?= card_icon($isForms ? 'clipboard-list' : 'link', 'size-6 text-slate-400') ?>
            <b><?= $isForms ? 'Belum ada form di bagian ini' : 'Belum ada tautan di bagian ini' ?></b>
            <span><?= $isForms ? 'Buat form monitoring pertama untuk mulai mencatat.' : 'Tambahkan tautan lewat tombol + Tambah, tab Kategori & Tautan.' ?></span>
            <?php if ($editable && $group['custom']): ?>
            <button type="button" class="section-textbtn section-textbtn--danger" data-delete-section="<?= e((string) $group['id']) ?>"><?= card_icon('trash-2', 'size-3.5') ?>Hapus kategori ini</button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php if ($hiddenCount === count($group['cards'])): ?>
        <p class="section-empty section-empty--compact" data-all-hidden>Semua kartu di bagian ini disembunyikan.</p>
        <?php endif; ?>
        <div class="<?= $isForms ? 'card-grid--forms grid grid-cols-1 gap-3.5' : 'grid grid-cols-2 gap-3.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7' ?>">
            <?php foreach ($group['cards'] as $card) { require BASE_PATH . '/app/Views/components/portal-card.php'; } ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
    </div>
    <noscript><p class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk menggunakan menu portal.</p></noscript>
</main>
<?php if ($editable): ?>
<div class="card-menu" id="card-menu" role="menu" data-editor-url="<?= e(url('/form-editor')) ?>" data-fill-url="<?= e(url('/form-dinamis')) ?>" data-responses-url="<?= e(url('/form-dinamis/respons')) ?>" hidden></div>
<template id="dash-icons"><?php foreach ($iconNames as $name): ?><span data-icon="<?= e($name) ?>"><?= card_icon($name) ?></span><?php endforeach; ?></template>
<script type="application/json" id="dashboard-state"><?= json_encode($clientState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?php endif; ?>
<?php if ($showDialogs): ?>
<?php require BASE_PATH . '/app/Views/components/dashboard-dialogs.php'; ?>
<?php endif; ?>
<?php if ($editable): ?>
<?= partial('toast') ?>
<?php endif; ?>
