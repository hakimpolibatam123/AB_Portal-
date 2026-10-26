<?php
$cameras = $module['cameras'];
$sectionTag = static fn (string $label): string => partial('badge', ['label' => $label, 'class' => 'text-[12px] font-bold uppercase text-slate-500 bg-slate-200/80 px-2 py-0.5 rounded-sm leading-[1.5] tracking-[.04em]']);
$radioCard = static fn (string $name, string $value, bool $checked, string $tone, string $hover, string $body): string =>
    '<label class="flex items-start p-3.5 rounded-xl bg-white border ' . e($hover) . ' cursor-pointer transition-all border-rs-line">'
    . '<input' . attrs(['checked' => $checked, 'class' => 'mt-1 w-4 h-4 ' . $tone, 'name' => $name, 'type' => 'radio', 'value' => $value]) . '>'
    . $body . '</label>';
ob_start(); ?>
<div class="flex flex-wrap sm:flex-nowrap items-center gap-3">
    <div class="flex items-center gap-3 bg-emerald-900/70 border border-emerald-700/50 px-4 py-2.5 rounded-xl text-white">
        <?= icon('calendar-check', 'text-emerald-300 text-[26px]') ?>
        <div class="flex flex-col">
            <span class="text-[11px] uppercase tracking-wider text-emerald-300 font-bold leading-[1.45]">PERIODE LAPORAN</span>
            <span class="text-sm font-bold text-white">Inspeksi Harian (Shift Pagi)</span>
        </div>
    </div>
    <?= partial('button', ['label' => 'Lihat Rekap & Unduh Laporan', 'href' => url('/rekap/cctv'), 'icon' => 'clipboard-list', 'iconClass' => 'text-[#044d36] text-[20px]', 'class' => 'inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-white text-[#044d36] hover:bg-emerald-50 text-sm font-bold transition-all whitespace-nowrap shadow-rs']) ?>
</div>
<?php $heroAside = ob_get_clean(); ?>
<main id="main-content" class="w-full flex-1 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col gap-5">
    <?= partial('breadcrumb', [
        'class' => 'flex items-center gap-2 text-xs font-medium text-slate-500',
        'separator' => ['text' => '/', 'class' => 'text-slate-400'],
        'items' => [
            ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'class' => 'hover:text-[#b70011] transition-colors'],
            ['label' => 'Monitoring Harian', 'href' => url('/'), 'class' => 'hover:text-[#b70011] transition-colors', 'separator' => ['text' => '>', 'class' => 'text-slate-400']],
            ['label' => 'CCTV', 'href' => url('/form/cctv'), 'class' => 'hover:text-[#b70011] transition-colors'],
            ['label' => 'Form Input Monitoring CCTV', 'class' => 'text-slate-900 font-semibold'],
        ],
    ]) ?>

    <?= partial('page-header', [
        'class' => 'relative overflow-hidden rounded-2xl p-6', 'introClass' => 'flex flex-col gap-1.5 max-w-2xl',
        'eyebrow' => partial('badge', ['tag' => 'div', 'label' => 'RS AWAL BROS GROUP • IT BOTANIA', 'dot' => 'w-1.5 h-1.5 rounded-full bg-emerald-400', 'class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-800/80 text-emerald-200 text-[12px] font-bold uppercase tracking-wider self-start border border-emerald-700/60 leading-[1.5] whitespace-nowrap']),
        'title' => 'Form Monitoring CCTV & Kamera Pengawas', 'titleClass' => 'mt-1',
        'description' => 'Pencatatan harian status kamera pengawas, kelayakan IP, dan kesesuaian sinkronisasi jam CCTV di lingkungan RS Awal Bros Botania.',
        'descriptionClass' => 'text-emerald-100/90 text-sm',
        'aside' => $heroAside,
    ]) ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <section class="lg:col-span-8 flex flex-col gap-6" aria-label="Form pemeriksaan CCTV">
            <form class="bg-white rounded-2xl border p-6 sm:p-8 flex flex-col gap-6 shadow-rs border-rs-line" id="cctvMonitoringForm" method="post" action="<?= e(url('/rekap/cctv')) ?>" enctype="multipart/form-data" data-module-form novalidate>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b gap-3 border-rs-line">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-[#044d36]"><?= icon('video', 'text-[24px]') ?></div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Data Pemeriksaan CCTV Harian</h2>
                            <p class="text-xs text-slate-500">Formulir Log Operasional Standar NOC IT Botania</p>
                        </div>
                    </div>
                    <?= partial('badge', ['label' => '* WAJIB DIISI', 'icon' => 'circle-alert', 'iconClass' => 'text-[14px]', 'wrap' => false, 'class' => 'inline-flex items-center gap-1 px-3 py-1 rounded-full bg-red-100 text-red-800 text-xs font-bold self-start sm:self-auto whitespace-nowrap']) ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5 p-3.5 rounded-xl bg-slate-50 border border-rs-line">
                        <span class="text-xs font-bold text-slate-600 uppercase flex items-center gap-1.5 tracking-[.04em]"><?= icon('shield-check', 'text-slate-500 text-[16px]') ?>Akun Verifikator IT</span>
                        <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-rs-line">
                            <span class="text-sm font-semibold text-slate-800"><?= e(config('dashboard')['profile']['email']) ?></span>
                            <?= partial('badge', ['label' => 'Verified', 'icon' => 'circle-check', 'iconClass' => 'text-[13px]', 'wrap' => false, 'class' => 'inline-flex items-center gap-1 text-[12px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-sm border border-emerald-200 leading-[1.5]']) ?>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5 p-3.5 rounded-xl bg-slate-50 border border-rs-line">
                        <span class="text-xs font-bold text-slate-600 uppercase flex items-center gap-1.5 tracking-[.04em]"><?= icon('calendar', 'text-slate-500 text-[16px]') ?>Waktu &amp; Tanggal Pemeriksaan *</span>
                        <div class="flex items-center justify-between bg-white px-3 py-2 rounded-lg border border-rs-line">
                            <span class="text-sm font-semibold text-slate-800"><?= e($module['inspected_at']) ?></span>
                            <?= icon('calendar', 'text-slate-400 text-[18px]') ?>
                            <input type="hidden" name="inspected_at" value="<?= e($module['inspected_at']) ?>">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div class="flex flex-col gap-1.5"><div class="relative"></div></div></div>

                <div class="flex flex-col gap-2 p-4 rounded-xl bg-slate-50 border border-rs-line">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-bold text-slate-900 uppercase tracking-[.04em]" for="cctvDamagedInput">ISI CCTV YANG RUSAK DAN IP-NYA <span class="text-[#b70011]">*</span></label>
                        <?= $sectionTag('Log Unit & IP') ?>
                    </div>
                    <p class="text-xs text-slate-600">Masukkan nama titik kamera, nomor IP, dan kendala (contoh: <em>CCTV-UGD-02 / 192.168.10.42 - Offline</em>). Ketik <strong>"NIHIL"</strong> jika semua kamera normal.</p>
                    <textarea class="w-full p-3 rounded-lg border border-slate-300 bg-white text-xs text-slate-800 focus:outline-none focus:border-[#044d36] focus:ring-1 focus:ring-[#044d36]" id="cctvDamagedInput" name="damaged_cameras" required data-char-count="charIndicator" placeholder="Contoh:&#10;1. CCTV-UGD-02 (IP: 192.168.10.42) - Offline / Putus Jaringan&#10;2. CCTV-PARKIR-TIMUR (IP: 192.168.10.112) - Kamera buram terkena embun" rows="4"></textarea>
                    <div class="flex items-center justify-between text-[12px] text-slate-500 pt-1 leading-[1.5]">
                        <span class="flex items-center gap-1"><?= icon('info', 'text-[14px] text-emerald-700') ?>Format: [Nama Titik / Channel] / [Alamat IP] - [Gejala Kerusakan]</span>
                        <span id="charIndicator" aria-live="polite">0 Karakter</span>
                    </div>
                </div>

                <div class="flex flex-col gap-2 p-4 rounded-xl bg-slate-50 border border-rs-line">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 uppercase tracking-[.04em]" id="ntpLabel">KESESUAIAN JAM CCTV HARIAN <span class="text-[#b70011]">*</span></span>
                        <?= $sectionTag('NTP Synchronize') ?>
                    </div>
                    <p class="text-xs text-slate-600">Pastikan jam internal NVR/DVR dan tampilan kamera CCTV tersinkronisasi presisi dengan master NTP Server RS Botania</p>
                    <div class="grid grid-cols-1 gap-2.5 mt-2" role="radiogroup" aria-labelledby="ntpLabel">
                        <?= $radioCard('ntp_status', 'sesuai', true, 'text-[#044d36] focus:ring-[#044d36] accent-[#044d36]', 'hover:border-emerald-600',
                            '<div class="ml-3 flex flex-col flex-1"><div class="flex items-center gap-2"><span class="text-sm font-bold text-slate-900">Sesuai</span>'
                            . partial('badge', ['label' => 'Optimal', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-emerald-600', 'class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[12px] font-bold leading-[1.5] whitespace-nowrap'])
                            . '</div><span class="text-xs text-slate-500 mt-0.5">Sinkron NTP Server RS 172.24.10.2 / Toleransi &lt; 5 detik pada seluruh rekaman channel.</span></div>') ?>
                        <?= $radioCard('ntp_status', 'tidak_sesuai', false, 'text-amber-600 focus:ring-amber-600 accent-amber-600', 'hover:border-amber-500',
                            '<div class="ml-3 flex flex-col flex-1"><div class="flex items-center gap-2"><span class="text-sm font-bold text-slate-900">Tidak Sesuai / Selisih Waktu</span>'
                            . partial('badge', ['label' => 'Perlu Kalibrasi', 'class' => 'inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[12px] font-bold leading-[1.5] whitespace-nowrap'])
                            . '</div><span class="text-xs text-slate-500 mt-0.5">Perlu kalibrasi ulang waktu NVR karena selisih &gt; 1 menit atau offset time zone berbeda.</span></div>') ?>
                        <label class="flex flex-col p-3.5 rounded-xl bg-white border hover:border-slate-400 cursor-pointer transition-all border-rs-line">
                            <div class="flex items-center">
                                <input class="w-4 h-4 text-slate-700 focus:ring-slate-700 accent-slate-700" name="ntp_status" type="radio" value="other">
                                <span class="ml-3 text-sm font-bold text-slate-900">Other / Keterangan Khusus</span>
                            </div>
                            <div class="mt-3 ml-7 hidden" id="otherFieldWrapper" data-reveal-on="ntp_status=other">
                                <input class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-slate-50 text-xs text-slate-800 focus:outline-none focus:border-[#044d36] focus:bg-white" name="ntp_note" placeholder="Tuliskan keterangan detail deviasi waktu atau unit yang bermasalah..." type="text" aria-label="Keterangan sinkronisasi jam">
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex flex-col gap-2 p-4 rounded-xl bg-slate-50 border border-rs-line">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 uppercase tracking-[.04em]" id="nvrLabel">STATUS &amp; HEALTH CHECK NVR / STORAGE <span class="text-[#b70011]">*</span></span>
                        <?= $sectionTag('RAID / HDD Integrity') ?>
                    </div>
                    <p class="text-xs text-slate-600">Kapasitas Penyimpanan dan ketahanan retensi data video rekaman (Standar Akreditasi KARS minimal 30 hari).</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1" role="radiogroup" aria-labelledby="nvrLabel">
                        <?= $radioCard('storage_health', 'normal', true, 'text-[#044d36] focus:ring-[#044d36] accent-[#044d36]', 'hover:border-emerald-600',
                            '<div class="ml-3 flex flex-col"><span class="text-xs font-bold text-slate-900">Normal (Retensi &gt; 30 Hari)</span><span class="text-[12px] text-slate-500 mt-0.5 leading-[1.5]">Sisa ruang aman (&gt; 20%), status hard disk OK, auto-overwrite berjalan.</span></div>') ?>
                        <?= $radioCard('storage_health', 'kritis', false, 'text-red-600 focus:ring-red-600 accent-red-600', 'hover:border-red-600',
                            '<div class="ml-3 flex flex-col"><span class="text-xs font-bold text-red-600">Kapasitas Kritis (&gt; 90%)</span><span class="text-[12px] text-slate-500 mt-0.5 leading-[1.5]">Penyimpanan menipis, peringatan disk degraded atau resiko rekaman terpotong.</span></div>') ?>
                    </div>
                </div>

                <div class="flex flex-col gap-2 p-4 rounded-xl bg-slate-50 border border-rs-line">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-900 uppercase tracking-[.04em]">UNGGAH BUKTI MATRIX / TAMPILAN CCTV ATAU DOKUMENTASI</span>
                        <?= $sectionTag('OPSIONAL / SCREENSHOT') ?>
                    </div>
                    <label class="border-2 border-dashed border-slate-300 hover:border-emerald-700 bg-white rounded-xl p-6 flex flex-col items-center justify-center text-center cursor-pointer transition-colors group">
                        <div class="w-12 h-12 rounded-full bg-emerald-50 text-[#044d36] flex items-center justify-center mb-2 group-hover:scale-105 transition-transform"><?= icon('cloud-upload', 'text-[28px]') ?></div>
                        <span class="text-sm font-bold text-slate-800">Tarik &amp; letakkan tangkapan layar monitoring CCTV / foto fisik kamera</span>
                        <span class="text-xs text-slate-500 mt-1" id="cctvFileLabel" data-file-label="Format didukung: JPG, PNG, PDF (Maksimal 10MB)">Format didukung: JPG, PNG, PDF (Maksimal 10MB)</span>
                        <span class="mt-3 px-4 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs border border-slate-300">+ Add file</span>
                        <input class="hidden" name="evidence[]" type="file" accept=".jpg,.jpeg,.png,.pdf" multiple data-file-input="cctvFileLabel">
                    </label>
                </div>

                <div class="flex flex-col gap-2 p-4 rounded-xl bg-slate-50 border border-rs-line">
                    <label class="text-sm font-bold text-slate-900 uppercase tracking-[.04em]" for="maintenanceNotes">CATATAN TINDAK LANJUT / SERAH TERIMA SHIFT</label>
                    <textarea class="w-full p-3 rounded-lg border border-slate-300 bg-white text-xs text-slate-800 focus:outline-none focus:border-[#044d36] focus:ring-1 focus:ring-[#044d36]" id="maintenanceNotes" name="handover_note" placeholder="Catatan operasional untuk serah terima shift atau nomor tiket vendor (contoh: Tiket Vendor Hikvision #HK-2026-103 penggantian adaptor port PoE UGD)..." rows="2"></textarea>
                </div>

                <div class="p-4 rounded-xl bg-emerald-50/80 border border-emerald-200 flex items-start gap-3">
                    <input class="mt-1 w-4 h-4 text-[#044d36] rounded-sm border-emerald-300 focus:ring-[#044d36] accent-[#044d36] cursor-pointer" id="complianceConfirm" name="declaration" required type="checkbox">
                    <label class="text-xs font-semibold text-emerald-950 cursor-pointer select-none" for="complianceConfirm">Saya menyatakan bahwa data pemeriksaan CCTV ini telah diverifikasi sesuai kondisi riil perangkat di lapangan dan mematuhi regulasi operasional IT RS Awal Bros Botania.</label>
                </div>

                <div class="pt-4 border-t flex flex-col sm:flex-row items-center justify-between gap-4 border-rs-line">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <?= partial('button', ['label' => 'Kembali ke Portal Terpadu', 'href' => url('/'), 'icon' => 'arrow-left', 'iconClass' => 'text-[16px]', 'class' => 'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors']) ?>
                        <?= partial('button', ['label' => 'Lihat Riwayat & Rekap', 'href' => url('/rekap/cctv'), 'icon' => 'history', 'iconClass' => 'text-[16px]', 'class' => 'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors']) ?>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <?= partial('button', ['label' => 'Clear form', 'type' => 'reset', 'class' => 'px-3 py-2 rounded-lg text-slate-500 hover:text-red-700 hover:bg-red-50 text-xs font-bold transition-colors']) ?>
                        <?= partial('button', ['label' => 'Simpan Draft', 'class' => 'px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold transition-colors', 'attrs' => ['data-draft' => true]]) ?>
                        <?= partial('button', ['label' => 'Kirim Laporan CCTV', 'type' => 'submit', 'icon' => 'send', 'iconClass' => 'text-[18px]', 'class' => 'inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg bg-[#044d36] hover:bg-[#065f46] text-white text-xs font-bold transition-all shadow-rs']) ?>
                    </div>
                </div>
            </form>
        </section>

        <aside class="lg:col-span-4 flex flex-col gap-6">
            <div class="bg-white rounded-2xl border p-5 flex flex-col gap-4 shadow-rs border-rs-line">
                <div class="flex items-center justify-between pb-2 border-b border-rs-line">
                    <div class="flex items-center gap-2"><?= icon('radio', 'text-emerald-800 text-[20px]') ?><h3 class="text-xs font-bold uppercase text-slate-900 tracking-wider">STATUS SISTEM CCTV</h3></div>
                    <?= partial('badge', ['label' => 'LIVE NVR', 'wrap' => false, 'dot' => 'w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse', 'class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-bold uppercase leading-[1.45] tracking-[.04em] whitespace-nowrap']) ?>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border flex items-center justify-between border-rs-line">
                    <div class="flex flex-col">
                        <span class="text-[12px] uppercase font-bold text-slate-500 leading-[1.5] tracking-[.04em]">TOTAL KAMERA AKTIF</span>
                        <div class="flex items-baseline gap-1 mt-1"><span class="text-3xl font-bold text-[#044d36]"><?= e($cameras['active']) ?></span><span class="text-base font-bold text-slate-500">/ <?= e($cameras['total']) ?> Unit</span></div>
                        <span class="text-xs font-semibold text-emerald-700 mt-0.5"><?= e($cameras['online']) ?> Kamera Online Normal</span>
                    </div>
                    <div class="relative w-16 h-16 flex items-center justify-center shrink-0">
                        <svg class="w-16 h-16 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                            <path class="text-slate-200" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="4"></path>
                            <path class="text-emerald-600" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-dasharray="<?= e($cameras['ring']) ?>, 100" stroke-linecap="round" stroke-width="4"></path>
                        </svg>
                        <span class="absolute text-xs font-bold text-slate-800"><?= e($cameras['ring']) ?>%</span>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <?php foreach ($module['issues'] as $issue): $red = $issue['tone'] === 'red'; ?>
                    <div class="flex items-center justify-between p-2.5 rounded-lg <?= $red ? 'bg-red-50 border border-red-100' : 'bg-amber-50 border border-amber-100' ?> text-xs">
                        <div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full <?= $red ? 'bg-red-600' : 'bg-amber-500' ?> shrink-0"></span><span class="font-bold text-slate-800"><?= e($issue['label']) ?></span></div>
                        <?= partial('badge', ['label' => $issue['zone'], 'class' => $red ? 'text-[12px] font-bold text-red-700 bg-red-100/80 px-2 py-0.5 rounded-sm leading-[1.5]' : 'text-[12px] font-medium text-amber-800 bg-amber-100/80 px-2 py-0.5 rounded-sm leading-[1.5]']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="pt-2 border-t flex flex-col gap-1.5 text-xs text-slate-600 border-rs-line"><div class="flex items-center justify-between"></div><div class="flex items-center justify-between"></div><div class="flex items-center justify-between"></div></div>
            </div>

            <div class="bg-white rounded-2xl border p-5 flex flex-col gap-4 shadow-rs border-rs-line">
                <div class="flex items-center gap-2 pb-2 border-b border-rs-line"><?= icon('clipboard-check', 'text-emerald-800 text-[20px]') ?><h3 class="text-xs font-bold uppercase text-slate-900 tracking-wider">JADWAL INSPEKSI CCTV</h3></div>
                <div class="flex flex-col gap-2.5">
                    <?php foreach ($module['shifts'] as $shift): $now = $shift['current']; ?>
                    <div class="flex items-start gap-3 p-3 rounded-xl <?= $now ? 'bg-emerald-50 border border-emerald-200' : 'bg-slate-50 border border-rs-line' ?>">
                        <div class="w-7 h-7 rounded-lg <?= $now ? 'bg-[#044d36] text-white' : 'bg-slate-200 text-slate-600' ?> flex items-center justify-center shrink-0 mt-0.5"><?= icon($shift['icon'], 'text-[16px]') ?></div>
                        <div class="flex flex-col flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold <?= $now ? 'text-slate-900' : 'text-slate-800' ?>"><?= e($shift['name']) ?></span>
                                <span class="<?= $now ? 'text-[11px] font-bold text-emerald-800 bg-emerald-200/70 px-2 py-0.5 rounded-sm leading-[1.45]' : 'text-[11px] text-slate-500 leading-[1.45]' ?>"><?= e($shift['meta']) ?></span>
                            </div>
                            <span class="text-[12px] <?= $now ? 'text-slate-600' : 'text-slate-500' ?> mt-0.5 leading-[1.5]"><?= e($shift['note']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>
    <div class="w-full text-center py-2">
        <span class="text-xs text-slate-500">&copy; <?= e(date('Y')) ?> RS Awal Bros Botania • Sistem Monitoring Fasilitas &amp; CCTV Terpadu</span>
    </div>
</main>
