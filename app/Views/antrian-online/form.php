<?php
/** @var array{name: string, initials: string, subtitle: string} $profile */
/** @var array{crons: list<array{key: string, title: string, description: string, good: string, bad: string}>} $monitoring */
/** @var string $recordedAt */
?>
<main id="main-content" class="gf-page queue-page">
    <form id="queue-form" method="post" class="gf-form" data-inspector="<?= e($profile['name']) ?>">
        <input type="hidden" name="recorded_at" value="<?= e($recordedAt) ?>">
        <?= partial('gf-header', [
            'titleId' => 'queue-title',
            'eyebrow' => 'RS AWAL BROS GROUP • IT BOTANIA',
            'title' => 'Form Monitoring Antrian Online BPJS',
            'description' => 'Pencatatan harian dan verifikasi sinkronisasi pengiriman data cron antrian online serta cron ketersediaan bed per jam MJKN Mobile JKN BPJS Kesehatan.',
            'meta' => [
                ['label' => 'Periode Laporan', 'value' => '<span id="queue-period">Inspeksi Harian</span>'],
                ['label' => 'Akun Verifikator IT', 'value' => '<span>' . e($profile['name']) . '</span><span class="gf-meta-sub">' . e($profile['subtitle']) . '</span>'],
                ['label' => 'Waktu & Tanggal', 'value' => '<time id="queue-recorded-time" datetime="' . e($recordedAt) . '" class="gf-mono">' . e(date('d/m/Y H:i')) . ' WIB</time>'],
            ],
            'rekap' => url('/reports/antrian-online'),
        ]) ?>

        <?= partial('gf-section', [
            'number' => 1, 'titleId' => 'queue-form-title',
            'title' => 'Data Verifikasi Antrian Online BPJS',
            'description' => 'Formulir Log Operasional Bridging SIMRS & BPJS • Monitoring sinkronisasi harian',
            'aside' => '<span class="gf-pill gf-mono">CRON AUDIT</span>',
        ]) ?>

        <p id="queue-feedback" role="status" aria-live="polite" class="gf-notice hidden"></p>

        <?php foreach ($monitoring['crons'] as $index => $cron): ?>
        <section class="gf-card" aria-labelledby="<?= e($cron['key']) ?>-title">
            <div class="gf-q-head"><h3 id="<?= e($cron['key']) ?>-title" class="gf-q-title"><span class="gf-qnum"><?= e($index + 1) ?></span><?= e($cron['title']) ?></h3><span class="gf-pill gf-pill--red gf-mono">WAJIB DIISI</span></div>
            <p id="<?= e($cron['key']) ?>-help" class="gf-help"><?= e($cron['description']) ?></p>
            <fieldset class="gf-body gf-options" aria-describedby="<?= e($cron['key']) ?>-help"><legend class="sr-only"><?= e($cron['title']) ?>, wajib diisi</legend>
                <?php foreach (['TERKIRIM' => true, 'TIDAK_TERKIRIM' => false] as $value => $good): ?>
                <?php $optionId = $cron['key'] . ($good ? '-good' : '-bad'); ?>
                <label for="<?= e($optionId) ?>" class="gf-option"><input id="<?= e($optionId) ?>" type="radio" name="<?= e($cron['key']) ?>" value="<?= e($value) ?>" required <?= $good ? 'checked' : '' ?>><span class="gf-option-text"><span class="gf-option-label"><?= $good ? 'TERKIRIM' : 'TIDAK TERKIRIM' ?><span class="gf-pill gf-mono<?= $good ? '' : ' gf-pill--red' ?>"><?= $good ? 'NORMAL' : 'CRITICAL' ?></span></span><span class="gf-option-note"><?= e($cron[$good ? 'good' : 'bad']) ?></span></span></label>
                <?php endforeach; ?>
            </fieldset>
        </section>
        <?php endforeach; ?>

        <div class="gf-card">
            <label for="queue-notes" class="gf-q-title">CATATAN KENDALA / TINDAK LANJUT IT <span id="queue-notes-required" hidden class="gf-req" aria-hidden="true">*</span></label>
            <p id="queue-notes-help" class="gf-help">Isi catatan bila ada cron yang tidak terkirim.</p>
            <div class="gf-body"><textarea id="queue-notes" name="notes" rows="4" maxlength="3000" aria-describedby="queue-notes-help" class="gf-input" placeholder="Tuliskan kendala cron dan tindak lanjut yang dilakukan..."></textarea></div>
        </div>

        <div class="gf-card">
            <div class="gf-confirm"><input id="queue-confirm" name="confirmed" type="checkbox" required><label for="queue-confirm" class="gf-confirm-label">Saya menyatakan bahwa pemeriksaan sinkronisasi Antrian Online BPJS ini telah diverifikasi sesuai kondisi log server riil RS Awal Bros Botania.</label></div>
        </div>

        <div class="gf-actions">
            <a href="/" class="gf-btn gf-btn--outline"><?= icon('arrow-left', 'size-4') ?>Kembali ke Portal Terpadu</a>
            <div class="gf-actions-end"><button type="reset" class="gf-btn gf-btn--text"><?= icon('reset', 'size-4') ?>Clear form</button><button type="submit" class="gf-btn gf-btn--primary" aria-haspopup="dialog"><?= icon('send', 'size-4') ?>Kirim Laporan Antrian</button></div>
        </div>
        <noscript><p class="gf-notice">Aktifkan JavaScript untuk validasi dan ringkasan laporan.</p></noscript>
    </form>
</main>
<script src="<?= e(asset_v('js/gform.js')) ?>" defer></script>

<dialog id="queue-review" class="fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-xl" aria-labelledby="queue-review-title" aria-describedby="queue-review-help"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-semibold text-emerald-700">Monitoring Antrian Online BPJS</p><h2 id="queue-review-title" class="mt-1 text-lg font-bold text-slate-900">Ringkasan Laporan Antrian</h2></div><button type="button" data-queue-close class="button-ghost module-action-small rounded-lg p-2" aria-label="Tutup ringkasan"><?= icon('close', 'size-5') ?></button></div><dl id="queue-review-fields" class="my-5 space-y-3 text-sm"></dl><p id="queue-review-help" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Pengiriman laporan ke server belum tersedia. Data belum dikirim.</p><div class="mt-5 flex flex-wrap justify-end gap-3"><button type="button" data-queue-close class="button-neutral module-action inspection-button border">Kembali ke Form</button></div></dialog>
