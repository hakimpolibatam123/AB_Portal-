<?php
/**
 * A page of the Aset IT module (AssetController::page()): the form or the recap is drawn into #as-app by the
 * design's scripts (public/assets/aset), which read and write the records through /aset/data and /aset/perintah.
 * The recap is the global one, or one part of it ($recapView, AssetController::RECAP_VIEWS) with a link to the global.
 * @var string $asetPage  @var string $recapView  @var string $recapLead
 */
?>
<?php if ($asetPage === 'rekap'): ?>
<main id="main-content" class="rs-page rs-page--wide">
    <section class="rs-head">
        <div>
            <p class="rs-head__eyebrow">IT Asset Management</p>
            <h1><?= e($pageTitle) ?></h1>
            <p><?= e($recapLead) ?></p>
        </div>
        <div class="as-head__actions">
            <?php if ($recapView !== ''): ?><a class="rs-btn rs-btn--onhead" href="<?= e(url('/aset/rekap')) ?>"><span data-i="chart-column"></span>Rekap & Daftar Perangkat</a><?php endif; ?>
            <a class="rs-btn rs-btn--onhead" href="<?= e(url('/aset')) ?>"><span data-i="layout-grid"></span>Menu Aset IT</a>
        </div>
    </section>
    <div id="as-app" aria-live="polite"><div class="rs-card"><div class="as-empty-inline">Memuat data aset…</div></div></div>
</main>
<?php else: ?>
<main id="main-content" class="gf-page">
    <div id="as-app" aria-live="polite"><div class="gf"><div class="gf-card gf-head gf-loading"><p>Memuat formulir…</p></div></div></div>
</main>
<?php endif; ?>
<noscript><p class="page-shell py-4">Halaman Aset IT memerlukan JavaScript.</p></noscript>
