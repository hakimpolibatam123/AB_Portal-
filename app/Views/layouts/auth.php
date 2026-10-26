<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Masuk ke Portal Web RS Awal Bros Botania.">
    <meta name="theme-color" content="#ffffff">
    <title><?= e($pageTitle ?? config('app')['name']) ?> | RS Awal Bros Botania</title>
    <link rel="icon" href="data:,">
    <link rel="preload" href="<?= e(asset('fonts/ibm-plex-sans-latin-400-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(asset('images/login-hero.webp')) ?>" as="image" type="image/webp" media="(min-width: 1024px)">
    <link rel="stylesheet" href="<?= e(asset_v('css/app.css')) ?>">
    <script src="<?= e(asset_v('js/login.js')) ?>" defer></script>
</head>
<body>
    <div class="rs-body min-h-screen antialiased selection:bg-blue-100 selection:text-blue-700 rs-forms" data-page="login">
        <?= $content ?>
    </div>
</body>
</html>
