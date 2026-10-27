<?php
/**
 * Dashboard card. The title holds the card's link, stretched over the whole card (.card-link), so the
 * ⋯ menu button can sit inside the card. $editable adds the menu; data-card feeds public/assets/js/dashboard.js.
 */
$isLink = $card['kind'] === 'link';
$href = is_string($card['url']) && $card['url'] !== '' ? $card['url'] : null;
if ($isLink && $href !== null && preg_match('#^https?://#i', $href) !== 1) {
    $href = null;
}
$isNew = ($newCardId ?? null) !== null && $card['id'] === $newCardId;
$cardData = [
    'id' => $card['id'], 'kind' => $card['kind'], 'title' => $card['title'], 'description' => $card['description'],
    'icon' => $card['icon'], 'image' => $card['image'], 'url' => $card['url'], 'rekap' => $card['rekap_url'],
    'section' => $card['section_id'], 'hidden' => $card['hidden'],
];
?>
<article<?= attrs([
    'id' => $card['id'] !== null ? 'card-' . $card['id'] : null,
    'class' => cx('portal-card', $card['hidden'] ? 'is-muted' : '', $isNew ? 'is-new' : ''),
    'data-hidden-card' => $card['hidden'],
    'hidden' => $card['hidden'],
    'data-card' => $editable ? json_encode($cardData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : null,
]) ?>>
    <span class="card-flags">
        <?php if (!$isLink): ?><span class="card-badge bg-emerald-50 text-emerald-800">Form</span><?php endif; ?>
        <?php if ($isNew): ?><span class="card-chip card-chip--new" data-new-chip>Baru</span><?php endif; ?>
        <?php if ($card['hidden']): ?><span class="card-chip">Disembunyikan</span><?php endif; ?>
    </span>
    <?php if ($editable): ?>
    <button type="button" class="card-menu-btn" data-card-menu aria-haspopup="menu" aria-expanded="false" aria-label="<?= e('Menu ' . $card['title']) ?>"><?= card_icon('ellipsis', 'size-4') ?></button>
    <?php endif; ?>
    <span class="card-icon tone-<?= e($card['color']) ?>"><?php if ($card['image'] !== null): ?><img class="card-icon-img" src="<?= e($card['image']) ?>" alt="" loading="lazy"><?php else: ?><?= card_icon($card['icon']) ?><?php endif; ?></span>
    <h3 class="portal-card-title">
        <?php if ($href !== null): ?>
        <a class="card-link" href="<?= e($isLink ? $href : url($href)) ?>"<?= $isLink ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= e($card['title']) ?></a>
        <?php else: ?>
        <button type="button" class="card-link" data-module="<?= e((string) $card['id']) ?>" data-title="<?= e($card['title']) ?>" data-type="<?= $isLink ? 'external' : 'internal' ?>" aria-haspopup="dialog"><?= e($card['title']) ?></button>
        <?php endif; ?>
    </h3>
    <p class="portal-card-description"><?= e($card['description']) ?><?php if ($isLink && $href === null): ?><span class="card-note">Alamat belum diatur</span><?php endif; ?></p>
</article>
