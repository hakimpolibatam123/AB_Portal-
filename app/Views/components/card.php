<?php
/**
 * Surface card with optional header, body and footer.
 * Optional: $tag (section|form|aside|div), $class, $attrs, $title, $icon, $description,
 *           $aside (HTML at the right of the header), $body (HTML inside the padded body),
 *           $content (HTML placed directly in the card, e.g. a table), $foot (HTML).
 */
$tag = $tag ?? 'section';
?>
<<?= $tag ?><?= attrs(['class' => cx('rs-card', $class ?? '')] + ($attrs ?? [])) ?>>
<?php if (isset($title)): ?>
    <div class="rs-card__head">
        <div>
            <h2><?= isset($icon) ? icon($icon) : '' ?><?= e($title) ?></h2>
            <?php if (isset($description)): ?><p><?= e($description) ?></p><?php endif; ?>
        </div>
        <?= $aside ?? '' ?>
    </div>
<?php endif; ?>
<?php if (isset($body)): ?>
    <div class="rs-card__body"><?= $body ?></div>
<?php endif; ?>
    <?= $content ?? '' ?>
<?php if (isset($foot)): ?>
    <div class="rs-card__foot"><?= $foot ?></div>
<?php endif; ?>
</<?= $tag ?>>
