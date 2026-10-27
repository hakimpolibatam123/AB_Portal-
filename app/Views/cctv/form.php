<?php
$radioOption = static fn (string $name, string $value, bool $checked, string $body): string =>
    '<label class="gf-option">'
    . '<input' . attrs(['checked' => $checked, 'name' => $name, 'type' => 'radio', 'value' => $value]) . '>'
    . '<span class="gf-option-text">' . $body . '</span></label>';
?>
<main id="main-content" class="gf-page">
    <section class="contents" aria-label="Form pemeriksaan CCTV">
        <form class="gf-form" id="cctvMonitoringForm" method="post" action="<?= e(url('/rekap/cctv')) ?>" enctype="multipart/form-data" data-module-form novalidate>
            <?= partial('gf-header', [
                'eyebrow' => 'RS AWAL BROS GROUP • IT BOTANIA',
                'title' => 'Form Monitoring CCTV & Kamera Pengawas',
                'description' => 'Pencatatan harian status kamera pengawas, kelayakan IP, dan kesesuaian sinkronisasi jam CCTV di lingkungan RS Awal Bros Botania.',
                'meta' => [
                    ['label' => 'PERIODE LAPORAN', 'value' => 'Inspeksi Harian (Shift Pagi)'],
                    ['label' => 'Akun Verifikator IT', 'value' => '<span>' . e(config('dashboard')['account']['email']) . '</span>'
                        . partial('badge', ['label' => 'Verified', 'icon' => 'circle-check', 'wrap' => false, 'class' => 'gf-pill'])],
                    ['label' => 'Waktu & Tanggal Pemeriksaan *', 'value' => '<span>' . e($module['inspected_at']) . '</span>'
                        . '<input type="hidden" name="inspected_at" value="' . e($module['inspected_at']) . '">'],
                ],
                'required' => partial('badge', ['label' => '* WAJIB DIISI', 'icon' => 'circle-alert', 'wrap' => false, 'class' => 'gf-required-note']),
                'rekap' => url('/rekap/cctv'),
            ]) ?>

            <?= partial('gf-section', ['number' => 1, 'title' => 'Data Pemeriksaan CCTV Harian', 'description' => 'Formulir Log Operasional Standar NOC IT Botania']) ?>

            <div class="gf-card">
                <div class="gf-q-head">
                    <label class="gf-q-title" for="cctvDamagedInput">ISI CCTV YANG RUSAK DAN IP-NYA <span class="gf-req">*</span></label>
                    <span class="gf-pill">Log Unit &amp; IP</span>
                </div>
                <p class="gf-help">Masukkan nama titik kamera, nomor IP, dan kendala (contoh: <em>CCTV-UGD-02 / 192.168.10.42 - Offline</em>). Ketik <strong>"NIHIL"</strong> jika semua kamera normal.</p>
                <div class="gf-body">
                    <textarea class="gf-input" id="cctvDamagedInput" name="damaged_cameras" required data-char-count="charIndicator" placeholder="Contoh:&#10;1. CCTV-UGD-02 (IP: 192.168.10.42) - Offline / Putus Jaringan&#10;2. CCTV-PARKIR-TIMUR (IP: 192.168.10.112) - Kamera buram terkena embun" rows="4"></textarea>
                    <div class="gf-meta-line">
                        <span class="inline-flex items-center gap-1"><?= design_icon('info') ?>Format: [Nama Titik / Channel] / [Alamat IP] - [Gejala Kerusakan]</span>
                        <span id="charIndicator" aria-live="polite">0 Karakter</span>
                    </div>
                </div>
            </div>

            <div class="gf-card">
                <div class="gf-q-head">
                    <span class="gf-q-title" id="ntpLabel">KESESUAIAN JAM CCTV HARIAN <span class="gf-req">*</span></span>
                    <span class="gf-pill">NTP Synchronize</span>
                </div>
                <p class="gf-help">Pastikan jam internal NVR/DVR dan tampilan kamera CCTV tersinkronisasi presisi dengan master NTP Server RS Botania</p>
                <div class="gf-body gf-options" role="radiogroup" aria-labelledby="ntpLabel">
                    <?= $radioOption('ntp_status', 'sesuai', true,
                        '<span class="gf-option-label">Sesuai' . partial('badge', ['label' => 'Optimal', 'class' => 'gf-pill']) . '</span>'
                        . '<span class="gf-option-note">Sinkron NTP Server RS 172.24.10.2 / Toleransi &lt; 5 detik pada seluruh rekaman channel.</span>') ?>
                    <?= $radioOption('ntp_status', 'tidak_sesuai', false,
                        '<span class="gf-option-label">Tidak Sesuai / Selisih Waktu' . partial('badge', ['label' => 'Perlu Kalibrasi', 'class' => 'gf-pill gf-pill--red']) . '</span>'
                        . '<span class="gf-option-note">Perlu kalibrasi ulang waktu NVR karena selisih &gt; 1 menit atau offset time zone berbeda.</span>') ?>
                    <label class="gf-option">
                        <input name="ntp_status" type="radio" value="other">
                        <span class="gf-option-text">
                            <span class="gf-option-label">Other / Keterangan Khusus</span>
                            <span class="gf-option-other hidden" id="otherFieldWrapper" data-reveal-on="ntp_status=other">
                                <input class="gf-input" name="ntp_note" placeholder="Tuliskan keterangan detail deviasi waktu atau unit yang bermasalah..." type="text" aria-label="Keterangan sinkronisasi jam">
                            </span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="gf-card">
                <div class="gf-q-head">
                    <span class="gf-q-title" id="nvrLabel">STATUS &amp; HEALTH CHECK NVR / STORAGE <span class="gf-req">*</span></span>
                    <span class="gf-pill">RAID / HDD Integrity</span>
                </div>
                <p class="gf-help">Kapasitas Penyimpanan dan ketahanan retensi data video rekaman (Standar Akreditasi KARS minimal 30 hari).</p>
                <div class="gf-body gf-options" role="radiogroup" aria-labelledby="nvrLabel">
                    <?= $radioOption('storage_health', 'normal', true,
                        '<span class="gf-option-label">Normal (Retensi &gt; 30 Hari)</span><span class="gf-option-note">Sisa ruang aman (&gt; 20%), status hard disk OK, auto-overwrite berjalan.</span>') ?>
                    <?= $radioOption('storage_health', 'kritis', false,
                        '<span class="gf-option-label">Kapasitas Kritis (&gt; 90%)</span><span class="gf-option-note">Penyimpanan menipis, peringatan disk degraded atau resiko rekaman terpotong.</span>') ?>
                </div>
            </div>

            <div class="gf-card">
                <div class="gf-q-head">
                    <span class="gf-q-title">UNGGAH BUKTI MATRIX / TAMPILAN CCTV ATAU DOKUMENTASI</span>
                    <span class="gf-pill">OPSIONAL / SCREENSHOT</span>
                </div>
                <label class="gf-body gf-drop">
                    <span class="gf-drop-icon"><?= design_icon('cloud-upload') ?></span>
                    <span class="gf-drop-text">Tarik &amp; letakkan tangkapan layar monitoring CCTV / foto fisik kamera</span>
                    <span class="gf-drop-note" id="cctvFileLabel" data-file-label="Format didukung: JPG, PNG, PDF (Maksimal 10MB)">Format didukung: JPG, PNG, PDF (Maksimal 10MB)</span>
                    <span class="gf-file-btn">+ Add file</span>
                    <input class="hidden" name="evidence[]" type="file" accept=".jpg,.jpeg,.png,.pdf" multiple data-file-input="cctvFileLabel">
                </label>
            </div>

            <div class="gf-card">
                <label class="gf-q-title" for="maintenanceNotes">CATATAN TINDAK LANJUT / SERAH TERIMA SHIFT</label>
                <div class="gf-body"><textarea class="gf-input" id="maintenanceNotes" name="handover_note" placeholder="Catatan operasional untuk serah terima shift atau nomor tiket vendor (contoh: Tiket Vendor Hikvision #HK-2026-103 penggantian adaptor port PoE UGD)..." rows="2"></textarea></div>
            </div>

            <div class="gf-card">
                <div class="gf-confirm">
                    <input id="complianceConfirm" name="declaration" required type="checkbox">
                    <label class="gf-confirm-label" for="complianceConfirm">Saya menyatakan bahwa data pemeriksaan CCTV ini telah diverifikasi sesuai kondisi riil perangkat di lapangan dan mematuhi regulasi operasional IT RS Awal Bros Botania.</label>
                </div>
            </div>

            <div class="gf-actions">
                <a class="gf-btn gf-btn--outline" href="<?= e(url('/')) ?>"><?= design_icon('arrow-left') ?><span>Kembali ke Portal Terpadu</span></a>
                <div class="gf-actions-end">
                    <button class="gf-btn gf-btn--text" type="reset">Clear form</button>
                    <button class="gf-btn gf-btn--primary" type="submit"><?= design_icon('send') ?><span>Kirim Laporan CCTV</span></button>
                </div>
            </div>
        </form>
    </section>
    <p class="gf-footnote">&copy; <?= e(date('Y')) ?> RS Awal Bros Botania • Sistem Monitoring Fasilitas &amp; CCTV Terpadu</p>
</main>
<script src="<?= e(asset_v('js/gform.js')) ?>" defer></script>
