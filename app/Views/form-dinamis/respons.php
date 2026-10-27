<?php
/**
 * Responses of a dynamic form, frontend only: public/assets/js/form-dinamis.js draws the table from the
 * answers kept in the browser (dashboard-store.js). The layout renders "Kembali ke Dashboard" above it.
 */
?>
<main id="main-content" class="fr-page page-shell flex-1 pb-12" data-form-responses data-form-id="<?= e($formId) ?>" data-fill-url="<?= e(url('/form-dinamis')) ?>">
    <div class="fe-missing" data-fill-missing hidden>
        <?= card_icon('circle-alert', 'size-8 text-slate-400') ?>
        <h1>Form tidak ditemukan</h1>
        <p>Form ini tidak ada di dashboard (mungkin sudah dihapus, atau data contoh sudah diatur ulang).</p>
        <a class="dash-btn dash-btn--primary" href="<?= e(url('/')) ?>">Kembali ke Dashboard</a>
    </div>
    <div class="fr-shell" data-fr-root hidden>
        <header class="fr-head">
            <span class="card-icon tone-teal fr-head__icon" data-fr-tile aria-hidden="true"></span>
            <div class="fr-head__text">
                <p class="fe-sub__eyebrow">Rekap Respons</p>
                <h1 class="fe-sub__title" data-fr-title></h1>
                <p class="fr-head__desc">Rekap respons yang sudah dikirim.</p>
            </div>
            <a class="dash-btn dash-btn--primary" data-fr-fill href="#"><?= card_icon('plus', 'size-4') ?>Input Form Baru</a>
        </header>
        <section class="fr-card" aria-labelledby="fr-table-title">
            <div class="fr-card__head">
                <h2 id="fr-table-title"><?= card_icon('table-2', 'size-4') ?>Respons</h2>
                <span class="fr-count" data-fr-count aria-live="polite"></span>
            </div>
            <div data-fr-body></div>
        </section>
        <p class="fr-note"><?= card_icon('info', 'size-4 shrink-0') ?><span>Data contoh: respons hanya tersimpan di tab browser ini dan hilang saat tab ditutup.</span></p>
    </div>
</main>
<template id="dash-icons"><?php foreach ($iconNames as $name): ?><span data-icon="<?= e($name) ?>"><?= card_icon($name) ?></span><?php endforeach; ?></template>
<script type="application/json" id="dashboard-state"><?= json_encode($clientState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?= partial('toast') ?>
