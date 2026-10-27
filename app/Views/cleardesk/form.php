<?php
$fieldLabel = static fn (string $for, string $text, bool $required = true): string =>
    '<label class="gf-q-title"' . attrs(['for' => $for]) . '><span>' . e($text) . '</span>' . ($required ? '<span class="gf-req">*</span>' : '') . '</label>';
$textInput = static fn (array $attributes): string => '<input' . attrs($attributes + ['required' => true, 'type' => 'text']) . '>';
?>
<?php $breadcrumbAside = partial('badge', ['tag' => 'div', 'label' => 'ISO 27001 ISMS Compliance', 'labelClass' => 'text-label-sm uppercase tracking-wider text-secondary', 'icon' => 'shield-check', 'iconClass' => 'text-tertiary text-base', 'class' => 'flex items-center gap-2 bg-surface-container-lowest px-3 py-1 rounded-full text-on-surface shadow-rs whitespace-nowrap']); ?>
<main id="main-content" class="gf-page">
    <form class="gf-form" id="clearDeskForm" method="post" action="<?= e(url('/rekap/cleardesk')) ?>" enctype="multipart/form-data" data-module-form novalidate>
        <?= partial('gf-header', [
            'eyebrow' => 'RS AWAL BROS GROUP • IT BOTANIA',
            'title' => 'Form Monitoring Clear Desk & Clear Screen',
            'description' => 'Pencatatan kepatuhan keamanan informasi fisik, penataan meja kerja (Clear Desk) dan penguncian layar monitor (Clear Screen) di lingkungan RS Awal Bros Botania sesuai standar ISO 27001 dan keamanan data rekam medis elektronik.',
            'meta' => [
                ['label' => 'PERIODE LAPORAN', 'value' => 'Inspeksi Harian (Shift Pagi)'],
                ['label' => 'Akun Verifikator IT', 'value' => '<span class="truncate">' . e(config('dashboard')['account']['email']) . '</span>'
                    . design_icon('circle-check', 'text-tertiary', ['title' => 'Verified Domain Account'])],
                ['label' => 'Waktu Inspeksi', 'labelFor' => 'inspectedAt', 'value' => '<input id="inspectedAt" name="started_at" readonly type="text" value="' . e($module['inspected_at']) . '">'],
            ],
            'required' => '<span class="gf-required-note">* WAJIB DIISI</span>',
            'rekap' => url('/rekap/cleardesk'),
        ]) ?>

        <?= partial('gf-section', ['number' => 1, 'title' => 'Data Pemeriksaan Clear Desk & Screen', 'titleTag' => 'span', 'description' => 'Formulir Log Kepatuhan Keamanan Fisik IT • Standar ISO/IEC 27001:2022 A.7.7']) ?>

        <?= partial('field', [
            'class' => 'gf-card', 'label' => 'Shift Operasional', 'labelClass' => 'gf-q-title',
            'control' => 'select', 'name' => 'shift', 'options' => $module['shifts'], 'boxClass' => 'gf-body',
            'controlClass' => 'gf-select',
        ]) ?>

        <div class="gf-card">
            <div class="gf-q-head">
                <?= $fieldLabel('lokasiInput', 'LOKASI KOMPUTER') ?>
                <span class="gf-pill">Zona Pelayanan Pasien &amp; Administrasi</span>
            </div>
            <div class="gf-body">
                <div class="gf-chips">
                    <span class="gf-chips-label">Pilihan Cepat:</span>
                    <?php foreach ($module['quick_locations'] as $quick): ?>
                    <?= partial('button', ['label' => $quick, 'class' => 'gf-chip', 'attrs' => ['data-fill-target' => 'lokasiInput']]) ?>
                    <?php endforeach; ?>
                </div>
                <?= $textInput(['class' => 'gf-input', 'id' => 'lokasiInput', 'name' => 'location_unit', 'placeholder' => 'Contoh: Ruang Rekam Medis Lt. 1 / Meja Pendaftaran UGD / Ruang Poli Anak']) ?>
            </div>
        </div>

        <div class="gf-card">
            <div class="gf-q-head">
                <?= $fieldLabel('macAddressInput', 'MAC ADDRESS') ?>
                <span class="gf-pill">NIC Hardware</span>
            </div>
            <p class="gf-help">Format MAC Address 12 digit heksadesimal dengan pemisah titik dua.</p>
            <div class="gf-body"><?= $textInput(['class' => 'gf-input gf-mono', 'id' => 'macAddressInput', 'name' => 'mac_address', 'maxlength' => '17', 'placeholder' => '00:1A:2B:3C:4D:5E', 'autocapitalize' => 'characters', 'spellcheck' => 'false']) ?></div>
        </div>

        <div class="gf-card">
            <div class="gf-q-head">
                <?= $fieldLabel('ipAddressInput', 'IP ADDRESS') ?>
                <span class="gf-pill">VLAN HIS Botania</span>
            </div>
            <p class="gf-help">Alamat IP lokal terminal kerja (Subnet 192.168.10.0/24).</p>
            <div class="gf-body"><?= $textInput(['class' => 'gf-input gf-mono', 'id' => 'ipAddressInput', 'name' => 'ip_address', 'placeholder' => '192.168.10.45', 'inputmode' => 'decimal']) ?></div>
        </div>

        <div class="gf-card">
            <span class="gf-q-title" id="osLabel"><span>SISTEM OPERASI</span><span class="gf-req">*</span></span>
            <div class="gf-body gf-options" role="radiogroup" aria-labelledby="osLabel">
                <?php foreach ($module['os_options'] as $i => $os): ?>
                <label class="gf-option">
                    <input<?= attrs(['checked' => $i === 0, 'name' => 'os_option', 'type' => 'radio', 'value' => $os['value']]) ?>>
                    <span class="gf-option-text">
                        <span class="gf-option-label"><?= e($os['label']) ?></span>
                        <span class="gf-option-note<?= !empty($os['highlight']) ? ' gf-option-note--accent' : '' ?>"><?= e($os['note']) ?></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <?= partial('gf-section', ['number' => 2, 'title' => 'Status Kepatuhan Audit Fisik', 'titleTag' => 'span', 'description' => 'Tentukan status evaluasi']) ?>

        <div class="gf-card">
            <?php foreach ($module['checks'] as $i => $check): ?>
            <div class="gf-matrix-row" role="radiogroup" aria-labelledby="<?= e($check['name']) ?>-title">
                <div class="gf-matrix-item">
                    <div class="gf-matrix-title"><span class="gf-qnum"><?= e($i + 1) ?></span><span id="<?= e($check['name']) ?>-title"><?= e($check['title']) ?></span></div>
                    <p class="gf-matrix-note"><?= e($check['description']) ?></p>
                </div>
                <div class="gf-pills">
                    <label class="gf-pill-radio"><input checked name="<?= e($check['name']) ?>" type="radio" value="compliant"><span>Patuh</span></label>
                    <label class="gf-pill-radio"><input name="<?= e($check['name']) ?>" type="radio" value="non-compliant"><span>Temuan</span></label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="gf-card">
            <div class="gf-q-head">
                <label class="gf-q-title" for="fileUpload"><span>UNGGAH BUKTI FOTO MEJA / LAYAR (OPSIONAL)</span></label>
                <span class="gf-pill">JPG, PNG, atau PDF (Maks. 10MB)</span>
            </div>
            <div class="gf-body">
                <input accept="image/*,application/pdf" capture="environment" class="hidden" id="fileUpload" name="photo" type="file" data-file-input="fileUploadLabel">
                <label class="gf-drop" for="fileUpload">
                    <span class="gf-drop-icon"><?= design_icon('cloud-upload') ?></span>
                    <span class="gf-drop-text">Tarik &amp; letakkan foto kondisi meja kerja atau layar komputer</span>
                    <span class="gf-drop-note" id="fileUploadLabel" data-file-label="Atau klik tombol di bawah untuk memilih file dari perangkat Anda">Atau klik tombol di bawah untuk memilih file dari perangkat Anda</span>
                    <span class="gf-file-btn"><?= design_icon('plus') ?><span>+ Add File Bukti</span></span>
                </label>
            </div>
        </div>

        <div class="gf-card">
            <label class="gf-q-title" for="catatanTemuan"><span>CATATAN TEMUAN / TINDAK LANJUT</span></label>
            <div class="gf-body"><textarea class="gf-input" id="catatanTemuan" name="notes" placeholder="Contoh: Diberikan kartu peringatan kepatuhan kepada staf loket 3; sticky note kredensial segera diamankan; sistem lock screen diatur ulang ke 3 menit." rows="3"></textarea></div>
        </div>

        <div class="gf-card">
            <div class="gf-confirm">
                <input id="declarationCheck" name="declaration" required type="checkbox">
                <label class="gf-confirm-label" for="declarationCheck">Saya menyatakan bahwa pemeriksaan <strong>Clear Desk and Clear Screen</strong> ini telah diverifikasi sesuai kondisi fisik riil di unit kerja dan mematuhi regulasi ISO 27001 IT RS Awal Bros Botania.</label>
            </div>
        </div>

        <div class="gf-actions">
            <a class="gf-btn gf-btn--outline" href="<?= e(url('/')) ?>"><?= design_icon('arrow-left') ?><span>Kembali ke Portal Terpadu</span></a>
            <div class="gf-actions-end">
                <button class="gf-btn gf-btn--text" type="reset">Clear form</button>
                <button class="gf-btn gf-btn--primary" type="submit"><?= design_icon('send') ?><span>Kirim Laporan Inspeksi</span></button>
            </div>
        </div>
    </form>
</main>
<script src="<?= e(asset_v('js/gform.js')) ?>" defer></script>
