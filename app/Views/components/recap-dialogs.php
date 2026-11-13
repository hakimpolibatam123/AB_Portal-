<?php
/**
 * Dialogs of the recap page (form-dinamis/rekap-form.php; public/assets/js/rekap.js): the detail of an
 * entry with its history, read only. A sent report stays as it was (no Ubah or Hapus), like a disposal record.
 */
?>
<dialog id="fr-detail" class="dash-dialog fr-detail" aria-labelledby="fr-detail-title">
    <div class="dash-dialog__head">
        <div>
            <h2 id="fr-detail-title" class="dash-dialog__title" data-detail-title></h2>
            <p class="fr-detail__sub" data-detail-sub></p>
        </div>
        <button type="button" class="fe-icon" data-detail-close aria-label="Tutup"><?= card_icon('x', 'size-5') ?></button>
    </div>
    <div class="dash-dialog__body fr-detail__body" data-detail-body></div>
    <div class="dash-dialog__foot fr-detail__foot">
        <button type="button" class="dash-btn dash-btn--primary" data-detail-close>Tutup</button>
    </div>
</dialog>
