<?php
/** @var array{name: string, initials: string, subtitle: string} $profile */
/** @var array{reasons: array<string, string>} $verification */
/** @var string $loginDate */
/** @var string $loginTime */
?>
<main id="main-content" class="gf-page">
    <form id="compliance-form" method="post" class="gf-form" data-inspector="<?= e($profile['name']) ?>">
        <?= partial('gf-header', [
            'titleId' => 'compliance-title',
            'eyebrow' => 'RS AWAL BROS GROUP • IT BOTANIA',
            'title' => 'Form Kesesuaian User (HIS)',
            'description' => 'Verifikasi sampel kesesuaian hak akses data sesuai kondisi lapangan di RS Awal Bros Botania.',
            'meta' => [
                ['label' => 'Periode Verifikasi', 'value' => '<time id="compliance-period" datetime="' . e($loginDate) . '">' . e(date_wib()) . '</time>'],
            ],
            'required' => '<span class="gf-required-note">* Wajib Diisi</span>',
            'rekap' => url('/reports/kesesuaian-user'),
        ]) ?>

        <?= partial('gf-section', [
            'number' => 1, 'titleId' => 'compliance-form-title',
            'title' => 'Masukkan username yang dilakukan sampel penyesuaian login',
            'description' => 'Leveling User Account sesuai regulasi SPO RS Awal Bros Botania.',
        ]) ?>

        <p id="compliance-feedback" role="status" aria-live="polite" class="gf-notice hidden"></p>

        <div class="gf-card">
            <div class="gf-q-head"><label for="compliance-username" class="gf-q-title">1. Masukkan username HIS di sini <span class="gf-req" aria-hidden="true">*</span></label><span class="gf-pill">Database ID / Akun SIMRS</span></div>
            <p id="compliance-username-help" class="gf-help">Ketik username akun SIMRS / HIS yang sedang diverifikasi sampelnya saat login sesi bertugas.</p>
            <div class="gf-body"><input id="compliance-username" name="username" type="text" required maxlength="100" autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="compliance-username-help" placeholder="Contoh: dr.anita / nur.siti / adm.budi" class="gf-input"></div>
        </div>

        <fieldset class="gf-card" aria-describedby="compliance-status-help">
            <legend class="sr-only">Kesesuaian username dengan regulasi, wajib diisi</legend>
            <div class="gf-q-head"><h3 class="gf-q-title">2. Username sesuai regulasi? <span class="gf-req" aria-hidden="true">*</span></h3><span class="gf-pill">Validasi Otorisasi</span></div>
            <p id="compliance-status-help" class="gf-help">Apakah data kredensial, peran modul, dan hak akses user sesuai dengan wewenang unit kerja aktif?</p>
            <div class="gf-body gf-options">
                <label for="compliance-good" class="gf-option"><input id="compliance-good" type="radio" name="status" value="sesuai" required checked><span class="gf-option-text"><span class="gf-option-label">Sesuai Regulasi<span class="gf-pill">Sesuai Regulasi</span></span><span class="gf-option-note">Hak akses modul tepat sesuai unit &amp; jabatan dinas.</span></span></label>
                <label for="compliance-bad" class="gf-option"><input id="compliance-bad" type="radio" name="status" value="tidak_sesuai" required><span class="gf-option-text"><span class="gf-option-label">Tidak Sesuai Regulasi<span class="gf-pill gf-pill--red">Tidak Sesuai Regulasi</span></span><span class="gf-option-note">Ditemukan anomali modul, mutasi belum diperbarui, dll.</span></span></label>
            </div>
            <div id="compliance-reason-panel" hidden class="gf-subpanel">
                <label for="compliance-reason" class="gf-subpanel-label"><?= icon('warning', 'size-4 text-rose-600') ?>Alasan / Catatan Ketidaksesuaian <span aria-hidden="true">*</span></label>
                <select id="compliance-reason" name="reason" class="gf-select" disabled><?php foreach ($verification['reasons'] as $value => $label): ?><option value="<?= e($value) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
                <label for="compliance-notes" class="gf-subpanel-label">Catatan spesifik tindak lanjut <span id="compliance-notes-required" hidden aria-hidden="true">*</span></label>
                <textarea id="compliance-notes" name="notes" rows="2" maxlength="1000" class="gf-input" placeholder="Catatan spesifik tindak lanjut..." disabled></textarea>
            </div>
        </fieldset>

        <div class="gf-card">
            <div class="gf-q-head"><h3 class="gf-q-title">3. Waktu login <span class="gf-req" aria-hidden="true">*</span></h3><button type="button" data-compliance-now class="gf-btn gf-btn--text"><?= icon('clock', 'size-3') ?>Set Jam Sekarang</button></div>
            <p class="gf-help">Waktu saat user melakukan autentikasi login pada sistem HIS (format 24 jam, WIB).</p>
            <div class="gf-body gf-row">
                <div><label for="compliance-date" class="sr-only">Tanggal login, wajib diisi</label><input id="compliance-date" name="login_date" type="date" required value="<?= e($loginDate) ?>" class="gf-input"></div>
                <div><label for="compliance-time" class="sr-only">Jam login WIB, wajib diisi</label><input id="compliance-time" name="login_time" type="time" required step="60" value="<?= e($loginTime) ?>" class="gf-input"></div>
            </div>
        </div>

        <div class="gf-card">
            <div class="gf-confirm"><input id="compliance-confirm" name="confirmed" type="checkbox" required><label for="compliance-confirm" class="gf-confirm-label">Saya mengonfirmasi bahwa pengecekan sampel kesesuaian hak akses ini telah dilakukan secara valid sesuai regulasi melalui pengecekan terminal log / active session HIS.</label></div>
        </div>

        <div class="gf-actions">
            <a href="/" class="gf-btn gf-btn--outline"><?= icon('arrow-left', 'size-4') ?>Kembali ke Portal Terpadu</a>
            <div class="gf-actions-end"><button type="submit" class="gf-btn gf-btn--primary" aria-haspopup="dialog"><?= icon('send', 'size-4') ?>Kirim Verifikasi</button></div>
        </div>
        <noscript><p class="gf-notice">Aktifkan JavaScript untuk menggunakan catatan ketidaksesuaian dan validasi form.</p></noscript>
    </form>
</main>
<script src="<?= e(asset_v('js/gform.js')) ?>" defer></script>

<dialog id="compliance-review" class="fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-xl" aria-labelledby="compliance-review-title" aria-describedby="compliance-review-help">
    <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold text-emerald-700">Kesesuaian User HIS</p><h2 id="compliance-review-title" class="mt-1 text-lg font-bold text-slate-900">Ringkasan Verifikasi</h2></div><button type="button" data-compliance-close class="button-ghost module-action-small rounded-lg p-2" aria-label="Tutup ringkasan"><?= icon('close', 'size-5') ?></button></div>
    <dl id="compliance-review-fields" class="my-5 space-y-3 text-sm"></dl>
    <p id="compliance-review-help" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Pengiriman verifikasi ke server belum tersedia. Data belum dikirim.</p>
    <div class="mt-5 flex flex-wrap justify-end gap-3"><button type="button" data-compliance-close class="button-neutral module-action inspection-button border">Kembali ke Form</button></div>
</dialog>
