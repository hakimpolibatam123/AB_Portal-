<main id="main-content" class="rs-page">
    <?= partial('page-header', ['variant' => 'account', 'eyebrow' => '404', 'title' => 'Halaman tidak ditemukan', 'description' => 'Alamat yang Anda buka belum tersedia di portal ini.']) ?>
    <div><?= partial('button', ['label' => 'Kembali ke Portal Terpadu', 'href' => url('/'), 'class' => 'rs-btn rs-btn--primary', 'icon' => 'arrow-left']) ?></div>
</main>
