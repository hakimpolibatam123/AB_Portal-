<?php
/**
 * Small label: status pills, chips and tags.
 * @var string $label
 * Optional: $class, $tag (span|div), $icon, $iconClass, $dot (classes of a leading dot), $attrs,
 *           $wrap (wrap the label in a span; default when there is an icon or dot), $labelClass (classes of that span).
 */
$tag = $tag ?? 'span';
?>
<<?= $tag ?><?= attrs(['class' => $class ?? null] + ($attrs ?? [])) ?>><?php if (isset($dot)): ?><span class="<?= e($dot) ?>"></span><?php endif; ?><?= isset($icon) ? icon($icon, $iconClass ?? '') : '' ?><?= ($wrap ?? (isset($dot) || isset($icon) || isset($labelClass))) ? '<span' . attrs(['class' => $labelClass ?? null]) . '>' . e($label) . '</span>' : e($label) ?></<?= $tag ?>>
