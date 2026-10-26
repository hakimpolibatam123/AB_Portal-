<?php
$daily = $module['daily'];
$fieldLabel = static fn (string $for, string $text, bool $required = true): string =>
    '<label class="text-headline-sm font-bold text-on-surface flex items-center gap-1.5"' . attrs(['for' => $for]) . '><span>' . e($text) . '</span>' . ($required ? '<span class="text-primary">*</span>' : '') . '</label>';
$iconInput = static fn (string $icon, array $attributes): string =>
    '<div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">' . icon($icon, 'text-on-surface-variant text-lg') . '</div>'
    . '<input' . attrs($attributes + ['required' => true, 'type' => 'text']) . '></div>';
ob_start(); ?>
<div class="flex flex-col sm:flex-row lg:flex-col items-start lg:items-end gap-3 shrink-0">
    <div class="bg-surface-container-lowest/10 backdrop-blur-md px-4 py-3 rounded-lg flex items-center gap-3 text-on-tertiary shadow-rs">
        <?= icon('calendar-check', 'text-secondary-fixed text-2xl') ?>
        <div>
            <div class="text-label-sm text-secondary-container tracking-wider uppercase">PERIODE LAPORAN</div>
            <div class="text-headline-sm font-semibold">Inspeksi Harian (Shift Pagi)</div>
        </div>
    </div>
    <?= partial('button', ['label' => 'Lihat Rekap & Unduh Laporan', 'href' => url('/rekap/cleardesk'), 'icon' => 'file-text', 'iconClass' => 'text-lg', 'class' => 'inline-flex items-center gap-2 bg-surface-container-lowest text-tertiary hover:bg-surface-container-low px-4 py-2.5 rounded-full text-body-sm font-semibold transition-all shadow-rs whitespace-nowrap']) ?>
</div>
<?php $heroAside = ob_get_clean(); ?>
<main id="main-content" class="w-full bg-surface min-h-[calc(100vh-56px)]">
<div class="flex flex-col w-full">
    <div class="w-full bg-surface-container-low px-space-md lg:px-margin py-3 border-b-0 flex flex-wrap items-center justify-between gap-3 shadow-rs">
        <?= partial('breadcrumb', [
            'class' => 'flex items-center gap-2 text-body-sm text-on-surface-variant flex-wrap',
            'separator' => ['icon' => 'chevron-right', 'class' => 'text-sm text-on-surface-variant/60'],
            'items' => [
                ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'class' => 'hover:text-tertiary cursor-pointer transition-colors'],
                ['label' => 'Monitoring Harian', 'class' => 'hover:text-tertiary cursor-pointer transition-colors'],
                ['label' => 'Clear Desk & Screen', 'class' => 'hover:text-tertiary cursor-pointer transition-colors'],
                ['label' => 'Form Input Inspeksi', 'class' => 'font-semibold text-tertiary'],
            ],
        ]) ?>
        <?= partial('badge', ['tag' => 'div', 'label' => 'ISO 27001 ISMS Compliance', 'labelClass' => 'text-label-sm uppercase tracking-wider text-secondary', 'icon' => 'shield-check', 'iconClass' => 'text-tertiary text-base', 'class' => 'flex items-center gap-2 bg-surface-container-lowest px-3 py-1 rounded-full text-on-surface shadow-rs whitespace-nowrap']) ?>
    </div>

    <div class="w-full px-space-md lg:px-margin py-space-lg space-y-6 max-w-[1680px] mx-auto">
        <?= partial('page-header', [
            'class' => 'relative overflow-hidden rounded-xl p-6 lg:p-8', 'introClass' => 'max-w-3xl space-y-3',
            'eyebrow' => partial('badge', ['tag' => 'div', 'label' => 'RS AWAL BROS GROUP • IT BOTANIA', 'dot' => 'w-2 h-2 rounded-full bg-secondary-fixed animate-ping', 'class' => 'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-surface-container-lowest/15 backdrop-blur-xs text-on-tertiary text-label-sm tracking-wider uppercase whitespace-nowrap']),
            'title' => 'Form Monitoring Clear Desk & Clear Screen',
            'description' => 'Pencatatan kepatuhan keamanan informasi fisik, penataan meja kerja (Clear Desk) dan penguncian layar monitor (Clear Screen) di lingkungan RS Awal Bros Botania sesuai standar ISO 27001 dan keamanan data rekam medis elektronik.',
            'descriptionClass' => 'text-body-md text-on-tertiary/90',
            'aside' => $heroAside,
        ]) ?>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
            <div class="xl:col-span-8 flex flex-col gap-6">
                <form class="bg-surface-container-lowest rounded-xl p-6 lg:p-8 space-y-8 shadow-rs" id="clearDeskForm" method="post" action="<?= e(url('/rekap/cleardesk')) ?>" enctype="multipart/form-data" data-module-form novalidate>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 bg-surface-container-low/50 -mx-6 lg:-mx-8 -mt-6 lg:-mt-8 p-6 lg:p-8 rounded-t-[12px] gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1"><?= icon('shield', 'text-primary text-xl') ?><span class="text-headline-md font-bold text-on-surface">Data Pemeriksaan Clear Desk &amp; Screen</span></div>
                            <p class="text-body-sm text-on-surface-variant">Formulir Log Kepatuhan Keamanan Fisik IT • Standar ISO/IEC 27001:2022 A.7.7</p>
                        </div>
                        <?= partial('badge', ['label' => '* WAJIB DIISI', 'class' => 'inline-flex items-center px-3 py-1 rounded-full bg-error-container text-on-error-container text-label-sm font-semibold tracking-wider self-start sm:self-center whitespace-nowrap']) ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 rounded-xl bg-surface-container-low">
                        <div class="space-y-1">
                            <span class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider block">Akun Verifikator IT</span>
                            <div class="flex items-center gap-2 bg-surface-container-lowest px-3 py-2 rounded-lg shadow-rs">
                                <?= icon('circle-user', 'text-tertiary text-base') ?>
                                <span class="text-label-md font-bold text-on-surface truncate"><?= e(config('dashboard')['profile']['email']) ?></span>
                                <?= icon('circle-check', 'text-tertiary text-sm ml-auto', ['title' => 'Verified Domain Account']) ?>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider block" for="inspectedAt">Waktu Inspeksi</label>
                            <div class="flex items-center gap-2 bg-surface-container-lowest px-3 py-2 rounded-lg shadow-rs">
                                <?= icon('clock', 'text-secondary text-base') ?>
                                <input class="text-label-md text-on-surface bg-transparent focus:outline-none w-full" id="inspectedAt" name="started_at" readonly type="text" value="<?= e($module['inspected_at']) ?>">
                            </div>
                        </div>
                        <?= partial('field', [
                            'class' => 'space-y-1', 'label' => 'Shift Operasional', 'labelClass' => 'text-label-sm font-semibold text-on-surface-variant uppercase tracking-wider block',
                            'control' => 'select', 'name' => 'shift', 'options' => $module['shifts'], 'boxClass' => 'relative',
                            'controlClass' => 'w-full bg-surface-container-lowest text-body-md text-on-surface px-3 py-2 rounded-lg focus:outline-none appearance-none pr-8 shadow-rs',
                            'iconRight' => ['chevron-down', 'absolute right-2.5 top-2.5 text-on-surface-variant pointer-events-none text-base'],
                        ]) ?>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <?= $fieldLabel('lokasiInput', 'LOKASI KOMPUTER') ?>
                            <span class="text-label-sm text-on-surface-variant">Zona Pelayanan Pasien &amp; Administrasi</span>
                        </div>
                        <?= $iconInput('map-pin', ['class' => 'w-full bg-surface-container-low focus:bg-surface-container-lowest text-body-md text-on-surface pl-10 pr-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary transition-all shadow-rs', 'id' => 'lokasiInput', 'name' => 'location_unit', 'placeholder' => 'Contoh: Ruang Rekam Medis Lt. 1 / Meja Pendaftaran UGD / Ruang Poli Anak']) ?>
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <span class="text-label-sm text-on-surface-variant">Pilihan Cepat:</span>
                            <?php foreach ($module['quick_locations'] as $quick): ?>
                            <?= partial('button', ['label' => $quick, 'class' => 'px-2.5 py-1 rounded-full bg-surface-container hover:bg-secondary-container hover:text-on-secondary-container text-label-sm text-on-surface transition-colors shadow-rs whitespace-nowrap', 'attrs' => ['data-fill-target' => 'lokasiInput']]) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <?= $fieldLabel('macAddressInput', 'MAC ADDRESS') ?>
                                <span class="text-label-sm text-secondary font-medium">NIC Hardware</span>
                            </div>
                            <?= $iconInput('ethernet-port', ['class' => 'w-full bg-surface-container-low focus:bg-surface-container-lowest text-label-md text-on-surface pl-10 pr-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary transition-all shadow-rs', 'id' => 'macAddressInput', 'name' => 'mac_address', 'maxlength' => '17', 'placeholder' => '00:1A:2B:3C:4D:5E', 'autocapitalize' => 'characters', 'spellcheck' => 'false']) ?>
                            <p class="text-body-sm text-on-surface-variant">Format MAC Address 12 digit heksadesimal dengan pemisah titik dua.</p>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <?= $fieldLabel('ipAddressInput', 'IP ADDRESS') ?>
                                <span class="text-label-sm bg-surface-container px-2 py-0.5 rounded-sm text-on-secondary-container font-semibold">VLAN HIS Botania</span>
                            </div>
                            <?= $iconInput('router', ['class' => 'w-full bg-surface-container-low focus:bg-surface-container-lowest text-label-md text-on-surface pl-10 pr-4 py-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary transition-all shadow-rs', 'id' => 'ipAddressInput', 'name' => 'ip_address', 'placeholder' => '192.168.10.45', 'inputmode' => 'decimal']) ?>
                            <p class="text-body-sm text-on-surface-variant">Alamat IP lokal terminal kerja (Subnet 192.168.10.0/24).</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <span class="text-headline-sm font-bold text-on-surface flex items-center gap-1.5" id="osLabel"><span>SISTEM OPERASI</span><span class="text-primary">*</span></span>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3" role="radiogroup" aria-labelledby="osLabel">
                            <?php foreach ($module['os_options'] as $i => $os): ?>
                            <label class="relative flex items-center p-3.5 rounded-xl bg-surface-container-low hover:bg-surface-container cursor-pointer transition-all<?= !empty($os['wide']) ? ' sm:col-span-2' : '' ?> shadow-rs">
                                <input<?= attrs(['checked' => $i === 0, 'class' => 'w-4 h-4 text-tertiary focus:ring-tertiary accent-tertiary', 'name' => 'os_option', 'type' => 'radio', 'value' => $os['value']]) ?>>
                                <div class="ml-3">
                                    <div class="text-body-md font-semibold text-on-surface"><?= e($os['label']) ?></div>
                                    <div class="text-label-sm <?= !empty($os['highlight']) ? 'text-secondary' : 'text-on-surface-variant' ?>"><?= e($os['note']) ?></div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="space-y-4 pt-2">
                        <div class="flex items-center justify-between pb-2 bg-surface-container-low px-4 py-2.5 rounded-lg">
                            <div class="flex items-center gap-2"><?= icon('list-checks', 'text-tertiary text-xl') ?><span class="text-headline-sm font-bold text-on-surface">Status Kepatuhan Audit Fisik</span></div>
                            <span class="text-label-sm text-on-surface-variant">Tentukan status evaluasi</span>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($module['checks'] as $i => $check): ?>
                            <div class="p-4 rounded-xl bg-surface-container-low flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-rs" role="radiogroup" aria-labelledby="<?= e($check['name']) ?>-title">
                                <div class="space-y-1 max-w-xl">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-surface-container-highest text-on-surface text-label-sm font-bold flex items-center justify-center whitespace-nowrap"><?= e($i + 1) ?></span>
                                        <span class="text-headline-sm font-bold text-on-surface" id="<?= e($check['name']) ?>-title"><?= e($check['title']) ?></span>
                                    </div>
                                    <p class="text-body-sm text-on-surface-variant pl-8"><?= e($check['description']) ?></p>
                                </div>
                                <div class="flex items-center gap-3 pl-8 md:pl-0 shrink-0">
                                    <label class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-secondary-container text-on-secondary-container text-body-sm font-semibold cursor-pointer shadow-rs">
                                        <input checked class="w-4 h-4 accent-tertiary" name="<?= e($check['name']) ?>" type="radio" value="compliant"><span>Patuh</span>
                                    </label>
                                    <label class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-surface-container text-on-surface-variant text-body-sm font-medium cursor-pointer hover:bg-error-container hover:text-on-error-container transition-colors shadow-rs">
                                        <input class="w-4 h-4 accent-primary" name="<?= e($check['name']) ?>" type="radio" value="non-compliant"><span>Temuan</span>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-headline-sm font-bold text-on-surface flex items-center gap-2" for="fileUpload"><?= icon('image-plus', 'text-secondary text-xl') ?><span>UNGGAH BUKTI FOTO MEJA / LAYAR (OPSIONAL)</span></label>
                            <span class="text-label-sm text-on-surface-variant">JPG, PNG, atau PDF (Maks. 10MB)</span>
                        </div>
                        <div class="rounded-xl bg-surface-container-low p-6 text-center hover:bg-surface-container transition-colors cursor-pointer group shadow-rs">
                            <input accept="image/*,application/pdf" capture="environment" class="hidden" id="fileUpload" name="photo" type="file" data-file-input="fileUploadLabel">
                            <label class="cursor-pointer flex flex-col items-center justify-center space-y-2" for="fileUpload">
                                <div class="w-12 h-12 rounded-full bg-surface-container-lowest text-tertiary flex items-center justify-center group-hover:scale-110 transition-transform shadow-rs whitespace-nowrap"><?= icon('cloud-upload', 'text-2xl') ?></div>
                                <div class="text-headline-sm font-semibold text-on-surface">Tarik &amp; letakkan foto kondisi meja kerja atau layar komputer</div>
                                <p class="text-body-sm text-on-surface-variant" id="fileUploadLabel" data-file-label="Atau klik tombol di bawah untuk memilih file dari perangkat Anda">Atau klik tombol di bawah untuk memilih file dari perangkat Anda</p>
                                <div class="pt-2"><span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-surface-container-lowest text-on-surface text-body-sm font-semibold hover:bg-surface-container-high transition-colors shadow-rs"><?= icon('plus', 'text-base') ?><span>+ Add File Bukti</span></span></div>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-headline-sm font-bold text-on-surface flex items-center gap-2" for="catatanTemuan"><?= icon('align-left', 'text-secondary text-xl') ?><span>CATATAN TEMUAN / TINDAK LANJUT</span></label>
                        <textarea class="w-full bg-surface-container-low focus:bg-surface-container-lowest text-body-md text-on-surface p-3.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-tertiary transition-all shadow-rs" id="catatanTemuan" name="notes" placeholder="Contoh: Diberikan kartu peringatan kepatuhan kepada staf loket 3; sticky note kredensial segera diamankan; sistem lock screen diatur ulang ke 3 menit." rows="3"></textarea>
                    </div>

                    <div class="p-4 rounded-xl bg-surface-container-low/70 flex items-start gap-3">
                        <input class="w-5 h-5 mt-0.5 text-tertiary focus:ring-tertiary accent-tertiary rounded-sm shadow-rs" id="declarationCheck" name="declaration" required type="checkbox">
                        <label class="text-body-sm text-on-surface cursor-pointer select-none" for="declarationCheck">Saya menyatakan bahwa pemeriksaan <strong class="font-semibold text-on-surface">Clear Desk and Clear Screen</strong> ini telah diverifikasi sesuai kondisi fisik riil di unit kerja dan mematuhi regulasi ISO 27001 IT RS Awal Bros Botania.</label>
                    </div>

                    <div class="pt-4 flex flex-col md:flex-row items-center justify-between gap-4 bg-surface-container-low/50 -mx-6 lg:-mx-8 p-6 lg:p-8 rounded-b-[12px]">
                        <div class="flex items-center gap-4 w-full md:w-auto justify-between md:justify-start">
                            <?= partial('button', ['label' => 'Kembali ke Portal Terpadu', 'href' => url('/'), 'icon' => 'arrow-left', 'iconClass' => 'text-lg', 'class' => 'inline-flex items-center gap-1.5 text-body-sm font-semibold text-on-surface-variant hover:text-on-surface transition-colors']) ?>
                            <?= partial('button', ['label' => 'Lihat Riwayat', 'href' => url('/rekap/cleardesk'), 'class' => 'text-body-sm font-semibold text-secondary hover:text-tertiary transition-colors']) ?>
                        </div>
                        <div class="flex items-center gap-3 w-full md:w-auto justify-end flex-wrap">
                            <?= partial('button', ['label' => 'Clear form', 'type' => 'reset', 'class' => 'px-4 py-2 text-body-sm font-semibold text-on-surface-variant hover:text-primary transition-colors']) ?>
                            <?= partial('button', ['label' => 'Simpan Draft', 'class' => 'px-4 py-2.5 rounded-lg bg-surface-container-lowest text-on-surface hover:bg-surface-container text-body-sm font-semibold transition-all shadow-rs', 'attrs' => ['data-draft' => true]]) ?>
                            <?= partial('button', ['label' => 'Kirim Laporan Inspeksi', 'type' => 'submit', 'icon' => 'send', 'iconClass' => 'text-lg', 'class' => 'inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-body-sm font-bold transition-all bg-rs-teal-700 text-white border-rs-teal-700 hover:bg-rs-teal-800 shadow-rs']) ?>
                        </div>
                    </div>
                </form>
            </div>

            <div class="xl:col-span-4 flex flex-col gap-6">
                <div class="bg-surface-container-lowest rounded-xl p-6 space-y-5 shadow-rs">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><?= icon('gauge', 'text-tertiary text-xl') ?><h2 class="text-headline-sm font-bold text-on-surface">Kepatuhan Harian</h2></div>
                        <?= partial('badge', ['label' => 'Live Audit', 'class' => 'text-label-sm text-secondary bg-secondary-fixed/40 px-2 py-0.5 rounded-full font-semibold whitespace-nowrap']) ?>
                    </div>
                    <div class="flex items-center justify-center py-2">
                        <div class="relative w-40 h-40 flex items-center justify-center">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                                <circle class="stroke-surface-container-high" cx="60" cy="60" fill="transparent" r="50" stroke-width="10"></circle>
                                <circle class="stroke-tertiary" cx="60" cy="60" fill="transparent" r="50" stroke-dasharray="314" stroke-dashoffset="<?= e($daily['ring_offset']) ?>" stroke-linecap="round" stroke-width="10"></circle>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-headline-xl font-bold text-on-surface leading-none"><?= e($daily['rate']) ?></span>
                                <span class="text-label-sm text-secondary font-medium uppercase tracking-wider mt-1">Tingkat Patuh</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="p-3 rounded-lg bg-surface-container-low text-center">
                            <div class="text-label-sm text-on-surface-variant font-medium">Workstation Diperiksa</div>
                            <div class="text-headline-md font-bold text-on-surface mt-0.5"><?= e($daily['inspected']) ?> <span class="text-body-sm font-normal text-on-surface-variant">/ <?= e($daily['target']) ?></span></div>
                        </div>
                        <div class="p-3 rounded-lg bg-surface-container-low text-center">
                            <div class="text-label-sm text-on-surface-variant font-medium">Sisa Target Unit</div>
                            <div class="text-headline-md font-bold text-tertiary mt-0.5"><?= e($daily['remaining']) ?> <span class="text-body-sm font-normal text-on-surface-variant">Terminal</span></div>
                        </div>
                    </div>
                    <div class="p-3 rounded-lg bg-error-container/40 flex items-center gap-3">
                        <?= icon('triangle-alert', 'text-error text-xl shrink-0') ?>
                        <div class="text-body-sm text-on-error-container"><strong class="font-semibold"><?= e($daily['findings']) ?> Temuan Pelanggaran Ringan</strong> terdeteksi pada shift berjalan dan telah diberikan teguran langsung.</div>
                    </div>
                </div>

                <div class="bg-surface-container-lowest rounded-xl p-6 space-y-4 shadow-rs">
                    <div class="flex items-center justify-between pb-2">
                        <div class="flex items-center gap-2"><?= icon('history', 'text-primary text-xl') ?><h2 class="text-headline-sm font-bold text-on-surface">Log Temuan Hari Ini</h2></div>
                        <span class="text-label-sm text-on-surface-variant"><?= e(count($module['findings_today'])) ?> Insiden</span>
                    </div>
                    <div class="space-y-3">
                        <?php foreach ($module['findings_today'] as $finding): ?>
                        <div class="p-3.5 rounded-lg bg-surface-container-low space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-body-sm font-bold text-on-surface"><?= e($finding['location']) ?></span>
                                <?= partial('badge', ['label' => ' Resolved', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-tertiary', 'class' => 'inline-flex items-center gap-1 text-label-sm text-tertiary font-bold bg-secondary-fixed/50 px-2 py-0.5 rounded-full whitespace-nowrap']) ?>
                            </div>
                            <p class="text-body-sm text-on-surface-variant"><?= e($finding['note']) ?></p>
                            <div class="flex items-center justify-between text-label-sm text-on-surface-variant/70 pt-1"><span><?= e($finding['time']) ?></span><span><?= e($finding['auditor']) ?></span></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
