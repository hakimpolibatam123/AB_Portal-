<?php
/** @var array $item  one workstation row from database/dummy/cleardesk.php */
$auditor = dummy('cleardesk')['auditors'][$item['auditor']];
$finding = $item['status'] !== 'patuh';
?>
<tr class="<?= $finding ? 'bg-error-container/10 hover:bg-error-container/20' : 'hover:bg-surface-container-low/60' ?> transition-colors" data-row data-month="<?= e($item['month']) ?>" data-status="<?= e($item['status']) ?>" data-unit="<?= e($item['unit']) ?>" data-os="<?= e($item['os']) ?>">
    <td class="py-3.5 px-space-md whitespace-nowrap">
        <div class="flex flex-col">
            <span class="text-label-md font-bold <?= $finding ? 'text-primary' : 'text-on-surface' ?>"><?= e($item['no']) ?></span>
            <span class="text-label-sm text-on-surface-variant"><?= e($item['when']) ?></span>
        </div>
    </td>
    <td class="py-3.5 px-space-md whitespace-nowrap">
        <div class="flex items-center gap-2">
            <?= icon($item['icon'], cx($finding ? 'text-primary' : 'text-secondary', 'text-[18px]')) ?>
            <div class="flex flex-col">
                <span class="font-semibold text-on-surface"><?= e($item['location']) ?></span>
                <span class="text-label-sm text-on-surface-variant"><?= e($item['detail']) ?></span>
            </div>
        </div>
    </td>
    <td class="py-3.5 px-space-md whitespace-nowrap"><span class="text-label-sm bg-surface-container-high px-2 py-0.5 rounded-sm text-on-surface font-semibold"><?= e($item['mac']) ?></span></td>
    <td class="py-3.5 px-space-md whitespace-nowrap"><span class="text-label-sm text-secondary font-medium"><?= e($item['ip']) ?></span></td>
    <td class="py-3.5 px-space-md whitespace-nowrap">
        <?= partial('badge', ['label' => $item['os_label'], 'wrap' => false, 'icon' => $item['os_icon'], 'iconClass' => 'text-[16px] text-on-surface-variant', 'class' => 'inline-flex items-center gap-1.5 font-medium text-on-surface']) ?>
    </td>
    <td class="py-3.5 px-space-md whitespace-nowrap">
        <?php if ($finding): ?>
        <?= partial('badge', ['label' => $item['status_label'], 'icon' => $item['status'] === 'screen' ? 'lock-open' : 'triangle-alert', 'iconClass' => 'text-[14px]', 'class' => 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-error-container text-primary text-label-sm font-bold whitespace-nowrap']) ?>
        <?php else: ?>
        <?= partial('badge', ['label' => $item['status_label'], 'icon' => 'circle-check', 'iconClass' => 'text-[14px]', 'class' => 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-secondary-fixed/50 text-tertiary text-label-sm font-semibold whitespace-nowrap']) ?>
        <?php endif; ?>
    </td>
    <td class="py-3.5 px-space-md whitespace-nowrap">
        <a class="inline-flex items-center gap-1 px-2 py-1 rounded-sm <?= $finding ? 'bg-error-container/50 hover:bg-error-container text-primary font-semibold' : 'bg-surface-container-low hover:bg-surface-container-high text-tertiary font-medium' ?> text-label-sm transition-colors" href="#" data-unavailable="<?= e($item['file']) ?>">
            <?= icon('image', 'text-[14px]') ?><span><?= e($item['file']) ?></span><?= icon('eye', 'text-[12px]') ?>
        </a>
    </td>
    <td class="py-3.5 px-space-md min-w-[240px]">
        <p class="<?= $finding ? 'text-primary font-medium' : 'text-on-surface-variant' ?> leading-tight"><?= e($item['note']) ?></p>
    </td>
    <td class="py-3.5 px-space-md whitespace-nowrap">
        <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full <?= e($auditor['avatar']) ?> flex items-center justify-center text-label-sm font-bold whitespace-nowrap"><?= e($auditor['initials']) ?></div>
            <span class="font-medium text-on-surface"><?= e($auditor['name']) ?></span>
        </div>
    </td>
</tr>
