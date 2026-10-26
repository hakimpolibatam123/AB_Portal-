<?php
/**
 * Shared layout: navbar with live clock, optional breadcrumb bar, page content and footer.
 * Optional view data: $page, $bodyClass, $documentClass, $htmlClass, $navActive, $brandSubtitle, $navStatus,
 * $crumbbar (breadcrumb data shown in the white bar under the navbar), $footer, $modal.
 */
?>
<!doctype html>
<html lang="id"<?= attrs(['class' => $htmlClass ?? null]) ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal Monitoring IT RS Awal Bros Botania. Form monitoring harian, rekap, dan sistem rumah sakit.">
    <meta name="theme-color" content="#ffffff">
    <title><?= e($pageTitle ?? config('app')['name']) ?> | RS Awal Bros Botania</title>
    <link rel="icon" href="data:,">
    <link rel="preload" href="<?= e(asset('fonts/ibm-plex-sans-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset_v('css/app.css')) ?>">
    <script src="<?= e(asset_v('js/app.js')) ?>" defer></script>
</head>
<body<?= attrs(['class' => $documentClass ?? null]) ?>>
    <div class="<?= e(cx('rs-body', $bodyClass ?? '')) ?>" data-page="<?= e($page ?? '') ?>">
        <a href="#main-content" class="fixed left-4 top-4 z-[90] -translate-y-24 rounded-lg bg-rs-teal-700 px-4 py-3 text-sm text-white focus:translate-y-0">Langsung ke konten</a>
        <?= partial('navbar', ['active' => $navActive ?? 'portal', 'subtitle' => $brandSubtitle ?? 'RS Awal Bros Botania', 'status' => $navStatus ?? 'Online']) ?>
        <?php if (!empty($crumbbar)): ?>
        <div class="rsab-crumbbar"><div><?= partial('breadcrumb', $crumbbar) ?></div></div>
        <?php endif; ?>
        <?= $content ?>
        <?= partial('footer', $footer ?? []) ?>
    </div>
    <?php if (!empty($modal)): ?><?= partial('submit-modal', $modal) ?><?php endif; ?>
    <?= partial('toast', ['flash' => $flash ?? null]) ?>
</body>
</html>
