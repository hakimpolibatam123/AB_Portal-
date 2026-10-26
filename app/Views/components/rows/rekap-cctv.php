<?php
/** @var array $item  one entry from database/dummy/cctv.php */
/** @var int $index */
$officer = dummy('cctv')['officers'][$item['officer']];
$camera = $item['camera'];
$alert = $camera !== null && str_contains($camera['box'], 'error');
$cameraFilter = $camera === null ? 'nihil' : $camera['kind'];
?>
<tr class="<?= $index % 2 === 0 ? 'bg-surface-container-lowest' : 'bg-surface-bright' ?> hover:bg-surface-container-low transition-colors group" data-row data-month="<?= e($item['month']) ?>" data-ntp="<?= e($item['ntp']) ?>" data-camera="<?= e($cameraFilter) ?>" data-zone="<?= e($item['zone']) ?>">
    <td class="py-3.5 px-4">
        <div class="flex items-center gap-2">
            <?= partial('badge', ['label' => $item['no'], 'class' => cx('text-label-sm px-2 py-0.5 rounded-sm font-bold', $alert ? 'bg-error-container text-on-error-container' : 'bg-surface-container text-on-surface')]) ?>
            <div class="flex flex-col">
                <span class="text-body-sm text-on-surface font-semibold"><?= e($item['when']) ?></span>
                <span class="text-label-sm <?= $item['shift'] === 'Shift Pagi' ? 'text-secondary' : 'text-on-surface-variant' ?>"><?= e($item['shift']) ?></span>
            </div>
        </div>
    </td>
    <td class="py-3.5 px-4">
        <div class="flex items-center gap-2">
            <?= icon('map-pin', 'text-secondary text-[18px]') ?>
            <div class="flex flex-col">
                <span class="text-body-md text-on-surface font-medium"><?= e($item['location']) ?></span>
                <span class="text-label-sm text-on-surface-variant"><?= e($item['detail']) ?></span>
            </div>
        </div>
    </td>
    <td class="py-3.5 px-4">
        <?php if ($camera === null): ?>
        <?= partial('badge', ['tag' => 'div', 'label' => 'Nihil / Semua Berfungsi Baik', 'icon' => 'circle-check', 'iconClass' => 'text-[16px] text-tertiary', 'class' => 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm bg-secondary-container text-on-secondary-container text-label-sm']) ?>
        <?php else: ?>
        <div class="p-2 rounded-sm <?= e($camera['box']) ?> space-y-0.5">
            <div class="flex items-center gap-1.5 <?= $alert ? 'text-on-error-container font-bold' : 'text-on-surface font-medium' ?> text-label-md">
                <?= icon($camera['icon'], cx('text-[16px]', $alert ? '' : 'text-outline')) ?>
                <span><?= e($camera['name']) ?></span>
            </div>
            <span class="text-label-sm <?= $alert ? 'text-error font-medium' : 'text-on-surface-variant' ?> block"><?= e($camera['status']) ?></span>
        </div>
        <?php endif; ?>
    </td>
    <td class="py-3.5 px-4">
        <?php if ($item['ntp'] === 'kalibrasi'): ?>
        <?= partial('badge', ['tag' => 'div', 'label' => $item['ntp_label'], 'icon' => 'check', 'iconClass' => 'text-[14px] text-tertiary', 'class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-surface-container-high text-on-surface text-label-sm uppercase font-semibold tracking-[.04em] whitespace-nowrap']) ?>
        <?php else: ?>
        <?= partial('badge', ['tag' => 'div', 'label' => $item['ntp_label'], 'icon' => 'check-check', 'iconClass' => 'text-[14px]', 'class' => 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-label-sm uppercase font-semibold tracking-[.04em] whitespace-nowrap']) ?>
        <?php endif; ?>
    </td>
    <td class="py-3.5 px-4">
        <a class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-sm bg-surface-container hover:bg-surface-container-high text-on-surface text-label-sm transition-colors" href="#" data-unavailable="<?= e($item['file']) ?>">
            <?= icon($item['file_icon'], cx('text-[16px]', $item['file_tone'])) ?>
            <span class="truncate max-w-[120px]"><?= e($item['file']) ?></span>
            <?= icon($item['file_action'], 'text-[14px]') ?>
        </a>
    </td>
    <td class="py-3.5 px-4">
        <div class="flex flex-col">
            <span class="text-label-sm <?= e($item['nvr_tone']) ?> font-semibold"><?= e($item['nvr']) ?></span>
            <span class="text-body-sm text-on-surface-variant"><?= e($item['note']) ?></span>
        </div>
    </td>
    <td class="py-3.5 px-4">
        <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-full <?= e(explode(' ', $officer['avatar'])[0]) ?> flex items-center justify-center text-label-sm <?= e(explode(' ', $officer['avatar'])[1]) ?> font-bold whitespace-nowrap"><?= e($officer['initials']) ?></div>
            <div class="flex flex-col">
                <span class="text-body-sm text-on-surface font-medium"><?= e($officer['name']) ?></span>
                <span class="text-label-sm text-on-surface-variant mt-1"><?= e($officer['role']) ?></span>
            </div>
        </div>
    </td>
</tr>
