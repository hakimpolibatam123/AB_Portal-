<?php
/**
 * Data table. Each row is rendered by a row component from app/Views/components/rows.
 * @var array $columns  [['label' => ..., 'class' => ?string]]
 * @var array $rows
 * @var string $row  row component name, e.g. "rows/rekap-suhu"
 * Optional: $wrapClass, $class, $theadRowClass, $tbodyClass, $empty (text of the row shown when a filter
 *           hides every row; enables client-side filtering when set).
 */
?>
<div<?= attrs(['class' => $wrapClass ?? null]) ?>>
<table<?= attrs(['class' => $class ?? null]) ?>>
    <thead>
        <tr<?= attrs(['class' => $theadRowClass ?? null]) ?>>
            <?php foreach ($columns as $column): ?>
            <th<?= attrs(['class' => $column['class'] ?? null, 'scope' => 'col']) ?>><?= e($column['label']) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody<?= attrs(['class' => $tbodyClass ?? null, 'data-filter-rows' => isset($empty) ? true : null]) ?>>
        <?php foreach ($rows as $index => $item): ?><?= partial($row, ['item' => $item, 'index' => $index]) ?><?php endforeach; ?>
        <?php if (isset($empty)): ?>
        <tr class="rs-filter-empty" data-filter-empty hidden><td colspan="<?= e(count($columns)) ?>"><?= e($empty) ?></td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>
