<?php $profile = current_profile(); ?>
<header class="portal-navbar sticky top-0 z-40 border-b border-slate-200 bg-white">
    <div class="portal-header page-shell flex flex-wrap items-center justify-between gap-4 py-3">
        <a href="<?= e(url('/')) ?>" class="flex min-w-0 flex-wrap items-center gap-4" aria-label="Beranda Portal RS Awal Bros Botania">
            <?php require BASE_PATH . '/app/Views/components/brand.php'; ?>
            <span class="flex flex-col border-l border-slate-300 pl-4">
                <span class="text-[11.7px] font-bold leading-tight text-slate-800">Portal Monitoring IT</span>
                <span class="text-[10.8px] font-medium text-slate-500">IT Division</span>
            </span>
        </a>
        <div class="flex w-full flex-wrap items-center gap-2.5 sm:w-auto">
            <time id="portal-clock" class="inline-flex items-center rounded border border-slate-200 bg-slate-100/90 px-3 py-1.5 text-[11.7px] font-medium text-slate-600" datetime="<?= e(date(DATE_ATOM)) ?>">
                <span data-clock-text><?= e(date('d/m/Y H:i:s')) ?> WIB</span>
            </time>
            <?php if (\App\Core\Auth::check()): $bell = \App\Services\Attention::notifications(); ?>
            <div class="notif-menu" data-notif-menu>
                <button type="button" class="notif-menu__button" aria-expanded="false" aria-controls="notif-panel" data-notif-toggle
                    data-label="<?= e('Notifikasi perlu perhatian, ' . \App\Services\Attention::DAYS . ' hari terakhir') ?>"
                    aria-label="<?= e('Notifikasi perlu perhatian, ' . \App\Services\Attention::DAYS . ' hari terakhir: ' . $bell['unread'] . ' belum dilihat') ?>">
                    <?= card_icon('bell', 'size-4') ?>
                    <span class="notif-menu__badge" aria-hidden="true" data-notif-badge<?= $bell['unread'] > 0 ? '' : ' hidden' ?>><?= $bell['unread'] > 9 ? '9+' : (int) $bell['unread'] ?></span>
                </button>
                <div class="notif-menu__panel" id="notif-panel" hidden data-seen-url="<?= e(url('/perlu-perhatian/dilihat')) ?>">
                    <div class="notif-menu__head">
                        <div><b>Perlu perhatian</b><span><?= \App\Services\Attention::DAYS ?> hari terakhir</span></div>
                        <button type="button" class="notif-menu__seen-all" data-notif-seen-all<?= $bell['unread'] > 0 ? '' : ' hidden' ?>>Tandai semua dilihat</button>
                    </div>
                    <?php if ($bell['items'] === []): ?>
                    <p class="notif-menu__empty">Tidak ada laporan yang perlu perhatian.</p>
                    <?php else: ?>
                    <ul class="notif-menu__list">
                        <?php foreach ($bell['items'] as $item): ?>
                        <?php $isService = ($item['kind'] ?? '') === 'service'; // Aset IT service waiting for spare parts ?>
                        <li><a href="<?= e($item['url']) ?>" <?= $isService ? 'data-notif-svc="' . e($item['id']) . '"' : 'data-notif-id="' . (int) $item['id'] . '"' ?><?= $item['read'] ? ' class="is-read"' : '' ?>>
                            <span class="notif-menu__form"><?= e($item['form']) ?></span>
                            <span class="notif-menu__meta"><?= e($item['entry_no']) ?> · <?= e($item['when']) ?></span>
                            <?php if ($item['reasons'] !== []): ?><span class="notif-menu__reason"><?= e(implode('; ', $item['reasons'])) ?></span><?php endif; ?>
                        </a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <a class="notif-menu__all" href="<?= e(url('/perlu-perhatian')) ?>" target="_blank" rel="noopener">Lihat semua data<?= card_icon('external-link', 'size-4') ?></a>
                </div>
            </div>
            <?php endif; ?>
            <?php if (\App\Core\Auth::check()): ?>
            <button type="button" class="side-menu__open" aria-haspopup="dialog" aria-controls="side-menu" aria-label="Buka menu" data-side-menu-open><?= card_icon('menu', 'size-5') ?></button>
            <?php endif; ?>
        </div>
    </div>
</header>
<?php if (\App\Core\Auth::check()):
    // Side menu (hamburger): the signed-in account, the portal's pages (the page shown is marked) and Logout.
    $navPath = \App\Services\NavMenu::currentPath();
    $navItem = static fn (string $path, string $iconName, string $label): string => '<a class="side-menu__link' . ($navPath === $path ? ' is-current' : '') . '" href="' . e(url($path)) . '"' . ($navPath === $path ? ' aria-current="page"' : '') . '>' . card_icon($iconName, 'size-4') . '<span>' . e($label) . '</span></a>';
?>
<dialog id="side-menu" class="side-menu" aria-label="Menu portal">
    <div class="side-menu__head">
        <a href="<?= e(url('/')) ?>" class="side-menu__brand">RS AWAL BROS</a>
        <button type="button" class="side-menu__close" aria-label="Tutup menu" data-side-menu-close><?= card_icon('x', 'size-5') ?></button>
    </div>
    <div class="side-menu__account">
        <span class="side-menu__avatar" aria-hidden="true"><?= e($profile['initials']) ?></span>
        <span class="side-menu__who"><b><?= e($profile['name']) ?></b><?php if ($profile['username'] !== ''): ?><small><?= e('@' . $profile['username']) ?></small><?php endif; ?></span>
    </div>
    <nav class="side-menu__nav" aria-label="Halaman portal">
        <?= $navItem('/', 'layout-dashboard', 'Portal Monitoring Terpadu') ?>
        <?php $onAset = $navPath === '/aset' || str_starts_with($navPath, '/aset/'); // Aset IT: one link to its menu page, marked on every Aset page. ?>
        <a class="side-menu__link<?= $onAset ? ' is-current' : '' ?>" href="<?= e(url('/aset')) ?>"<?= $navPath === '/aset' ? ' aria-current="page"' : '' ?>><?= card_icon('hard-drive', 'size-4') ?><span>Aset IT</span></a>
        <?php $onClearance = $navPath === '/clearance' || str_starts_with($navPath, '/clearance/'); // Form Clearance: marked on its list and form. ?>
        <a class="side-menu__link<?= $onClearance ? ' is-current' : '' ?>" href="<?= e(url('/clearance')) ?>"<?= $navPath === '/clearance' ? ' aria-current="page"' : '' ?>><?= card_icon('user-check', 'size-4') ?><span>Form Clearance</span></a>
        <?= $navItem('/perlu-perhatian', 'triangle-alert', 'Perlu perhatian') ?>
        <?= $navItem('/riwayat-aktivitas', 'history', 'Riwayat aktivitas') ?>
        <?= $navItem('/pengaturan-email', 'mail', 'Pengaturan Email') ?>
        <?= $navItem('/password', 'lock', 'Ubah password') ?>
    </nav>
    <form class="side-menu__foot" method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button type="submit" class="side-menu__logout"><?= card_icon('log-out', 'size-4') ?>Logout</button></form>
</dialog>
<?php endif; ?>
