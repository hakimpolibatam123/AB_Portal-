<?php
// Module pages retain their content styling; every page uses the shared portal header.
// $portalPage puts another page (the form editor) on the dashboard's styling.
$modulePage = !isset($groups) && empty($portalPage);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal Monitoring IT RS Awal Bros Botania. Akses form internal, monitoring, laporan, dan sistem rumah sakit.">
    <meta name="theme-color" content="#043b33">
    <title><?= e($pageTitle ?? config('app')['name']) ?> | RS Awal Bros Botania</title>
    <link rel="icon" href="data:,">
    <link rel="preload" href="<?= e(asset('fonts/inter-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>?v=<?= e(is_file(BASE_PATH . '/public/assets/css/app.css') ? filemtime(BASE_PATH . '/public/assets/css/app.css') : '1') ?>">
    <script src="<?= e(asset('js/app.js')) ?>?v=<?= e(is_file(BASE_PATH . '/public/assets/js/app.js') ? filemtime(BASE_PATH . '/public/assets/js/app.js') : '1') ?>" defer></script>
    <?php foreach ($pageScripts ?? [] as $script): ?>
    <script src="<?= e(asset($script)) ?>?v=<?= e(filemtime(BASE_PATH . '/public/assets/' . $script)) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body class="flex min-h-screen flex-col<?= $modulePage ? ' module-page' : ' portal-page' ?>">
    <a href="#main-content" class="fixed left-4 top-4 z-50 -translate-y-24 rounded-lg bg-teal-800 px-4 py-3 text-sm text-white focus:translate-y-0">Langsung ke konten</a>
    <?php require __DIR__ . '/header.php'; ?>
    <?php if (!empty($breadcrumbs) || !empty($backToDashboard)): ?>
    <div class="breadcrumb-shell page-shell py-5">
        <?php require BASE_PATH . '/app/Views/components/breadcrumbs.php'; ?>
    </div>
    <?php endif; ?>
    <?= $content ?>
    <?php if ($modulePage): ?>
    <?= partial('design-footer', ['class' => 'mt-auto text-center py-8']) ?>
    <?php else: ?>
    <?php require __DIR__ . '/footer.php'; ?>
    <?php endif; ?>
    <?php require BASE_PATH . '/app/Views/components/portal-dialog.php'; ?>
</body>
</html>
