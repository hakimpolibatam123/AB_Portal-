<?php
/** @var array{name: string, initials: string, subtitle: string} $profile */
/** @var string $recordedAt */
/**
 * @var array{
 *     unit: string,
 *     location: string,
 *     period: string,
 *     racks: list<array{value: string, description: string, icon: string, detail: string}>,
 *     conditions: list<array<string, int|string>>,
 *     quick_notes: list<array{label: string, text: string}>
 * } $inspection
 */
?>
<main id="main-content" class="flex-1">
    <?php require BASE_PATH . '/app/Views/components/hardware-server-hero.php'; ?>

    <div class="page-shell relative mt-6 pb-16">
        <form id="hardware-monitoring-form" method="post" class="space-y-6" data-inspector="<?= e($profile['name']) ?>">
            <input type="hidden" name="recorded_at" value="<?= e($recordedAt) ?>">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center gap-4">
                    <dl class="grid w-full flex-1 grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="inspection-metadata">
                            <span class="inspection-metadata-icon border-teal-100 bg-teal-50 text-teal-700"><?= icon('clipboard', 'size-4') ?></span>
                            <div class="min-w-0"><dt>Petugas Pemeriksa:</dt><dd><?= e($profile['name']) ?></dd></div>
                        </div>
                        <div class="inspection-metadata">
                            <span class="inspection-metadata-icon border-slate-200 bg-slate-100 text-rose-500"><?= icon('location', 'size-4') ?></span>
                            <div class="min-w-0"><dt>Lokasi Ruang Server:</dt><dd><?= e($inspection['location']) ?></dd></div>
                        </div>
                        <div class="inspection-metadata">
                            <span class="inspection-metadata-icon border-slate-200 bg-slate-100 text-teal-600"><?= icon('clock', 'size-4') ?></span>
                            <div class="min-w-0"><dt>Waktu Pencatatan:</dt><dd><time id="inspection-timestamp" datetime="<?= e($recordedAt) ?>"><?= e(date('d/m/Y, H:i')) ?> WIB</time></dd></div>
                        </div>
                    </dl>
                    <p class="inline-flex min-h-10 items-center gap-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800"><?= icon('warning', 'size-4 shrink-0 text-amber-500') ?>Wajib Diisi Lengkap</p>
                </div>
            </div>
            <p id="inspection-feedback" role="status" aria-live="polite" class="hidden rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800"></p>
            <noscript><p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Aktifkan JavaScript untuk menggunakan catatan cepat dan pemeriksaan form.</p></noscript>

            <fieldset class="inspection-panel" aria-describedby="rack-help">
                <legend class="sr-only">Pilih rak server, wajib diisi</legend>
                <div class="inspection-section-heading">
                    <span class="inspection-step">1</span>
                    <div class="min-w-0 flex-1"><h2 class="text-base font-bold tracking-tight text-slate-900">PILIH RAK SERVER <span class="ml-1 text-rose-500" aria-hidden="true">*</span></h2><p id="rack-help" class="mt-0.5 text-xs text-slate-500">Pilih rack enclosure server yang sedang diinspeksi</p></div>
                    <span class="inspection-section-tag">Single Selection</span>
                </div>
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <?php foreach ($inspection['racks'] as $index => $rack): ?>
                    <div class="inspection-choice">
                        <input type="radio" class="inspection-radio sr-only" id="rack-<?= e($index) ?>" name="rak_server" value="<?= e($rack['value']) ?>" required <?= $index === 0 ? 'checked' : '' ?>>
                        <label for="rack-<?= e($index) ?>" class="flex h-full cursor-pointer flex-col justify-between p-5">
                            <span class="flex items-center gap-3.5">
                                <span class="inspection-radio-indicator" aria-hidden="true"></span>
                                <span class="min-w-0 flex-1"><span class="block text-sm font-bold text-slate-800"><?= e($rack['value']) ?></span><span class="mt-0.5 block text-xs leading-relaxed text-slate-500"><?= e($rack['description']) ?></span></span>
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-teal-600"><?= icon($rack['icon'], 'size-4') ?></span>
                            </span>
                            <span class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-200/60 pt-3 text-xs font-medium text-slate-500"><span class="inline-flex items-center gap-1.5"><?= icon('cube', 'size-3.5 text-slate-400') ?>42U Enclosure</span><span class="inline-flex items-center gap-1.5 text-emerald-700"><?= icon('check-circle', 'size-3.5') ?><?= e($rack['detail']) ?></span></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <?php foreach ($inspection['conditions'] as $condition): ?>
                <?php require BASE_PATH . '/app/Views/components/hardware-condition.php'; ?>
            <?php endforeach; ?>

            <section class="inspection-panel" aria-labelledby="hardware-notes-title">
                <div class="inspection-section-heading">
                    <span class="inspection-step">4</span>
                    <div class="min-w-0 flex-1"><h2 id="hardware-notes-title" class="text-base font-bold tracking-tight text-slate-900">KONDISI HARDWARE DAN LAIN-LAIN</h2><p id="hardware-notes-help" class="mt-0.5 text-xs text-slate-500">Catatan tambahan perangkat keras: UPS, AC Server, Kerapian Kabel (Patch Cord), PDU, Switch, dsb.</p></div>
                    <span class="inspection-section-tag">Catatan / Detail</span>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <span class="mr-1 text-xs font-semibold text-slate-500">Rekomendasi Cepat:</span>
                    <?php foreach ($inspection['quick_notes'] as $note): ?>
                    <button type="button" data-quick-note="<?= e($note['text']) ?>" class="button-soft module-action-small rounded-full border px-3 py-1.5 text-xs transition-colors">+ <?= e($note['label']) ?></button>
                    <?php endforeach; ?>
                </div>
                <label for="hardware-notes" class="sr-only">Catatan kondisi hardware dan lain-lain</label>
                <textarea id="hardware-notes" name="catatan_hardware" rows="4" maxlength="3000" aria-describedby="hardware-notes-help" class="inspection-input mt-4 min-h-28 resize-y bg-slate-50/50 p-4 text-sm" placeholder="Tuliskan catatan kondisi hardware lainnya secara terperinci (misal: kondisi UPS, temperatur ruang AC server, kebersihan rak, perangkat storage)..."></textarea>
            </section>

            <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="/" class="button-neutral module-action inspection-button border"><?= icon('arrow-left', 'size-4 shrink-0') ?><span>Kembali ke Portal Terpadu</span></a>
                    <a href="<?= e(url('/reports/hardware-server')) ?>" class="button-soft module-action inspection-button border" aria-label="Lihat rekap Form Monitoring Hardware Server IT"><?= card_icon('chart-column', 'size-4 shrink-0') ?><span>Lihat Rekap</span></a>
                </div>
                <div class="flex w-full flex-col-reverse gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <button type="reset" class="button-ghost module-action inline-flex min-h-11 w-full items-center justify-center rounded-lg px-3 py-2.5 text-xs font-semibold sm:w-auto">Reset Form</button>
                    <button type="submit" class="button-primary module-action inspection-button w-full shadow-sm sm:w-auto" aria-haspopup="dialog"><?= icon('send', 'size-4 shrink-0') ?>Kirim Laporan Monitoring</button>
                </div>
            </div>
        </form>
    </div>
</main>

<dialog id="inspection-review" class="fixed inset-0 m-auto max-h-[85dvh] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-slate-700 shadow-xl" aria-labelledby="inspection-review-title" aria-describedby="inspection-review-help">
    <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold text-teal-700">Pemeriksaan Hardware Server</p><h2 id="inspection-review-title" class="mt-1 text-lg font-bold text-slate-900">Ringkasan laporan</h2></div><button type="button" data-close-inspection-review class="button-ghost module-action-small rounded-lg p-2" aria-label="Tutup ringkasan"><?= icon('close', 'size-5') ?></button></div>
    <dl id="inspection-review-details" class="my-5 space-y-3 text-sm"></dl>
    <p id="inspection-review-help" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs leading-relaxed text-amber-900">Pengiriman laporan ke server belum tersedia. Data belum dikirim.</p>
    <div class="mt-5 flex flex-wrap justify-end gap-3"><button type="button" data-close-inspection-review class="button-neutral module-action inspection-button border">Kembali ke Form</button></div>
</dialog>
