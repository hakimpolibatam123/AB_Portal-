<?php
/**
 * Form Clearance (ClearanceController): the list or one form, drawn into #as-app by public/assets/clearance/clearance.js
 * from the JSON below (mode list|form, the records, the accesses Unit IT checks).
 * @var array $clearance
 */
?>
<?php if ($clearance['mode'] === 'list'): ?>
<main id="main-content" class="rs-page rs-page--wide">
    <section class="rs-head">
        <div>
            <p class="rs-head__eyebrow">Sumber Daya Manusia</p>
            <h1>Form Clearance</h1>
            <p>Clearance karyawan yang mengundurkan diri: isian Unit IT, tanda tangan Ka. Unit IT, dan rekap ekspor PDF / Excel.</p>
        </div>
    </section>
    <div id="as-app" aria-live="polite"><div class="rs-card"><div class="as-empty-inline">Memuat…</div></div></div>
</main>
<?php else: ?>
<main id="main-content" class="gf-page">
    <div id="as-app" aria-live="polite"><div class="gf"><div class="gf-card gf-head gf-loading"><p>Memuat formulir…</p></div></div></div>
</main>
<?php endif; ?>
<script type="application/json" id="clearance-data"><?= json_encode($clearance, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<noscript><p class="page-shell py-4">Halaman ini memerlukan JavaScript.</p></noscript>
