<main id="main-content" class="rs-page">
    <?= partial('page-header', ['variant' => 'account', 'eyebrow' => 'Akun & Akses', 'title' => 'Users', 'description' => 'Daftar akun yang terdaftar pada Portal Monitoring IT.']) ?>
    <?= partial('card', [
        'title' => 'Daftar Pengguna', 'icon' => 'users',
        'aside' => '<span class="rs-count">' . e(count($users)) . ' pengguna</span>',
        'content' => partial('table', [
            'wrapClass' => 'rs-table-wrap', 'class' => 'rs-table',
            'columns' => [['label' => 'Pengguna'], ['label' => 'Peran'], ['label' => 'Status']],
            'rows' => $users, 'row' => 'rows/user',
        ]) . '<div class="rs-empty">' . icon('users') . '<b>Belum ada pengguna lain</b><span>Data pengguna lain belum tersedia pada sistem ini.</span></div>',
    ]) ?>
</main>
