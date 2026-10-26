<?php
/** @var string $active  portal | password | users */
/** @var string $subtitle  text under "Portal Monitoring IT" */
/** @var string $status  label of the connection pill */
$profile = config('dashboard')['profile'];
$links = [
    'portal' => ['label' => 'Portal Monitoring Terpadu', 'href' => url('/'), 'icon' => 'layout-grid'],
    'password' => ['label' => 'Password', 'href' => url('/password'), 'icon' => 'key-round'],
    'users' => ['label' => 'Users', 'href' => url('/users'), 'icon' => 'users'],
];
?>
<header class="rsab-nav" role="banner">
    <div class="rsab-nav__inner">
        <a class="rsab-brand" href="<?= e(url('/')) ?>">
            <span class="rsab-brand__mark">RS AWAL BROS</span>
            <span class="rsab-brand__sep"></span>
            <span class="rsab-brand__text"><b>Portal Monitoring IT</b><small><?= e($subtitle) ?></small></span>
        </a>
        <nav aria-label="Navigasi utama" class="rsab-nav__links" id="rsab-menu">
            <?php foreach ($links as $key => $link): ?>
            <a<?= attrs(['class' => $key === $active ? 'is-active' : null, 'href' => $link['href'], 'aria-current' => $key === $active ? 'page' : null]) ?>><?= icon($link['icon']) ?><span><?= e($link['label']) ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="rsab-nav__meta">
            <span class="rsab-clock"><?= icon('clock') ?><span data-clock><?= e(clock_wib()) ?></span></span>
            <span class="rsab-status"><i></i><?= e($status) ?></span>
            <span class="rsab-divider"></span>
            <span class="rsab-user">
                <span class="rsab-avatar"><?= e($profile['initials']) ?></span>
                <span class="rsab-user__text"><b><?= e($profile['name']) ?></b><small><?= e($profile['role']) ?></small></span>
            </span>
            <a class="rsab-logout" href="<?= e(url('/login')) ?>" title="Logout"><?= icon('log-out') ?><span>Logout</span></a>
            <button aria-controls="rsab-menu" aria-expanded="false" aria-label="Menu" class="rsab-menu-btn" type="button"><?= icon('menu') ?></button>
        </div>
    </div>
</header>
