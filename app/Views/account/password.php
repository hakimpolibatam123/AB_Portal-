<?php
$fields = '';
foreach ([
    ['currentPassword', 'Password saat ini', 'current-password', null],
    ['newPassword', 'Password baru', 'new-password', null],
    ['confirmPassword', 'Konfirmasi password baru', 'new-password', ['id' => 'confirmError']],
] as [$id, $label, $autocomplete, $error]) {
    $fields .= partial('field', [
        'variant' => 'account', 'label' => $label, 'type' => 'password', 'name' => $id, 'required' => true, 'toggle' => true,
        'attrs' => ['autocomplete' => $autocomplete, 'required' => true], 'error' => $error,
    ]);
}
$foot = partial('button', ['label' => 'Kembali ke Portal Terpadu', 'href' => url('/'), 'class' => 'rs-btn rs-btn--ghost', 'icon' => 'arrow-left'])
    . partial('button', ['label' => 'Simpan Password', 'type' => 'submit', 'class' => 'rs-btn rs-btn--primary', 'icon' => 'circle-check']);
$account = '<div class="rs-person"><span class="rsab-avatar">' . e($profile['initials']) . '</span><span><b>' . e($profile['name']) . '</b><small>' . e($profile['role']) . '</small></span></div>'
    . '<dl class="rs-kv"><dt>Username</dt><dd>' . e($profile['name']) . '</dd><dt>Peran</dt><dd>' . e($profile['role']) . '</dd><dt>Status</dt><dd><span class="rs-pill rs-pill--ok"><i></i>Online</span></dd></dl>';
?>
<main id="main-content" class="rs-page">
    <?= partial('page-header', ['variant' => 'account', 'eyebrow' => 'Akun & Keamanan', 'title' => 'Password', 'description' => 'Ubah password akun yang sedang login.']) ?>
    <div class="rs-grid">
        <?= partial('card', [
            'tag' => 'form', 'attrs' => ['id' => 'passwordForm', 'novalidate' => true, 'data-password-form' => true],
            'title' => 'Ubah Password', 'icon' => 'key-round', 'description' => 'Semua kolom wajib diisi.',
            'body' => $fields, 'foot' => $foot,
        ]) ?>
        <?= partial('card', ['tag' => 'aside', 'title' => 'Akun', 'icon' => 'user', 'body' => $account]) ?>
    </div>
</main>
