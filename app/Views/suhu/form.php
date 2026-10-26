<?php
ob_start(); ?>
<div class="flex flex-wrap items-center gap-3">
    <div class="flex items-center gap-3 bg-surface-container-lowest/10 backdrop-blur-md px-4 py-3 rounded-lg border border-surface-container-lowest/20">
        <div class="w-10 h-10 rounded-lg bg-surface-container-lowest/15 flex items-center justify-center text-tertiary-fixed"><?= icon('calendar-days', 'text-[22px]') ?></div>
        <div class="flex flex-col text-left">
            <span class="uppercase tracking-wider text-tertiary-fixed font-semibold">PERIODE LAPORAN</span>
            <span class="font-bold text-on-tertiary leading-tight">Oktober 2026 (Aktif)</span>
        </div>
    </div>
    <?= partial('button', ['label' => 'Lihat Rekap & Unduh Laporan', 'href' => url('/rekap/suhu'), 'icon' => 'file-text', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-2 px-4 py-3 rounded-lg bg-surface-container-lowest text-tertiary font-bold hover:bg-surface-container transition-all shadow-rs']) ?>
</div>
<?php $heroAside = ob_get_clean(); ?>
<main id="main-content" class="max-w-[1720px] w-full mx-auto px-4 lg:px-8 py-6 space-y-6 min-h-[calc(100vh-80px)]">
<div class="flex flex-col w-full">
    <?= partial('breadcrumb', [
        'tag' => 'div', 'class' => 'flex items-center space-x-2 text-on-surface-variant mb-4',
        'separator' => ['text' => '/', 'class' => 'text-surface-container-highest'],
        'items' => [
            ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'icon' => 'house', 'iconClass' => 'text-[16px]', 'class' => 'hover:text-tertiary transition-colors flex items-center gap-1'],
            ['label' => 'Monitoring Harian', 'class' => 'hover:text-tertiary cursor-pointer'],
            ['label' => 'Suhu Server', 'class' => 'hover:text-tertiary cursor-pointer'],
            ['label' => 'Form Input Suhu Bulanan', 'class' => 'text-tertiary font-semibold'],
        ],
    ]) ?>

    <?= partial('page-header', [
        'class' => 'relative overflow-hidden rounded-xl p-6 lg:p-8 mb-8',
        'introClass' => 'space-y-2 max-w-2xl',
        'eyebrow' => partial('badge', ['tag' => 'div', 'label' => 'RS AWAL BROS GROUP • IT BOTANIA', 'dot' => 'w-2 h-2 rounded-full bg-tertiary-fixed animate-ping', 'class' => 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-lowest/15 backdrop-blur-xs text-tertiary-fixed text-label-sm font-semibold uppercase tracking-[.04em] whitespace-nowrap']),
        'title' => 'Form Monitoring Rata - Rata Suhu Server Perbulan',
        'description' => 'Pencatatan dan verifikasi data tren suhu ruangan dan rak server data center IT RS Awal Bros Botania.',
        'descriptionClass' => 'text-on-tertiary-container/90',
        'aside' => $heroAside,
    ]) ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-8 flex flex-col space-y-6">
            <form class="bg-surface-container-lowest rounded-xl border p-6 lg:p-8 space-y-8 shadow-rs border-rs-line" id="monitoringForm" method="post" action="<?= e(url('/rekap/suhu')) ?>" enctype="multipart/form-data" data-module-form novalidate>
                <div class="flex items-center justify-between pb-4 border-b border-rs-line">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-6 bg-tertiary rounded-full"></span>
                        <h2 class="text-on-surface font-bold">Data Pengukuran Suhu Server Bulanan</h2>
                    </div>
                    <?= partial('badge', ['label' => '* Wajib Diisi', 'class' => 'text-error bg-error-container/40 px-2.5 py-1 rounded-sm font-semibold uppercase tracking-[.04em]']) ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <?= partial('field', [
                        'class' => 'space-y-1.5', 'label' => 'Periode Bulan Monitoring ', 'labelClass' => 'block text-on-surface', 'required' => true,
                        'control' => 'select', 'name' => 'period', 'options' => $module['months'], 'selected' => '2026-10', 'boxClass' => 'relative',
                        'controlClass' => 'w-full h-11 px-3.5 pr-10 rounded-lg bg-surface border text-on-surface focus:border-tertiary focus:ring-2 focus:ring-tertiary/20 outline-none transition-all border-rs-line',
                        'iconRight' => ['chevron-down', 'absolute right-3 top-3 pointer-events-none text-on-surface-variant text-[20px]'],
                    ]) ?>
                    <?= partial('field', [
                        'class' => 'space-y-1.5', 'label' => 'Waktu Pengambilan Data ', 'labelClass' => 'block text-on-surface', 'required' => true,
                        'type' => 'datetime-local', 'name' => 'captured_at', 'value' => $module['captured_at'], 'boxClass' => 'relative', 'attrs' => ['required' => true],
                        'controlClass' => 'w-full h-11 px-3.5 rounded-lg bg-surface border text-on-surface focus:border-tertiary focus:ring-2 focus:ring-tertiary/20 outline-none transition-all border-rs-line',
                    ]) ?>
                </div>

                <div class="p-5 rounded-xl bg-surface-container-low/50 border space-y-3 border-rs-line">
                    <div class="flex items-center justify-between">
                        <label class="text-on-surface uppercase font-bold tracking-[.04em]" for="suhuInput">RATA - RATA SUHU PERBULAN <span class="text-error">*</span></label>
                        <?= partial('badge', ['label' => 'Target: 15°C - 22°C', 'class' => 'text-tertiary bg-secondary-container px-2 py-0.5 rounded-sm font-bold']) ?>
                    </div>
                    <p class="text-on-surface-variant">Masukkan nilai rata-rata hasil logging sensor thermometer digital selama 1 bulan berjalan.</p>
                    <div class="relative flex items-center">
                        <input class="w-full h-14 pl-4 pr-32 rounded-lg bg-surface-container-lowest border-2 text-tertiary font-bold focus:border-tertiary focus:ring-0 outline-none transition-all border-rs-line" id="suhuInput" name="avg_temp" max="45" min="10" placeholder="Your answer" required step="0.1" type="number" inputmode="decimal" value="21.5">
                        <div class="absolute right-3.5 flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-surface-container text-on-surface font-bold"><span>°C (Celsius)</span></div>
                    </div>
                    <div class="flex items-center gap-2 pt-1 text-tertiary" id="suhuStatusBadge" aria-live="polite">
                        <?= icon('circle-check', 'text-[18px]') ?>
                        <span>Status Parameter: <strong data-suhu-status>Optimal &amp; Sesuai Regulasi Server Data Center</strong></span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 pt-2 text-center">
                        <?php foreach ([['18.0°C - 24.0°C', 'Kondisi Optimal', 'bg-tertiary-container/10 border-tertiary-container/20', 'text-tertiary'], ['24.1°C - 26.9°C', 'Peringatan Suhu', 'bg-amber-500/10 border-amber-500/20', 'text-amber-700'], ['≥ 27.0°C', 'Bahaya / Overheat', 'bg-error/10 border-error/20', 'text-error']] as [$range, $meaning, $box, $tone]): ?>
                        <div class="py-1.5 px-2 rounded-sm border <?= e($box) ?>">
                            <span class="block <?= e($tone) ?> font-bold"><?= e($range) ?></span>
                            <span class="block text-on-surface"><?= e($meaning) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-on-surface" id="sensorLocationLabel">Lokasi / Titik Sensor Monitoring Suhu <span class="text-error">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-labelledby="sensorLocationLabel">
                        <?php foreach ($module['locations'] as $value => $location): ?>
                        <label class="relative flex items-center p-4 rounded-xl border bg-surface cursor-pointer hover:bg-surface-container-low transition-all border-rs-line">
                            <input<?= attrs(['checked' => $value === array_key_first($module['locations']), 'class' => 'w-4 h-4 text-tertiary focus:ring-tertiary border-outline', 'name' => 'sensor_location', 'type' => 'radio', 'value' => $value]) ?>>
                            <div class="ml-3 flex flex-col">
                                <span class="text-on-surface text-[14px]"><?= e($location['name']) ?></span>
                                <span class="text-on-surface-variant">Sensor ID: <?= e($location['sensor']) ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-on-surface" for="fileUpload">Unggah Berkas Bukti / Laporan Suhu Sensor&nbsp; Perbulan<span class="text-error">*</span></label>
                        <span class="text-on-surface-variant">Upload 1 supported file. Max 10 MB.</span>
                    </div>
                    <div class="p-6 rounded-xl border-2 border-dashed hover:border-tertiary bg-surface-container-low/30 transition-all flex flex-col items-center justify-center text-center gap-3 border-rs-line">
                        <div class="w-12 h-12 rounded-full bg-secondary-container/50 text-tertiary flex items-center justify-center"><?= icon('file-up', 'text-[26px]') ?></div>
                        <div class="space-y-1">
                            <p class="text-on-surface text-[15px]">Tarik &amp; letakkan file log sensor di sini</p>
                            <p class="text-on-surface-variant">Mendukung format: PDF, CSV, XLSX, JPG/PNG (foto thermometer)</p>
                        </div>
                        <label class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-surface-container-lowest border text-tertiary font-bold hover:bg-surface-container cursor-pointer transition-colors border-rs-line">
                            <?= icon('upload', 'text-[18px]') ?><span>Add file</span>
                            <input class="hidden" id="fileUpload" name="evidence" type="file" accept=".pdf,.csv,.xlsx,.jpg,.jpeg,.png" data-file-input="fileSelectedDisplay">
                        </label>
                        <div class="hidden text-tertiary font-semibold items-center gap-1.5 pt-2" id="fileSelectedDisplay">
                            <?= icon('circle-check', 'text-[16px]') ?><span data-file-name>log-suhu-oktober-2026.pdf (2.4 MB)</span>
                        </div>
                    </div>
                </div>

                <?= partial('field', [
                    'class' => 'space-y-1.5', 'label' => 'Catatan Operasional AC & Pendingin Ruangan (PAC)', 'labelClass' => 'block text-on-surface',
                    'control' => 'textarea', 'name' => 'ac_notes', 'attrs' => ['rows' => '3'],
                    'placeholder' => 'Contoh: Unit AC Precision 1 beroperasi normal pada setpoint 20°C. Unit AC Precision 2 standby aktif. Filter udara dibersihkan pada tanggal 05 Oktober.',
                    'controlClass' => 'w-full p-3.5 rounded-lg bg-surface border text-on-surface focus:border-tertiary focus:ring-2 focus:ring-tertiary/20 outline-none transition-all border-rs-line',
                ]) ?>

                <div class="p-4 rounded-lg bg-surface-container-low border flex items-start gap-3 border-rs-line">
                    <input class="mt-1 w-4 h-4 rounded-sm text-tertiary focus:ring-tertiary border-outline" id="regulasiCheck" name="declaration" required type="checkbox">
                    <label class="text-on-surface leading-relaxed cursor-pointer" for="regulasiCheck">Saya menyatakan bahwa pencatatan rata-rata suhu server ini diambil dari data logger sensor resmi dan telah diverifikasi sesuai batas aman operasional data center RS Awal Bros Botania.</label>
                </div>

                <div class="pt-4 border-t flex flex-wrap items-center justify-between gap-4 border-rs-line">
                    <div class="flex items-center gap-2">
                        <?= partial('button', ['label' => 'Kembali ke Portal Terpadu', 'href' => url('/'), 'icon' => 'arrow-left', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-lg border bg-surface-container-lowest text-on-surface-variant font-semibold hover:bg-surface-container hover:text-on-surface transition-colors border-rs-line']) ?>
                        <?= partial('button', ['label' => 'Lihat Riwayat & Rekap', 'href' => url('/rekap/suhu'), 'icon' => 'history', 'iconClass' => 'text-[18px]', 'class' => 'hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-lg border bg-surface-container-lowest text-secondary font-semibold hover:bg-surface-container transition-colors border-rs-line']) ?>
                    </div>
                    <div class="flex items-center gap-3">
                        <?= partial('button', ['label' => 'Clear form', 'type' => 'reset', 'class' => 'px-4 py-2.5 rounded-lg text-on-surface-variant hover:text-error hover:bg-error-container/20 font-semibold transition-colors']) ?>
                        <?= partial('button', ['label' => 'Simpan Draft', 'class' => 'px-4 py-2.5 rounded-lg border bg-surface-container-lowest text-on-surface font-semibold hover:bg-surface-container transition-colors border-rs-line', 'attrs' => ['data-draft' => true]]) ?>
                        <?= partial('button', ['label' => 'Kirim Data Suhu', 'type' => 'submit', 'icon' => 'send', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-2 px-6 py-2.5 rounded-lg font-bold transition-all bg-rs-teal-700 text-white border-rs-teal-700 hover:bg-rs-teal-800 shadow-rs']) ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="lg:col-span-4 flex flex-col space-y-6">
            <div class="bg-surface-container-lowest rounded-xl p-6 border relative overflow-hidden shadow-rs border-rs-line">
                <div class="flex items-center justify-between pb-3 border-b border-rs-line">
                    <div class="flex items-center gap-2"><?= icon('radio', 'text-tertiary text-[20px]') ?><span class="font-bold text-on-surface uppercase tracking-wider">Indikator Suhu Terkini</span></div>
                    <?= partial('badge', ['label' => 'LIVE IOT', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-tertiary-container animate-pulse', 'class' => 'text-tertiary bg-secondary-container px-2 py-0.5 rounded-full flex items-center gap-1 font-semibold whitespace-nowrap']) ?>
                </div>
                <div class="py-6 flex items-center justify-between">
                    <div>
                        <div class="text-tertiary font-bold leading-none tracking-tight">15&nbsp; - 22&nbsp;<span class="text-on-surface-variant font-normal">°C</span></div>
                        <?= partial('badge', ['tag' => 'div', 'label' => 'KONDISI NORMAL', 'wrap' => false, 'icon' => 'badge-check', 'iconClass' => 'text-[14px]', 'class' => 'mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm bg-secondary-container/60 text-tertiary font-bold']) ?>
                    </div>
                    <div class="text-right space-y-1">
                        <span class="text-on-surface-variant block uppercase tracking-[.04em]">Kelembapan Udara</span>
                        <span class="font-bold text-on-surface block">52% <span class="text-on-surface-variant">RH</span></span>
                        <span class="text-tertiary block">Standar: 45-60%</span>
                    </div>
                </div>
                <div class="pt-2">
                    <div class="flex items-center justify-between text-on-surface-variant mb-1.5"><span>Tren 24 Jam Terakhir</span><span class="text-tertiary font-bold">±0.3°C Stabilitas</span></div>
                    <div class="w-full h-12 bg-surface-container-low rounded-lg p-1 flex items-end">
                        <svg class="w-full h-10 text-tertiary" fill="none" preserveAspectRatio="none" viewBox="0 0 200 40" aria-hidden="true">
                            <path d="M0,25 Q20,22 40,24 T80,20 T120,23 T160,21 T200,22" fill="none" stroke="currentColor" stroke-width="2.5"></path>
                            <path d="M0,25 Q20,22 40,24 T80,20 T120,23 T160,21 T200,22 L200,40 L0,40 Z" fill="currentColor" fill-opacity="0.12"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-xl p-6 border space-y-4 shadow-rs border-rs-line">
                <div class="flex items-center justify-between pb-3 border-b border-rs-line">
                    <div class="flex items-center gap-2"><?= icon('history', 'text-secondary text-[20px]') ?><span class="font-bold text-on-surface uppercase tracking-wider">Sampel Terakhir Tercatat</span></div>
                    <span class="text-on-surface-variant">Bulan Ini</span>
                </div>
                <?php foreach ($module['recent'] as $sample): $tone = $sample['warning'] ? 'text-amber-700' : 'text-tertiary'; ?>
                <div class="p-3.5 rounded-lg bg-surface-container-low/60 border flex items-center justify-between border-rs-line">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg <?= $sample['warning'] ? 'bg-amber-500/15' : 'bg-secondary-container' ?> <?= e($tone) ?> flex items-center justify-center font-bold"><?= icon($sample['icon'], 'text-[18px]') ?></div>
                        <div>
                            <span class="text-on-surface text-[14px] block"><?= e($sample['location']) ?></span>
                            <span class="text-on-surface-variant"><?= e($sample['time']) ?></span>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="font-bold <?= e($tone) ?> block"><?= e($sample['temp']) ?></span>
                        <?= partial('badge', ['label' => $sample['status'], 'class' => cx($tone, $sample['warning'] ? 'bg-amber-500/15 font-semibold' : 'bg-secondary-container', 'px-2 py-0.5 rounded-full inline-block whitespace-nowrap')]) ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <a class="block text-center py-2 text-tertiary hover:text-tertiary-container font-semibold transition-colors" href="<?= e(url('/rekap/suhu')) ?>">Lihat Semua Riwayat Bulanan →</a>
            </div>
        </div>
    </div>
</div>
</main>
