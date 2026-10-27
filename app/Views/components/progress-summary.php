<?php
// Progress of today's due forms; data-progress hooks let public/assets/js/dashboard.js update it in the browser.
$circumference = 2 * M_PI * 30;
$offset = $circumference * (1 - $progress['ratio']);
?>
<div id="progress-panel" class="flex flex-col items-center gap-5 rounded-xl border border-teal-500/30 bg-[#022c22]/80 p-3.5 shadow-inner backdrop-blur-sm sm:flex-row sm:p-4" aria-label="Ringkasan progres harian">
        <div class="flex items-center gap-4">
            <div class="progress-ring relative flex size-20 shrink-0 items-center justify-center">
                <svg class="absolute inset-0 size-full -rotate-90" viewBox="0 0 72 72" aria-hidden="true">
                    <circle cx="36" cy="36" r="30" fill="none" stroke="rgba(20,184,166,0.2)" stroke-width="6" />
                    <circle cx="36" cy="36" r="30" fill="none" stroke="#10b981" stroke-width="6" stroke-linecap="round" data-progress="ring" stroke-dasharray="<?= e(number_format($circumference, 3, '.', '')) ?>" stroke-dashoffset="<?= e(number_format($offset, 3, '.', '')) ?>" />
                </svg>
                <span class="flex flex-col items-center"><span class="text-lg font-black leading-none text-white" data-progress="pct"><?= e($progress['percentage']) ?>%</span><span class="mt-0.5 text-[9px] font-bold uppercase tracking-tighter text-teal-300">Terisi</span></span>
            </div>
            <div class="min-w-0">
                <p class="flex items-center gap-1.5 text-[11.7px] font-bold uppercase tracking-wide text-teal-200"><span class="size-2 shrink-0 rounded-full bg-emerald-400" aria-hidden="true"></span>Progres Harian</p>
                <p class="mt-0.5 flex items-baseline gap-1"><span class="text-[23.4px] font-black text-white" data-progress="done"><?= e($progress['completed']) ?></span><span class="text-[13.5px] font-semibold text-teal-300/80" data-progress="total">/ <?= e($progress['target']) ?> Form</span></p>
                <p class="text-[9.9px] text-teal-200/70" data-progress="note">
                    <?php if ($progress['target'] === 0): ?>Belum ada form harian wajib
                    <?php elseif ($progress['remaining'] === 0): ?>Semua form hari ini sudah diisi
                    <?php else: ?><?= e($progress['remaining']) ?> form belum diinput hari ini<?php endif; ?>
                </p>
            </div>
        </div>
        <div class="hidden h-12 w-px bg-teal-800/60 sm:block" aria-hidden="true"></div>
        <div class="flex w-full flex-col justify-center space-y-1.5 sm:w-auto">
            <div class="flex items-center justify-between gap-4 text-[10.8px]">
                <span class="font-medium text-teal-200/90">Capaian Target</span>
                <span class="shrink-0 font-mono font-bold text-emerald-400" data-progress="ratio"><?= e($progress['completed']) ?>/<?= e($progress['target']) ?> Selesai</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full border border-teal-700/50 bg-teal-950 sm:w-48" role="progressbar" data-progress="meter" aria-label="Capaian pengisian form" aria-valuemin="0" aria-valuemax="<?= e(max(1, $progress['target'])) ?>" aria-valuenow="<?= e($progress['completed']) ?>" aria-valuetext="<?= e($progress['completed']) ?> dari <?= e($progress['target']) ?> form selesai">
                <div class="h-full rounded-full bg-linear-to-r from-teal-400 to-emerald-400" data-progress="bar" style="width: <?= e(number_format($progress['ratio'] * 100, 3, '.', '')) ?>%"></div>
            </div>
            <div class="flex items-center pt-0.5"><span class="rounded border border-emerald-500/40 bg-emerald-950/80 px-1.5 py-0.5 text-[9px] font-semibold text-emerald-300">Target: 100% Hari Ini</span></div>
        </div>
</div>
