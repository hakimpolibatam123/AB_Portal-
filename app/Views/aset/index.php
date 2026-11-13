<?php
/**
 * Aset IT menu (AssetController::index): the module's forms as dashboard cards (portal-card styling), each with
 * "Lihat Rekap" to the recap of its own data (like the dashboard's form cards), and the global recap.
 * No statistics here; they are on the recap pages.
 */
$cards = [
    'Formulir Aset' => [
        ['registrasi', 'Registrasi Perangkat', 'Daftarkan perangkat baru: pindai barcode, isi data dan spesifikasi, cetak label.', 'circle-plus', 'teal', 'rekap#perangkat'],
        ['lacak', 'Penempatan & Lacak Perangkat', 'Tentukan atau pindahkan lokasi dan penanggung jawab, lihat riwayat perpindahan dan servis.', 'map-pin', 'sky', 'rekap-perpindahan'],
        ['servis', 'Servis & Pemeliharaan', 'Laporkan perangkat yang rusak atau perlu pemeliharaan.', 'wrench', 'amber', 'rekap-servis'],
        ['serah-terima', 'Serah Terima Perangkat', 'Buat berita acara serah terima dengan tanda tangan, lalu ekspor sebagai PDF.', 'clipboard-check', 'cyan', 'rekap-serah-terima'],
        ['disposal', 'Disposal Perangkat', 'Hapuskan perangkat langsung setelah konfirmasi. Riwayat tetap diarsipkan.', 'archive', 'rose', 'rekap-disposal'],
    ],
    'Rekap & Laporan' => [
        // Riwayat Servis has no card of its own: it opens from "Lihat Rekap" of Servis & Pemeliharaan.
        ['rekap', 'Rekap & Daftar Perangkat', 'Ringkasan aset, daftar semua perangkat dengan filter dan ekspor CSV, serta riwayatnya.', 'chart-column', 'purple', null],
        ['progres-servis', 'Progres Servis', 'Cek progres servis yang berjalan, ubah statusnya, dan lihat riwayatnya.', 'activity', 'orange', null],
    ],
];
?>
<main id="main-content" class="page-shell flex-1 space-y-5 pb-12">
    <section aria-labelledby="aset-title" class="welcome-hero hero-pattern overflow-hidden rounded-2xl border border-[#0d594e] bg-brand-dark p-6 text-white shadow-lg sm:p-8">
        <p class="flex items-center gap-2 text-[11.7px] font-semibold uppercase tracking-wider text-teal-300/90"><span class="h-px w-3 shrink-0 bg-teal-300/90" aria-hidden="true"></span>IT Asset Management</p>
        <h1 id="aset-title" class="mt-1 font-serif text-3xl font-bold leading-tight tracking-tight sm:text-4xl">Aset IT</h1>
        <p class="pt-2 text-sm text-teal-100/90">Siklus perangkat keras: registrasi, penempatan, pelacakan, servis, hingga disposal.</p>
    </section>

    <?php foreach ($cards as $sectionTitle => $items): $sectionId = 'aset-sec-' . md5($sectionTitle); ?>
    <section aria-labelledby="<?= e($sectionId) ?>" class="portal-category space-y-3 pt-2">
        <div class="section-head">
            <h2 id="<?= e($sectionId) ?>" class="flex items-center gap-2 text-[11.7px] font-bold uppercase tracking-wider text-slate-500"><?= e($sectionTitle) ?></h2>
        </div>
        <div class="card-grid--forms grid grid-cols-1 gap-3.5">
            <?php foreach ($items as [$path, $title, $description, $icon, $tone, $rekap]): ?>
            <article class="portal-card portal-card--form">
                <span class="card-icon tone-<?= e($tone) ?>"><?= card_icon($icon) ?></span>
                <h3 class="portal-card-title"><a class="card-link" href="<?= e(url('/aset/' . $path)) ?>"><?= e($title) ?></a></h3>
                <p class="portal-card-description"><?= e($description) ?></p>
                <?php if ($rekap !== null): ?>
                <div class="card-foot">
                    <a class="card-rekap" href="<?= e(url('/aset/' . $rekap)) ?>" aria-label="<?= e('Lihat rekap ' . $title) ?>"><?= card_icon('chart-column', 'size-4') ?>Lihat Rekap</a>
                </div>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</main>
