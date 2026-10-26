<?php
/**
 * Success dialog shown after a form is sent; its button submits the form to the rekap page.
 * @var string $formName  e.g. "Data Suhu Server"
 * @var string $formId    id of the form to submit
 */
?>
<div class="rs-modal" id="submit-modal" role="dialog" aria-modal="true" aria-labelledby="submit-modal-title" aria-describedby="submit-modal-desc" hidden>
    <div class="rs-modal__box">
        <div class="rs-modal__icon"><svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
        <h3 id="submit-modal-title">Laporan Berhasil Terkirim!</h3>
        <p id="submit-modal-desc">Data <?= e($formName) ?> telah berhasil diverifikasi dan tersimpan ke rekapitulasi audit.</p>
        <div class="pt-2 flex justify-center">
            <button type="submit" form="<?= e($formId) ?>" class="rs-modal__btn" data-submit-confirm>Buka Rekapitulasi Laporan</button>
        </div>
    </div>
</div>
