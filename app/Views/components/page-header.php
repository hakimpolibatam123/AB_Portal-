<?php
/**
 * Page header.
 * variant "account": the compact teal header of the account pages ($eyebrow, $title, $description).
 * variant "module":  the teal header of the module pages. Pass $class for spacing/shape,
 *                    $eyebrow as rendered HTML (usually a badge partial), $title, $description,
 *                    $aside as rendered HTML for the right-hand side, and optional
 *                    $innerClass, $introClass, $titleClass, $descriptionClass, $tag.
 */
$variant = $variant ?? 'module';
?>
<?php if ($variant === 'account'): ?>
<section class="rs-head">
    <div>
        <p class="rs-head__eyebrow"><?= e($eyebrow) ?></p>
        <h1><?= e($title) ?></h1>
        <p><?= e($description) ?></p>
    </div>
</section>
<?php else: $tag = $tag ?? 'div'; ?>
<<?= $tag ?> class="<?= e(cx($class ?? '', 'rsab-hero')) ?>">
    <div class="<?= e(cx('relative z-10', $innerClass ?? 'flex flex-col lg:flex-row lg:items-center justify-between gap-6')) ?>">
        <div<?= attrs(['class' => $introClass ?? null]) ?>>
            <?= $eyebrow ?? '' ?>
            <h1<?= attrs(['class' => $titleClass ?? null]) ?>><?= $titleHtml ?? e($title) ?></h1>
            <p<?= attrs(['class' => $descriptionClass ?? null]) ?>><?= e($description) ?></p>
        </div>
        <?= $aside ?? '' ?>
    </div>
</<?= $tag ?>>
<?php endif; ?>
