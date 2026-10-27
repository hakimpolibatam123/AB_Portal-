<?php
/**
 * Dashboard card. The title holds the card's link, stretched over the whole card (.card-link), so the
 * ⋯ menu and the footer's "Lihat Rekap" link can sit inside the card without nested links.
 * Monitoring Harian cards (builtin/form) get a schedule badge and a footer: "Lihat Rekap" + today's status. Cards that are
 * not due today look muted, but their "Lihat Rekap" stays active. $editable adds the ⋯ menu;
 * data-card feeds public/assets/js/dashboard.js.
 */
$isLink = $card['kind'] === 'link';
$href = is_string($card['url']) && $card['url'] !== '' ? $card['url'] : null;
if ($isLink && $href !== null && preg_match('#^https?://#i', $href) !== 1) {
    $href = null;
}
$status = $isLink ? null : ($card['status'] ?? null);
$rekap = !$isLink && is_string($card['rekap_url'] ?? null) && $card['has_data'] ? url($card['rekap_url']) : null;
$offSchedule = $status !== null && !$status['due'];
$isNew = ($newCardId ?? null) !== null && $card['id'] === $newCardId;
$hasMenu = $editable && (!$isLink || !empty($linkMenus));
$cardData = [
    'id' => $card['id'], 'kind' => $card['kind'], 'builtin' => $card['builtin'] ?? false, 'title' => $card['title'],
    'description' => $card['description'], 'icon' => $card['icon'], 'image' => $card['image'], 'url' => $card['url'],
    'rekap' => $rekap, 'section' => $card['section_id'], 'hidden' => $card['hidden'],
];
?>
<article<?= attrs([
    'id' => $card['id'] !== null ? 'card-' . $card['id'] : null,
    'class' => cx('portal-card', $isLink ? '' : 'portal-card--form', $card['hidden'] ? 'is-muted' : '', $offSchedule ? 'is-off-schedule' : '', $isNew ? 'is-new' : ''),
    'data-hidden-card' => $card['hidden'],
    'hidden' => $card['hidden'],
    'data-card' => $editable ? json_encode($cardData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) : null,
]) ?>>
    <span class="card-flags">
        <?php if (!$isLink && !empty($card['label'])): ?><span class="card-badge card-badge--<?= e($card['label']['tone']) ?>"><?= e($card['label']['text']) ?></span><?php elseif (!$isLink): ?><span class="card-badge bg-emerald-50 text-emerald-800">Form</span><?php endif; ?>
        <?php if ($isNew): ?><span class="card-chip card-chip--new" data-new-chip>Baru</span><?php endif; ?>
        <?php if ($card['hidden']): ?><span class="card-chip">Disembunyikan</span><?php endif; ?>
    </span>
    <?php if ($hasMenu): ?>
    <button type="button" class="card-menu-btn" data-card-menu aria-haspopup="menu" aria-expanded="false" aria-label="<?= e('Menu ' . $card['title']) ?>"><?= card_icon('ellipsis', 'size-4') ?></button>
    <?php endif; ?>
    <span class="card-icon tone-<?= e($card['color']) ?>"><?php if ($card['image'] !== null): ?><img class="card-icon-img" src="<?= e($card['image']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php else: ?><?= card_icon($card['icon']) ?><?php endif; ?></span>
    <h3 class="portal-card-title">
        <?php if ($href !== null): ?>
        <a class="card-link" href="<?= e($isLink ? $href : url($href)) ?>"<?= $isLink ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><?= e($card['title']) ?></a>
        <?php else: ?>
        <button type="button" class="card-link" data-module="<?= e((string) $card['id']) ?>" data-title="<?= e($card['title']) ?>" data-type="<?= $isLink ? 'external' : 'internal' ?>" aria-haspopup="dialog"><?= e($card['title']) ?></button>
        <?php endif; ?>
    </h3>
    <p class="portal-card-description"><?= e($card['description']) ?><?php if ($isLink && $href === null): ?><span class="card-note">Alamat belum diatur</span><?php endif; ?></p>
    <?php if (!$isLink): ?>
    <div class="card-foot">
        <?php if ($rekap !== null): ?>
        <a class="card-rekap" href="<?= e($rekap) ?>" aria-label="<?= e('Lihat rekap ' . $card['title']) ?>"><?= card_icon('chart-column', 'size-4') ?>Lihat Rekap</a>
        <?php else: ?>
        <span class="card-tip">
            <button type="button" class="card-rekap is-off" aria-disabled="true" data-no-rekap aria-label="<?= e('Lihat rekap ' . $card['title'] . ' (belum ada laporan)') ?>" aria-describedby="tip-<?= e((string) $card['id']) ?>"><?= card_icon('chart-column', 'size-4') ?>Lihat Rekap</button>
            <span class="card-tip__box" role="tooltip" id="tip-<?= e((string) $card['id']) ?>">Belum ada laporan</span>
        </span>
        <?php endif; ?>
        <?php if ($status !== null): ?>
        <span class="card-status card-status--<?= e($status['tone']) ?>"><?php if ($status['tone'] === 'ok'): ?><?= card_icon('check', 'size-3.5') ?><?php endif; ?><?= e($status['text']) ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</article>
