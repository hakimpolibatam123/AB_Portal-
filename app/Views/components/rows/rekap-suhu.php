<?php
/** @var array $item  one entry from database/dummy/suhu.php */
$officer = dummy('suhu')['officers'][$item['officer']];
$warn = $item['temp'] > 24.0;
$status = $item['temp'] > 27.0 ? 'warning critical' : ($warn ? 'warning' : ($item['temp'] >= 18.0 ? 'normal' : 'warning'));
$isAdmin = $item['officer'] === 'ib';
?>
<tr class="<?= $warn ? 'bg-error-container/20 hover:bg-error-container/30' : 'hover:bg-surface-container-low/70' ?> transition-colors" data-row data-month="<?= e($item['month']) ?>" data-status="<?= e($status) ?>" data-location="<?= e($item['location_key']) ?>">
    <td class="py-3.5 px-4 align-top">
        <div class="flex flex-col">
            <span class="font-bold <?= $warn ? 'text-error' : 'text-secondary' ?>"><?= e($item['no']) ?> • <?= e($item['date']) ?></span>
            <span class="font-semibold text-on-surface"><?= e($item['time']) ?></span>
            <span class="text-on-surface-variant"><?= e($item['shift']) ?></span>
        </div>
    </td>
    <td class="py-3.5 px-4 align-top">
        <div class="flex items-center gap-2">
            <?= icon($item['icon'], cx($warn ? 'text-primary' : 'text-secondary', 'text-[18px]')) ?>
            <div>
                <span class="font-bold block text-on-surface"><?= e($item['location']) ?></span>
                <span class="text-on-surface-variant"><?= e($item['sensor']) ?></span>
            </div>
        </div>
    </td>
    <td class="py-3.5 px-4 align-top text-center">
        <div class="inline-flex flex-col items-center">
            <span class="font-bold <?= $warn ? 'text-error' : 'text-tertiary' ?>"><?= e(number_format($item['temp'], 1)) ?>°C</span>
            <?= partial('badge', ['label' => ' ' . $item['status'], 'wrap' => false, 'dot' => cx('w-1.5 h-1.5 rounded-full', $warn ? 'bg-error' : 'bg-tertiary'), 'class' => cx('inline-flex items-center gap-1 px-2 py-0.5 mt-0.5 rounded-full font-semibold whitespace-nowrap', $warn ? 'bg-error-container text-on-error-container' : 'bg-secondary-container/40 text-tertiary')]) ?>
        </div>
    </td>
    <td class="py-3.5 px-4 align-top">
        <a class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-sm <?= $warn ? 'bg-surface-container-lowest text-on-error-container' : 'bg-surface-container-low text-secondary' ?> hover:bg-surface-container font-semibold transition-colors group" href="#" data-unavailable="<?= e($item['file']) ?>">
            <?= icon($item['file_icon'], cx('text-[16px]', $warn ? 'text-error' : 'text-tertiary')) ?>
            <span class="truncate max-w-[130px]"><?= e($item['file']) ?></span>
            <?= icon('eye', 'text-[14px] text-on-surface-variant group-hover:text-primary') ?>
        </a>
    </td>
    <td class="py-3.5 px-4 align-top">
        <p class="text-on-surface leading-tight"><?= e($item['note']) ?></p>
    </td>
    <td class="py-3.5 px-4 align-top">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-sm <?= $isAdmin ? 'bg-secondary text-on-secondary' : 'bg-surface-variant text-on-surface' ?> flex items-center justify-center font-bold"><?= e($officer['initials']) ?></div>
            <div class="flex flex-col">
                <span class="font-bold leading-none text-on-surface"><?= e($officer['name']) ?></span>
                <span class="<?= $isAdmin ? 'text-secondary' : 'text-on-surface-variant' ?>"><?= e($officer['role']) ?></span>
            </div>
        </div>
    </td>
</tr>
