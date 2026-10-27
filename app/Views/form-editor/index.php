<?php
/**
 * "Edit Isi Form" page: tabs Pertanyaan and Jadwal. Frontend only; public/assets/js/form-editor.js fills
 * the form from the browser state (dashboard-store.js) and saves it there. The layout renders the
 * "Kembali ke Dashboard" button above this page ($backToDashboard).
 */
?>
<main id="main-content" class="fe-page page-shell flex-1 pb-12" data-form-editor data-form-id="<?= e($formId) ?>">
    <div class="fe-missing" data-fe-missing hidden>
        <?= card_icon('circle-alert', 'size-8 text-slate-400') ?>
        <h1>Form tidak ditemukan</h1>
        <p>Form ini tidak ada di dashboard (mungkin sudah dihapus, atau data contoh sudah diatur ulang).</p>
        <a class="dash-btn dash-btn--primary" href="<?= e(url('/')) ?>">Kembali ke Dashboard</a>
    </div>

    <div class="fe-shell" data-fe-root hidden>
        <header class="fe-sub">
            <span class="card-icon tone-teal fe-sub__icon" data-fe-tile aria-hidden="true"></span>
            <div class="fe-sub__text">
                <p class="fe-sub__eyebrow">Edit Isi Form</p>
                <h1 class="fe-sub__title" data-fe-heading></h1>
            </div>
            <div class="fe-sub__actions">
                <button type="button" class="dash-btn dash-btn--ghost" data-fe-cancel>Batal</button>
                <button type="button" class="dash-btn dash-btn--primary" data-fe-save><?= card_icon('save', 'size-4') ?>Simpan Form</button>
            </div>
            <p class="fe-note"><?= card_icon('info', 'size-4 shrink-0') ?><span>Perubahan pertanyaan tidak menghapus laporan yang sudah tersimpan.</span></p>
        </header>
        <div class="fe-discard" data-fe-discard role="alert" hidden>
            <span>Perubahan belum disimpan.</span>
            <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-fe-keep>Lanjut mengedit</button>
            <button type="button" class="dash-btn dash-btn--danger dash-btn--sm" data-fe-discard-go>Buang perubahan</button>
        </div>

        <div class="dash-tabs fe-tabs" role="tablist" aria-label="Bagian editor">
            <button type="button" role="tab" id="fe-tab-questions" aria-controls="fe-panel-questions" aria-selected="true" data-fe-tab="questions"><?= card_icon('list-checks', 'size-4') ?>Pertanyaan</button>
            <button type="button" role="tab" id="fe-tab-schedule" aria-controls="fe-panel-schedule" aria-selected="false" tabindex="-1" data-fe-tab="schedule"><?= card_icon('calendar-days', 'size-4') ?>Jadwal</button>
        </div>
        <p class="fe-status" data-fe-status aria-live="polite"></p>

        <div id="fe-panel-questions" role="tabpanel" aria-labelledby="fe-tab-questions">
            <div class="fe-layout">
                <div class="fe-list" data-fe-list></div>
                <nav class="fe-tools" aria-label="Tambah item">
                    <button type="button" data-tool="question"><?= card_icon('circle-plus', 'size-5') ?><span>Tambah Pertanyaan</span></button>
                    <button type="button" data-tool="title"><?= card_icon('type', 'size-5') ?><span>Tambah Judul</span></button>
                    <button type="button" data-tool="image"><?= card_icon('image', 'size-5') ?><span>Tambah Gambar</span></button>
                    <button type="button" data-tool="section"><?= card_icon('rows-3', 'size-5') ?><span>Tambah Bagian</span></button>
                </nav>
            </div>
        </div>
        <div id="fe-panel-schedule" class="fe-schedule" role="tabpanel" aria-labelledby="fe-tab-schedule" hidden data-fe-schedule></div>
    </div>
</main>
<template id="dash-icons"><?php foreach ($iconNames as $name): ?><span data-icon="<?= e($name) ?>"><?= card_icon($name) ?></span><?php endforeach; ?></template>
<script type="application/json" id="dashboard-state"><?= json_encode($clientState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?= partial('toast') ?>
