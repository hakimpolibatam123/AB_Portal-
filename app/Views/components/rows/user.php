<?php /** @var array $item */ ?>
<tr>
    <td><div class="rs-person"><span class="rsab-avatar"><?= e($item['initials']) ?></span><span><b><?= e($item['name']) ?></b><small><?= e($item['note']) ?></small></span></div></td>
    <td><span class="rs-pill rs-pill--teal"><?= e($item['role']) ?></span></td>
    <td><span class="rs-pill rs-pill--ok"><i></i><?= e($item['status']) ?></span></td>
</tr>
