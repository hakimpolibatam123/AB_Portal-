<main id="main-content" class="gf-page">
    <form class="gf-form" id="monitoringForm" method="post" action="<?= e(url('/rekap/suhu')) ?>" enctype="multipart/form-data" data-module-form novalidate>
        <?= partial('gf-header', [
            'eyebrow' => 'RS AWAL BROS GROUP • IT BOTANIA',
            'title' => 'Form Monitoring Rata - Rata Suhu Server Perbulan',
            'description' => 'Pencatatan dan verifikasi data tren suhu ruangan dan rak server data center IT RS Awal Bros Botania.',
            'meta' => [
                ['label' => 'PERIODE LAPORAN', 'value' => 'Oktober 2026 (Aktif)'],
            ],
            'required' => '<span class="gf-required-note">* Wajib Diisi</span>',
            'rekap' => url('/rekap/suhu'),
        ]) ?>

        <?= partial('gf-section', ['number' => 1, 'title' => 'Data Pengukuran Suhu Server Bulanan']) ?>

        <?= partial('field', [
            'class' => 'gf-card', 'label' => 'Periode Bulan Monitoring ', 'labelClass' => 'gf-q-title', 'required' => true, 'requiredClass' => 'gf-req',
            'control' => 'select', 'name' => 'period', 'options' => $module['months'], 'selected' => '2026-10', 'boxClass' => 'gf-body',
            'controlClass' => 'gf-select',
        ]) ?>
        <?= partial('field', [
            'class' => 'gf-card', 'label' => 'Waktu Pengambilan Data ', 'labelClass' => 'gf-q-title', 'required' => true, 'requiredClass' => 'gf-req',
            'type' => 'datetime-local', 'name' => 'captured_at', 'value' => $module['captured_at'], 'boxClass' => 'gf-body', 'attrs' => ['required' => true],
            'controlClass' => 'gf-input',
        ]) ?>

        <div class="gf-card">
            <div class="gf-q-head">
                <label class="gf-q-title" for="suhuInput">RATA - RATA SUHU PERBULAN <span class="gf-req">*</span></label>
                <span class="gf-pill">Target: 15°C - 22°C</span>
            </div>
            <p class="gf-help">Masukkan nilai rata-rata hasil logging sensor thermometer digital selama 1 bulan berjalan.</p>
            <div class="gf-body">
                <div class="gf-temp">
                    <input class="gf-input" id="suhuInput" name="avg_temp" max="45" min="10" placeholder="Your answer" required step="0.1" type="number" inputmode="decimal" value="21.5">
                    <span class="gf-unit">°C (Celsius)</span>
                </div>
                <div class="gf-status text-tertiary" id="suhuStatusBadge" aria-live="polite">
                    <?= design_icon('circle-check', 'text-[18px]') ?>
                    <span>Status Parameter: <strong data-suhu-status>Optimal &amp; Sesuai Regulasi Server Data Center</strong></span>
                </div>
                <div class="gf-ranges">
                    <?php foreach ([['18.0°C - 24.0°C', 'Kondisi Optimal', 'gf-range--ok'], ['24.1°C - 26.9°C', 'Peringatan Suhu', 'gf-range--warn'], ['≥ 27.0°C', 'Bahaya / Overheat', 'gf-range--bad']] as [$range, $meaning, $tone]): ?>
                    <div class="gf-range <?= e($tone) ?>">
                        <strong><?= e($range) ?></strong>
                        <span><?= e($meaning) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="gf-card">
            <label class="gf-q-title" id="sensorLocationLabel">Lokasi / Titik Sensor Monitoring Suhu <span class="gf-req">*</span></label>
            <div class="gf-body gf-options" role="radiogroup" aria-labelledby="sensorLocationLabel">
                <?php foreach ($module['locations'] as $value => $location): ?>
                <label class="gf-option">
                    <input<?= attrs(['checked' => $value === array_key_first($module['locations']), 'name' => 'sensor_location', 'type' => 'radio', 'value' => $value]) ?>>
                    <span class="gf-option-text">
                        <span class="gf-option-label"><?= e($location['name']) ?></span>
                        <span class="gf-option-note">Sensor ID: <?= e($location['sensor']) ?></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="gf-card">
            <label class="gf-q-title" for="fileUpload">Unggah Berkas Bukti / Laporan Suhu Sensor&nbsp; Perbulan<span class="gf-req">*</span></label>
            <p class="gf-help">Upload 1 supported file. Max 10 MB.</p>
            <div class="gf-body gf-drop">
                <div class="gf-drop-icon"><?= design_icon('file-up') ?></div>
                <p class="gf-drop-text">Tarik &amp; letakkan file log sensor di sini</p>
                <p class="gf-drop-note">Mendukung format: PDF, CSV, XLSX, JPG/PNG (foto thermometer)</p>
                <label class="gf-file-btn">
                    <?= design_icon('upload') ?><span>Add file</span>
                    <input class="hidden" id="fileUpload" name="evidence" type="file" accept=".pdf,.csv,.xlsx,.jpg,.jpeg,.png" data-file-input="fileSelectedDisplay">
                </label>
                <div class="gf-file-name hidden" id="fileSelectedDisplay">
                    <?= design_icon('circle-check') ?><span data-file-name>log-suhu-oktober-2026.pdf (2.4 MB)</span>
                </div>
            </div>
        </div>

        <?= partial('field', [
            'class' => 'gf-card', 'label' => 'Catatan Operasional AC & Pendingin Ruangan (PAC)', 'labelClass' => 'gf-q-title', 'boxClass' => 'gf-body',
            'control' => 'textarea', 'name' => 'ac_notes', 'attrs' => ['rows' => '3'],
            'placeholder' => 'Contoh: Unit AC Precision 1 beroperasi normal pada setpoint 20°C. Unit AC Precision 2 standby aktif. Filter udara dibersihkan pada tanggal 05 Oktober.',
            'controlClass' => 'gf-input',
        ]) ?>

        <div class="gf-card">
            <div class="gf-confirm">
                <input id="regulasiCheck" name="declaration" required type="checkbox">
                <label class="gf-confirm-label" for="regulasiCheck">Saya menyatakan bahwa pencatatan rata-rata suhu server ini diambil dari data logger sensor resmi dan telah diverifikasi sesuai batas aman operasional data center RS Awal Bros Botania.</label>
            </div>
        </div>

        <div class="gf-actions">
            <a class="gf-btn gf-btn--outline" href="<?= e(url('/')) ?>"><?= design_icon('arrow-left') ?><span>Kembali ke Portal Terpadu</span></a>
            <div class="gf-actions-end">
                <button class="gf-btn gf-btn--text" type="reset">Clear form</button>
                <button class="gf-btn gf-btn--primary" type="submit"><?= design_icon('send') ?><span>Kirim Data Suhu</span></button>
            </div>
        </div>
    </form>
</main>
<script src="<?= e(asset_v('js/gform.js')) ?>" defer></script>
