<?php
/**
 * Header card of a Google-Forms style form page (resources/css/gform.css).
 * @var string $title
 * Optional: $titleId, $eyebrow, $description, $meta (list of ['label' => text, 'value' => HTML, 'labelFor' => id]),
 *           $required (HTML of the form's "Wajib Diisi" note), $rekap (URL of the module's rekap page, shown as an
 *           outlined "Lihat Rekap" button).
 */
?>
<section class="gf-card gf-card--header"<?= attrs(['aria-labelledby' => $titleId ?? null]) ?>>
    <?php if (!empty($eyebrow)): ?><p class="gf-eyebrow"><?= e($eyebrow) ?></p><?php endif; ?>
    <h1<?= attrs(['class' => 'gf-title', 'id' => $titleId ?? null]) ?>><?= e($title) ?></h1>
    <?php if (!empty($description)): ?><p class="gf-desc"><?= e($description) ?></p><?php endif; ?>
    <?php if (!empty($meta)): ?>
    <div class="gf-meta">
        <?php foreach ($meta as $item): ?>
        <div class="min-w-0">
            <?php if (isset($item['labelFor'])): ?><label class="gf-meta-label" for="<?= e($item['labelFor']) ?>"><?= e($item['label']) ?></label><?php else: ?><span class="gf-meta-label"><?= e($item['label']) ?></span><?php endif; ?>
            <div class="gf-meta-value"><?= $item['value'] ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($required) || !empty($rekap)): ?>
    <div class="gf-header-foot">
        <?= $required ?? '' ?>
        <?php if (!empty($rekap)): ?><div class="gf-links"><a class="gf-rekap" href="<?= e($rekap) ?>" aria-label="<?= e('Lihat rekap ' . $title) ?>"><?= card_icon('chart-column', 'gf-rekap__icon') ?>Lihat Rekap</a></div><?php endif; ?>
    </div>
    <?php endif; ?>
</section>
