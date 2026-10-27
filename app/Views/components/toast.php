<?php /** Optional: $flash (['title' => ..., 'description' => ..., 'variant' => 'success'|'info'|'error']) is shown once the page loads. */ ?>
<div class="rs-toast" id="site-toast" role="status" aria-live="polite"<?= attrs(['data-flash-title' => $flash['title'] ?? null, 'data-flash-desc' => $flash['description'] ?? null, 'data-flash-variant' => $flash['variant'] ?? null]) ?>>
    <div class="rs-toast__box">
        <span class="rs-toast__icon" data-toast-icon><?= design_icon('circle-check') ?></span>
        <div class="flex-1 min-w-0">
            <h4 class="rs-toast__title" data-toast-title></h4>
            <p class="rs-toast__desc" data-toast-desc></p>
            <div class="rs-toast__time" data-toast-time></div>
        </div>
        <button type="button" class="rs-toast__close" aria-label="Tutup" data-toast-close><?= design_icon('x') ?></button>
    </div>
</div>
<template id="rs-icons"><?php foreach (['circle-check', 'info', 'circle-alert', 'triangle-alert', 'eye', 'eye-off'] as $name): ?><?= design_icon($name, '', ['data-name' => $name]) ?><?php endforeach; ?></template>
