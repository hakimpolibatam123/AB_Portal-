<?php
/**
 * Dialogs and the card menu of the editable dashboard (public/assets/js/dashboard.js).
 * Uses $linkSections (sections that hold link cards) and $cardStyle (config/dashboard-cards.php).
 */
$iconOption = static fn (string $name, string $keywords, string $field, bool $checked = false): string =>
    '<label class="icon-option" title="' . e($name) . '" data-keywords="' . e($name . ' ' . $keywords) . '">'
    . '<input type="radio" class="sr-only" name="' . e($field) . '" value="' . e($name) . '"' . ($checked ? ' checked' : '') . '>'
    . card_icon($name, 'size-5') . '<span class="sr-only">Icon ' . e($name) . '</span></label>';
?>
<dialog id="link-dialog" class="dash-dialog dash-dialog--wide" aria-labelledby="link-dialog-title">
    <form id="link-form" method="post" action="<?= e(url('/dashboard/links')) ?>" enctype="multipart/form-data" novalidate
        data-create-action="<?= e(url('/dashboard/links')) ?>" data-update-action="<?= e(url('/dashboard/links/')) ?>" data-section-action="<?= e(url('/dashboard/sections')) ?>">
        <?= csrf_field() ?>
        <header class="dash-dialog__head">
            <h2 id="link-dialog-title" class="dash-dialog__title" data-dialog-title>Tambah</h2>
            <button type="button" class="dash-dialog__x" data-dialog-close aria-label="Tutup"><?= card_icon('x', 'size-5') ?></button>
        </header>
        <div class="dash-dialog__body">
            <p class="dash-alert" data-form-error role="alert" hidden></p>
            <div class="dash-grid">
                <div class="dash-fields">
                    <div class="dash-field">
                        <label for="link-section">Kategori</label>
                        <select id="link-section" name="section_id" class="dash-input" aria-describedby="link-section-error">
                            <?php foreach ($linkSections as $section): ?>
                            <option value="<?= e((string) $section['id']) ?>"><?= e($section['title']) ?></option>
                            <?php endforeach; ?>
                            <option value="__new">+ Buat kategori baru</option>
                        </select>
                        <p class="dash-error" id="link-section-error" data-error-for="section_id" hidden></p>
                    </div>

                    <div class="dash-newcat" data-newcat hidden>
                        <p class="dash-newcat__head"><?= card_icon('folder-plus', 'size-4') ?><b>Kategori baru</b></p>
                        <div class="dash-field">
                            <label for="category-title">Nama Kategori <span class="dash-req" aria-hidden="true">*</span></label>
                            <input id="category-title" name="category_title" class="dash-input" maxlength="32" autocomplete="off" placeholder="Mis. JARINGAN &amp; SERVER" aria-describedby="category-title-error">
                            <p class="dash-error" id="category-title-error" data-error-for="category_title" hidden></p>
                        </div>
                        <fieldset class="dash-field">
                            <legend>Icon kategori (opsional)</legend>
                            <div class="icon-grid icon-grid--sm">
                                <?php foreach ($cardStyle['category_icons'] as $i => $name): ?>
                                <?= $iconOption($name, $cardStyle['icons'][$name] ?? '', 'category_icon', $i === 0) ?>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                        <div class="dash-newcat__actions">
                            <button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-newcat-cancel>Batal</button>
                            <button type="button" class="dash-btn dash-btn--outline dash-btn--sm" data-newcat-save><?= card_icon('save', 'size-4') ?>Simpan kategori</button>
                        </div>
                    </div>

                    <div class="dash-field">
                        <label for="link-title">Judul <span class="dash-req" aria-hidden="true">*</span></label>
                        <input id="link-title" name="title" class="dash-input" maxlength="40" required autocomplete="off" aria-describedby="link-title-error">
                        <p class="dash-error" id="link-title-error" data-error-for="title" hidden></p>
                    </div>
                    <div class="dash-field">
                        <div class="dash-label-row"><label for="link-description">Deskripsi Singkat</label><span class="dash-count" data-count-for="link-description" aria-hidden="true">0/60</span></div>
                        <input id="link-description" name="description" class="dash-input" maxlength="60" autocomplete="off" placeholder="Tampil di bawah judul kartu" aria-describedby="link-description-error">
                        <p class="dash-error" id="link-description-error" data-error-for="description" hidden></p>
                    </div>
                    <div class="dash-field">
                        <label for="link-url">URL Website <span class="dash-req" aria-hidden="true">*</span></label>
                        <div class="dash-inline">
                            <input id="link-url" name="url" class="dash-input" type="url" inputmode="url" maxlength="500" required placeholder="https://" autocomplete="off" aria-describedby="link-url-error">
                            <a class="dash-btn dash-btn--ghost dash-btn--sm" data-open-url target="_blank" rel="noopener noreferrer" aria-disabled="true"><?= card_icon('external-link', 'size-4') ?>Buka tautan</a>
                        </div>
                        <p class="dash-error" id="link-url-error" data-error-for="url" hidden></p>
                    </div>
                    <fieldset class="dash-field">
                        <legend>Icon</legend>
                        <div class="dash-seg">
                            <label><input type="radio" class="sr-only" name="icon_source" value="list" checked><span>Pilih dari daftar</span></label>
                            <label><input type="radio" class="sr-only" name="icon_source" value="image"><span>Unggah gambar</span></label>
                        </div>
                        <div data-icon-panel="list">
                            <div class="dash-search"><?= card_icon('search', 'size-4') ?><input class="dash-input" type="search" data-icon-search placeholder="Cari icon (mis. server, suhu)" aria-label="Cari icon"></div>
                            <div class="icon-grid" data-icon-grid aria-describedby="link-icon-error">
                                <?php foreach ($cardStyle['icons'] as $name => $keywords): ?>
                                <?= $iconOption($name, $keywords, 'icon') ?>
                                <?php endforeach; ?>
                            </div>
                            <p class="dash-help" data-icon-empty hidden>Icon tidak ditemukan.</p>
                            <p class="dash-error" id="link-icon-error" data-error-for="icon" hidden></p>
                        </div>
                        <div data-icon-panel="image" hidden>
                            <input id="link-icon-file" class="dash-file" type="file" name="icon_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" aria-describedby="link-icon-file-help link-icon-file-error">
                            <p class="dash-help" id="link-icon-file-help">Gambar persegi PNG, JPG, atau WebP, maksimal 1 MB. Pratinjau tampil di kartu.</p>
                            <p class="dash-help" data-current-image hidden>Gambar saat ini tetap dipakai. Pilih file baru untuk menggantinya.</p>
                            <p class="dash-error" id="link-icon-file-error" data-error-for="icon_file" hidden></p>
                        </div>
                    </fieldset>
                </div>
                <aside class="dash-preview" aria-label="Pratinjau kartu tautan">
                    <span class="dash-preview__label">Pratinjau kartu</span>
                    <div class="dash-preview__card portal-card" aria-hidden="true">
                        <span class="card-icon tone-teal" data-preview-icon><?= card_icon('link') ?></span>
                        <span class="portal-card-title" data-preview-title>Judul tautan</span>
                        <span class="portal-card-description"><span data-preview-description></span><span class="card-note" data-preview-note>Alamat belum diatur</span></span>
                    </div>
                </aside>
            </div>
        </div>
        <footer class="dash-dialog__foot">
            <button type="button" class="dash-btn dash-btn--ghost" data-dialog-close>Batal</button>
            <button type="submit" class="dash-btn dash-btn--primary" data-submit><?= card_icon('save', 'size-4') ?>Simpan</button>
        </footer>
    </form>
</dialog>

<dialog id="move-dialog" class="dash-dialog" aria-labelledby="move-dialog-title">
    <form method="post" data-action="<?= e(url('/dashboard/cards/{id}/move')) ?>" class="dash-dialog__pad">
        <?= csrf_field() ?>
        <h2 id="move-dialog-title" class="dash-dialog__title">Pindah Kategori</h2>
        <p class="dash-text">Pilih bagian dashboard untuk kartu <b data-card-name></b>.</p>
        <fieldset class="move-list">
            <legend class="sr-only">Kategori tujuan</legend>
            <?php foreach ($linkSections as $section): ?>
            <label class="move-option"><input type="radio" name="section_id" value="<?= e((string) $section['id']) ?>"><?= card_icon($section['icon'], 'size-4') ?><span><?= e($section['title']) ?></span></label>
            <?php endforeach; ?>
        </fieldset>
        <div class="dash-dialog__actions">
            <button type="button" class="dash-btn dash-btn--ghost" data-dialog-close>Batal</button>
            <button type="submit" class="dash-btn dash-btn--primary"><?= card_icon('folder-input', 'size-4') ?>Pindahkan</button>
        </div>
    </form>
</dialog>

<dialog id="delete-dialog" class="dash-dialog" aria-labelledby="delete-dialog-title" aria-describedby="delete-dialog-text">
    <form method="post" data-action="<?= e(url('/dashboard/cards/{id}/delete')) ?>" class="dash-dialog__pad">
        <?= csrf_field() ?>
        <span class="dash-danger-icon"><?= card_icon('trash-2', 'size-5') ?></span>
        <h2 id="delete-dialog-title" class="dash-dialog__title">Hapus tautan ini?</h2>
        <p id="delete-dialog-text" class="dash-text">Kartu <b data-card-name></b> akan dihapus dari dashboard. Website tujuannya tidak terpengaruh.</p>
        <div class="dash-dialog__actions">
            <button type="button" class="dash-btn dash-btn--ghost" data-dialog-close>Batal</button>
            <button type="submit" class="dash-btn dash-btn--danger"><?= card_icon('trash-2', 'size-4') ?>Hapus</button>
        </div>
    </form>
</dialog>

<div class="card-menu" id="card-menu" role="menu" hidden></div>
<form id="card-action" method="post" data-action="<?= e(url('/dashboard/cards/{id}/')) ?>" hidden><?= csrf_field() ?></form>
<template id="card-menu-icons"><?php foreach (['chart-column' => 'rekap', 'pencil' => 'edit', 'folder-input' => 'move', 'eye-off' => 'hide', 'eye' => 'show', 'trash-2' => 'delete'] as $name => $action): ?><span data-icon="<?= e($action) ?>"><?= card_icon($name, 'size-4') ?></span><?php endforeach; ?></template>
