<?php
/**
 * Form field: label, control and optional help/error text.
 * @var string $label
 * Control: $control (input|select|textarea, default input), $type, $name, $id, $value, $placeholder,
 *          $options ([value => label] for selects), $selected, $controlClass, $attrs (extra control attributes).
 * Layout:  $class (wrapper), $labelClass, $labelHtml (rendered label content instead of $label),
 *          $required (append the required mark), $requiredClass, $boxClass (wrapper around the control),
 *          $iconLeft / $iconRight ([name, classes]), $toggle (password visibility button),
 *          $help, $helpClass, $error (['id' => ..., 'text' => ...]).
 * $variant "account" renders the account-page field style (.rs-field / .rs-input).
 */
$control = $control ?? 'input';
$id = $id ?? $name ?? null;
$account = ($variant ?? '') === 'account';
$controlAttrs = ['class' => $controlClass ?? null, 'id' => $id, 'name' => $name ?? null] + ($attrs ?? []);
if ($control === 'input') {
    $controlAttrs = ['type' => $type ?? 'text'] + $controlAttrs + ['value' => $value ?? null, 'placeholder' => $placeholder ?? null];
} elseif ($control === 'textarea') {
    $controlAttrs += ['placeholder' => $placeholder ?? null];
}
if (isset($error['id'])) {
    $controlAttrs['aria-describedby'] = $error['id'];
}
$boxClass = $account ? 'rs-input' : ($boxClass ?? null);
?>
<div<?= attrs(['class' => $account ? 'rs-field' : ($class ?? null)]) ?>>
    <label<?= attrs(['class' => $account ? null : ($labelClass ?? null), 'for' => $id]) ?>><?= $labelHtml ?? e($label) ?><?php if (!empty($required)): ?><span class="<?= e($account ? 'req' : ($requiredClass ?? 'text-error')) ?>">*</span><?php endif; ?></label>
    <?php if ($boxClass !== null): ?><div class="<?= e($boxClass) ?>"><?php endif; ?>
        <?php if (isset($iconLeft)): ?><?= icon($iconLeft[0], $iconLeft[1] ?? '') ?><?php endif; ?>
        <?php if ($control === 'select'): ?>
        <select<?= attrs($controlAttrs) ?>>
            <?php foreach ($options ?? [] as $optionValue => $optionLabel): ?>
            <option<?= attrs(['value' => is_string($optionValue) ? $optionValue : null, 'selected' => (string) $optionValue === (string) ($selected ?? array_key_first($options))]) ?>><?= e($optionLabel) ?></option>
            <?php endforeach; ?>
        </select>
        <?php elseif ($control === 'textarea'): ?>
        <textarea<?= attrs($controlAttrs) ?>><?= e($value ?? '') ?></textarea>
        <?php else: ?>
        <input<?= attrs($controlAttrs) ?>>
        <?php endif; ?>
        <?php if (isset($iconRight)): ?><?= icon($iconRight[0], $iconRight[1] ?? '') ?><?php endif; ?>
        <?php if (!empty($toggle)): ?>
        <button type="button" class="rs-input__toggle" data-toggle-password="<?= e($id) ?>" aria-label="Tampilkan password" aria-pressed="false"><?= icon('eye') ?></button>
        <?php endif; ?>
    <?php if ($boxClass !== null): ?></div><?php endif; ?>
    <?php if (isset($help)): ?><p class="<?= e($account ? 'rs-help' : ($helpClass ?? '')) ?>"><?= e($help) ?></p><?php endif; ?>
    <?php if (isset($error)): ?>
    <p class="rs-error" id="<?= e($error['id']) ?>" hidden><?= icon('circle-alert') ?><span data-error-text><?= e($error['text'] ?? '') ?></span></p>
    <?php endif; ?>
</div>
