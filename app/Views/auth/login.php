<?php
/**
 * Login page: the portal title, and under it the link sections of the dashboard (Monitoring & Laporan, Sistem) that
 * anyone can open without signing in. "Masuk" in the header opens the login form in a window (public/assets/js/login.js);
 * it opens by itself after a refused login or with a notice. Styles: resources/css/login-portal.css.
 * @var list<array{id: mixed, title: string, icon: string, cards: list<array<string, mixed>>}> $linkSections
 */
$linkSections ??= [];
?>
<div class="lp">
    <header class="lp-top">
        <div class="lp-shell lp-top__row">
            <a class="lp-brand" href="<?= e(url('/login')) ?>" aria-label="Portal Web RS Awal Bros Botania">
                <?= partial('brand') ?>
                <span class="lp-brand__text"><b>Portal Web</b><small>RS Awal Bros Botania</small></span>
            </a>
            <button type="button" class="lp-top__login" data-open-login aria-haspopup="dialog" aria-controls="login-card"><?= design_icon('login-arrow', 'text-[16px]') ?>Masuk</button>
        </div>
    </header>

    <main id="main-content" class="lp-shell lp-main">
        <div class="lp-hero">
            <section class="lp-welcome" aria-labelledby="lp-title">
                <img class="lp-welcome__img" alt="" src="<?= e(asset('images/login-hero.webp')) ?>" width="1660" height="947">
                <div class="lp-welcome__text">
                    <p class="lp-welcome__eyebrow">RS Awal Bros Botania</p>
                    <h2 id="lp-title">PORTAL WEB RS AWALBROS BOTANIA</h2>
                    <p>Buka sistem dan dashboard rumah sakit langsung dari kartu di bawah. Masuk untuk mengisi form monitoring dan mengelola portal.</p>
                </div>
            </section>
        </div>

        <dialog class="lp-login" id="login-card" aria-labelledby="login-title"<?= !empty($error) || !empty($notice) ? ' data-autoopen' : '' ?>>
                <div class="flex items-center justify-between gap-3 mb-5">
                    <h1 id="login-title" class="text-[20px] leading-7 font-semibold text-slate-900 tracking-tight">Masuk</h1>
                    <span class="flex items-center gap-2">
                        <?= partial('badge', ['label' => 'Botania', 'class' => 'inline-flex items-center justify-center bg-rs-teal-700 text-white text-xs font-semibold px-2.5 py-0.5 rounded-sm tracking-wide shadow-rs']) ?>
                        <button type="button" class="lp-login__x" data-close-login aria-label="Tutup">&times;</button>
                    </span>
                </div>
                <form class="space-y-3.5" id="login-form" method="post" action="<?= e(url('/login')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <?php if (!empty($notice)): ?>
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-[13px] text-amber-900" role="status"><strong><?= e($notice['title']) ?>.</strong> <?= e($notice['description']) ?></p>
                    <?php endif; ?>
                    <p class="rounded-lg border border-rs-err/20 bg-rs-err-bg px-3.5 py-2.5 text-[13px] text-rs-err" id="login-error" role="alert"<?= empty($error) ? ' hidden' : '' ?>><?= e($error ?? '') ?></p>
                    <div>
                        <label class="block text-[13px] leading-[1.55] font-medium text-rs-ink-2 mb-1.5" for="username">Username</label>
                        <input class="w-full rounded-lg px-3.5 py-2.5 text-sm text-slate-800 min-h-[42px] transition" id="username" name="username" placeholder="Username" required type="text" value="<?= e($username ?? '') ?>" autocomplete="username" autocapitalize="none" spellcheck="false" aria-describedby="username-error">
                        <p class="rs-error mt-1.5" id="username-error" hidden></p>
                    </div>
                    <div>
                        <label class="block text-[13px] leading-[1.55] font-medium text-rs-ink-2 mb-1.5" for="password">Password</label>
                        <input class="w-full rounded-lg px-3.5 py-2.5 text-sm text-slate-800 min-h-[42px] transition" id="password" name="password" placeholder="Password" required type="password" autocomplete="current-password" aria-describedby="password-error">
                        <p class="rs-error mt-1.5" id="password-error" hidden></p>
                    </div>
                    <div class="pt-1">
                        <button class="w-full inline-flex items-center justify-center gap-2 bg-rs-teal-700 hover:bg-rs-teal-800 text-white text-sm font-medium py-2.5 px-4 rounded-lg min-h-[42px] transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-70" type="submit" data-submit>
                            <span data-submit-label>Masuk</span>
                            <?= design_icon('login-arrow', 'text-[16px] [&>svg]:stroke-[2.2]', ['data-submit-icon' => true]) ?>
                            <?= design_icon('loader-circle', 'text-[16px] animate-spin', ['data-submit-spinner' => true, 'hidden' => true]) ?>
                        </button>
                    </div>
                </form>
        </dialog>
        <noscript><p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Aktifkan JavaScript untuk masuk ke portal.</p></noscript>

        <?php foreach ($linkSections as $section): ?>
        <section class="lp-section" aria-labelledby="lp-sec-<?= e((string) $section['id']) ?>">
            <h2 class="lp-section__title" id="lp-sec-<?= e((string) $section['id']) ?>"><?= card_icon($section['icon'], 'lp-section__icon') ?><?= e($section['title']) ?></h2>
            <div class="lp-grid">
                <?php foreach ($section['cards'] as $card):
                    $isUrl = preg_match('#^(https?://|www\.)\S+$#i', trim($card['description'])) === 1;
                ?>
                <article class="lp-card">
                    <span class="lp-card__icon tone-<?= e($card['color']) ?>"><?php if ($card['image'] !== null): ?><img src="<?= e($card['image']) ?>" alt="" loading="lazy" referrerpolicy="no-referrer"><?php else: ?><?= card_icon($card['icon']) ?><?php endif; ?></span>
                    <h3 class="lp-card__title">
                        <?php if ($card['url'] !== null): ?>
                        <a class="lp-card__link" href="<?= e($card['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($card['title']) ?></a>
                        <?php else: ?>
                        <?= e($card['title']) ?>
                        <?php endif; ?>
                    </h3>
                    <p class="lp-card__desc<?= $isUrl ? ' is-url' : '' ?>"><?= e($card['description']) ?><?php if ($card['url'] === null): ?><span class="lp-card__note">Alamat belum diatur</span><?php endif; ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </main>

    <footer class="lp-foot">&copy; <?= e(date('Y')) ?> By Rizky Rashid Sasha Hakim</footer>
</div>
