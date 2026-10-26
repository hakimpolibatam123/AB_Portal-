<?php
/**
 * Button, or a link styled as one when $href is set.
 * @var string $label
 * Optional: $href, $type (button|submit|reset), $class, $icon, $iconClass, $iconAfter, $iconAfterClass, $attrs.
 */
$isLink = isset($href);
$attributes = $isLink ? ['class' => $class ?? null, 'href' => $href] : ['class' => $class ?? null, 'type' => $type ?? 'button'];
$tag = $isLink ? 'a' : 'button';
?>
<<?= $tag ?><?= attrs($attributes + ($attrs ?? [])) ?>><?= isset($icon) ? icon($icon, $iconClass ?? '') : '' ?><?= isset($icon) || isset($iconAfter) ? '<span>' . e($label) . '</span>' : e($label) ?><?= isset($iconAfter) ? icon($iconAfter, $iconAfterClass ?? '') : '' ?></<?= $tag ?>>
