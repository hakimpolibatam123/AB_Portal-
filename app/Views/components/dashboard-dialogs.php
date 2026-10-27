<?php
/**
 * Dialogs of the editable dashboard (public/assets/js/dashboard.js). Frontend only: nothing is posted
 * to the server; changes stay in the browser (dummy data, sessionStorage). The category lists are
 * filled by the script from the current state. Uses $cardStyle (config/dashboard-cards.php).
 */
$iconOption = static fn (string $name, string $keywords, string $field, bool $checked = false): string =>
    '<label class="icon-option" title="' . e($name) . '" data-keywords="' . e($name . ' ' . $keywords) . '">'
    . '<input type="radio" class="sr-only" name="' . e($field) . '" value="' . e($name) . '"' . ($checked ? ' checked' : '') . '>'
    . card_icon($name, 'size-5') . '<span class="sr-only">Icon ' . e($name) . '</span></label>';
?>
<dialog id="add-dialog" class="dash-dialog dash-dialog--wide" aria-labelledby="add-dialog-title">
    <header class="dash-dialog__head">
        <h2 id="add-dialog-title" class="dash-dialog__title" data-dialog-title>Tambah</h2>
        <button type="button" class="dash-dialog__x" data-dialog-close aria-label="Tutup"><?= card_icon('x', 'size-5') ?></button>
    </header>
    <div class="dash-tabs" role="tablist" aria-label="Jenis yang ditambahkan" data-add-tabs>
        <button type="button" role="tab" id="add-tab-form" aria-controls="card-form" aria-selected="false" tabindex="-1" data-add-tab="form"><?= card_icon('clipboard-list', 'size-4') ?>Form</button>
        <button type="button" role="tab" id="add-tab-link" aria-controls="link-form" aria-selected="true" data-add-tab="link"><?= card_icon('link', 'size-4') ?>Kategori &amp; Tautan</button>
    </div>
    <div class="dash-dialog__body">
        <form id="card-form" role="tabpanel" aria-labelledby="add-tab-form" novalidate hidden>
            <div class="dash-grid">
                <div class="dash-fields">
                    <div class="dash-field">
                        <label for="card-title">Judul Form <span class="dash-req" aria-hidden="true">*</span></label>
                        <input id="card-title" name="title" class="dash-input" maxlength="60" required autocomplete="off" placeholder="Mis. Cek UPS Ruang Server" aria-describedby="card-title-error">
                        <p class="dash-error" id="card-title-error" data-error-for="title" hidden></p>
                    </div>
                    <div class="dash-field">
                        <div class="dash-label-row"><label for="card-description">Deskripsi Singkat</label><span class="dash-count" data-count-for="card-description" aria-hidden="true">0/60</span></div>
                        <input id="card-description" name="description" class="dash-input" maxlength="60" autocomplete="off" placeholder="Tampil di bawah judul kartu" aria-describedby="card-description-error">
                        <p class="dash-error" id="card-description-error" data-error-for="description" hidden></p>
                    </div>
                    <div class="dash-field" data-keterangan-field>
                        <label for="card-keterangan">Keterangan di Halaman Form</label>
                        <textarea id="card-keterangan" name="keterangan" class="dash-input" rows="3" maxlength="500" placeholder="Penjelasan yang tampil di bagian atas halaman form" aria-describedby="card-keterangan-error"></textarea>
                        <p class="dash-error" id="card-keterangan-error" data-error-for="keterangan" hidden></p>
                    </div>
                    <fieldset class="dash-field">
                        <legend>Icon</legend>
                        <div class="dash-search"><?= card_icon('search', 'size-4') ?><input class="dash-input" type="search" data-icon-search placeholder="Cari icon (mis. server, suhu)" aria-label="Cari icon form"></div>
                        <div class="icon-grid" data-icon-grid>
                            <?php foreach ($cardStyle['icons'] as $name => $keywords): ?>
                            <?= $iconOption($name, $keywords, 'icon') ?>
                            <?php endforeach; ?>
                        </div>
                        <p class="dash-help" data-icon-empty hidden>Icon tidak ditemukan.</p>
                    </fieldset>
                    <fieldset class="dash-field">
                        <legend>Warna aksen</legend>
                        <div class="color-grid">
                            <?php foreach ($cardStyle['colors'] as $tone => $label): ?>
                            <label class="color-option" title="<?= e($label) ?>"><input type="radio" class="sr-only" name="color" value="<?= e($tone) ?>"<?= $tone === 'teal' ? ' checked' : '' ?>><span class="color-option__dot tone-<?= e($tone) ?>" aria-hidden="true"></span><span class="sr-only"><?= e($label) ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                    <p class="dash-note" data-card-note><?= card_icon('info', 'size-4 shrink-0') ?><span>Pertanyaan dan jadwal diatur setelah kartu dibuat, lewat menu titik tiga pada kartu.</span></p>
                </div>
                <aside class="dash-preview" aria-label="Pratinjau kartu form">
                    <span class="dash-preview__label">Pratinjau kartu</span>
                    <div class="dash-preview__slot" data-card-preview aria-hidden="true"></div>
                </aside>
            </div>
        </form>
        <form id="link-form" role="tabpanel" aria-labelledby="add-tab-link" novalidate>
            <p class="dash-alert" data-form-error role="alert" hidden></p>
            <div class="dash-grid">
                <div class="dash-fields">
                    <div class="dash-field">
                        <label for="link-section">Kategori</label>
                        <select id="link-section" name="section_id" class="dash-input" aria-describedby="link-section-error" data-section-select></select>
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
                            <label><input type="radio" class="sr-only" name="icon_source" value="url"><span>URL gambar</span></label>
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
                        <div data-icon-panel="url" hidden>
                            <input id="link-icon-url" class="dash-input" type="url" inputmode="url" name="icon_url" maxlength="500" placeholder="https://.../logo.png" autocomplete="off" aria-label="URL gambar icon" aria-describedby="link-icon-url-help link-icon-url-error">
                            <p class="dash-help" id="link-icon-url-help">Gambar persegi PNG, SVG, atau JPG. Pratinjau tampil di kartu.</p>
                            <p class="dash-error" id="link-icon-url-error" data-error-for="icon_url" hidden></p>
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
        </form>
    </div>
    <footer class="dash-dialog__foot">
        <button type="button" class="dash-btn dash-btn--ghost" data-dialog-close>Batal</button>
        <button type="submit" form="link-form" class="dash-btn dash-btn--primary" data-submit><?= card_icon('save', 'size-4') ?>Simpan</button>
    </footer>
</dialog>

<dialog id="move-dialog" class="dash-dialog" aria-labelledby="move-dialog-title">
    <form class="dash-dialog__pad">
        <h2 id="move-dialog-title" class="dash-dialog__title">Pindah Kategori</h2>
        <p class="dash-text">Pilih bagian dashboard untuk kartu <b data-card-name></b>.</p>
        <fieldset class="move-list" data-move-list>
            <legend class="sr-only">Kategori tujuan</legend>
        </fieldset>
        <div class="dash-dialog__actions">
            <button type="button" class="dash-btn dash-btn--ghost" data-dialog-close>Batal</button>
            <button type="submit" class="dash-btn dash-btn--primary"><?= card_icon('folder-input', 'size-4') ?>Pindahkan</button>
        </div>
    </form>
</dialog>

<dialog id="delete-dialog" class="dash-dialog" aria-labelledby="delete-dialog-title" aria-describedby="delete-dialog-text">
    <form class="dash-dialog__pad">
        <span class="dash-danger-icon"><?= card_icon('trash-2', 'size-5') ?></span>
        <h2 id="delete-dialog-title" class="dash-dialog__title" data-delete-title>Hapus tautan ini?</h2>
        <p id="delete-dialog-text" class="dash-text" data-delete-text></p>
        <div class="dash-field dash-field--confirm" data-delete-confirm hidden>
            <label for="delete-confirm-input">Ketik <b data-delete-name></b> untuk melanjutkan</label>
            <input id="delete-confirm-input" class="dash-input" autocomplete="off" spellcheck="false">
        </div>
        <div class="dash-dialog__actions">
            <button type="button" class="dash-btn dash-btn--ghost" data-dialog-close>Batal</button>
            <button type="submit" class="dash-btn dash-btn--danger" data-delete-go><?= card_icon('trash-2', 'size-4') ?>Hapus</button>
        </div>
    </form>
</dialog>
