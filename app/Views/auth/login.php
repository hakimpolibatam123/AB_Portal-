<main class="min-h-screen w-full flex flex-col lg:flex-row">
    <section class="w-full lg:w-[38%] min-h-screen bg-white flex flex-col justify-center px-8 sm:px-14 md:px-20 lg:px-16 xl:px-24 py-12 z-10" aria-labelledby="login-title">
        <div class="w-full max-w-sm mx-auto">
            <div class="flex items-center justify-between mb-8">
                <h1 id="login-title" class="text-[24px] leading-8 font-semibold text-slate-900 tracking-tight">Sign In</h1>
                <?= partial('badge', ['label' => 'Botania', 'class' => 'inline-flex items-center justify-center bg-rs-teal-700 text-white text-xs font-semibold px-2.5 py-0.5 rounded-sm tracking-wide shadow-rs']) ?>
            </div>
            <form class="space-y-4" id="login-form" method="post" action="<?= e(url('/login')) ?>" data-redirect="<?= e($redirectTo) ?>" novalidate>
                <p class="rounded-lg border border-rs-err/20 bg-rs-err-bg px-3.5 py-2.5 text-[13px] text-rs-err" id="login-error" role="alert" hidden></p>
                <div>
                    <label class="block text-[13px] leading-[1.55] font-medium text-rs-ink-2 mb-1.5" for="username">Username</label>
                    <input class="w-full rounded-lg px-3.5 py-2.5 text-sm text-slate-800 min-h-[42px] transition" id="username" name="username" placeholder="Username" required type="text" autocomplete="username" autocapitalize="none" spellcheck="false" aria-describedby="username-error">
                    <p class="rs-error mt-1.5" id="username-error" hidden></p>
                </div>
                <div class="pt-1">
                    <label class="block text-[13px] leading-[1.55] font-medium text-rs-ink-2 mb-1.5" for="password">Password</label>
                    <input class="w-full rounded-lg px-3.5 py-2.5 text-sm text-slate-800 min-h-[42px] transition" id="password" name="password" placeholder="Password" required type="password" autocomplete="current-password" aria-describedby="password-error">
                    <p class="rs-error mt-1.5" id="password-error" hidden></p>
                </div>
                <div class="pt-2">
                    <button class="w-full inline-flex items-center justify-center gap-2 bg-rs-teal-700 hover:bg-rs-teal-800 text-white text-sm font-medium py-2.5 px-4 rounded-lg min-h-[42px] transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-70" type="submit" data-submit>
                        <span data-submit-label>Sign In</span>
                        <?= icon('login-arrow', 'text-[16px] [&>svg]:stroke-[2.2]', ['data-submit-icon' => true]) ?>
                        <?= icon('loader-circle', 'text-[16px] animate-spin', ['data-submit-spinner' => true, 'hidden' => true]) ?>
                    </button>
                </div>
                <noscript><p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900">Aktifkan JavaScript untuk masuk ke portal.</p></noscript>
            </form>
            <div class="mt-8 text-center">
                <p class="text-xs text-slate-400">&copy; <?= e(date('Y')) ?> RS Awal Bros Botania</p>
            </div>
        </div>
    </section>
    <section class="w-full lg:w-[62%] min-h-screen bg-rs-teal-50 flex flex-col items-center justify-center p-6 md:p-12 lg:p-16" aria-label="Portal Web RS Awal Bros Botania">
        <div class="w-full max-w-3xl flex flex-col items-center text-center">
            <div class="mb-8 md:mb-10">
                <h2 class="text-2xl sm:text-3xl lg:text-[2.1rem] font-bold text-rs-teal-900 leading-tight sm:leading-9 tracking-[-0.01em] max-w-xl mx-auto">PORTAL WEB RS AWALBROS BOTANIA</h2>
            </div>
            <div class="w-full max-w-2xl aspect-[16/10] bg-white rounded-2xl overflow-hidden border border-rs-line shadow-rs">
                <img alt="Gedung RS Awal Bros Botania" class="w-full h-full block select-none object-cover" src="<?= e(asset('images/login-hero.webp')) ?>" width="1660" height="947">
            </div>
        </div>
    </section>
</main>
