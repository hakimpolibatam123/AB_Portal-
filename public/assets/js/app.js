(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  /* Icons rendered by the server, cloned when the UI needs to swap one (see components/toast.php). */
  const iconHtml = (name) => document.getElementById('rs-icons')?.content.querySelector(`[data-name="${name}"]`)?.outerHTML ?? '';

  /* ---------------------------------------------------------------- */
  /* Navbar: live WIB clock and mobile menu                            */
  /* ---------------------------------------------------------------- */
  const DAYS = { Sun: 'Min', Mon: 'Sen', Tue: 'Sel', Wed: 'Rab', Thu: 'Kam', Fri: 'Jum', Sat: 'Sab' };
  const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const jakarta = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Jakarta', weekday: 'short', day: '2-digit', month: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23',
  });
  const clock = $('[data-clock]');
  const tick = () => {
    const parts = Object.fromEntries(jakarta.formatToParts(new Date()).map((p) => [p.type, p.value]));
    clock.textContent = `${DAYS[parts.weekday]} ${parts.day} ${MONTHS[Number(parts.month) - 1]} ${parts.hour}:${parts.minute}:${parts.second}`;
  };
  if (clock) {
    tick();
    window.setInterval(tick, 1000);
  }

  const nav = $('.rsab-nav');
  const menuButton = $('.rsab-menu-btn');
  if (nav && menuButton) {
    menuButton.addEventListener('click', () => {
      menuButton.setAttribute('aria-expanded', String(nav.classList.toggle('is-open')));
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.focus();
      }
    });
  }

  /* ---------------------------------------------------------------- */
  /* Toast                                                             */
  /* ---------------------------------------------------------------- */
  const TOAST = {
    success: { color: 'var(--color-rs-ok)', bg: 'var(--color-rs-ok-bg)', icon: 'circle-check' },
    info: { color: 'var(--color-rs-warn)', bg: 'var(--color-rs-warn-bg)', icon: 'info' },
    error: { color: 'var(--color-rs-err)', bg: 'var(--color-rs-err-bg)', icon: 'circle-alert' },
  };
  const toast = $('#site-toast');
  let toastTimer = null;
  const showToast = (title, description = '', variant = 'success') => {
    if (!toast) return;
    const style = TOAST[variant] ?? TOAST.success;
    const box = $('.rs-toast__box', toast);
    const iconBox = $('[data-toast-icon]', toast);
    box.style.setProperty('--rs-toast', style.color);
    iconBox.style.background = style.bg;
    iconBox.style.color = style.color;
    iconBox.innerHTML = iconHtml(style.icon);
    $('[data-toast-title]', toast).textContent = title;
    const desc = $('[data-toast-desc]', toast);
    desc.textContent = description;
    desc.hidden = description === '';
    const now = new Date();
    $('[data-toast-time]', toast).textContent = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')} WIB`;
    toast.classList.add('is-show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('is-show'), 4500);
  };
  $('[data-toast-close]')?.addEventListener('click', () => toast.classList.remove('is-show'));
  if (toast?.dataset.flashTitle) showToast(toast.dataset.flashTitle, toast.dataset.flashDesc ?? '');

  const notAvailable = (label) => showToast('Belum Tersedia', `${label ? `"${label}" ` : 'Fitur ini '}belum tersedia pada prototype ini.`, 'info');

  /* ---------------------------------------------------------------- */
  /* Links and buttons without a destination yet                       */
  /* ---------------------------------------------------------------- */
  document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-unavailable], a[href="#"], [data-page-btn], [data-export], [data-print], [data-draft], [data-fill-target]');
    if (!target) return;
    if (target.matches('[data-fill-target]')) {
      const input = document.getElementById(target.dataset.fillTarget);
      if (input) { input.value = target.textContent.trim(); input.focus(); }
      return;
    }
    event.preventDefault();
    if (target.matches('[data-print]')) window.print();
    else if (target.dataset.export === 'xlsx') showToast('Mengunduh Laporan Format Excel (.xlsx)...', 'Ekspor masih berupa tampilan; berkas belum dibuat.', 'info');
    else if (target.dataset.export === 'pdf') showToast('Mengunduh Laporan Format PDF...', 'Ekspor masih berupa tampilan; berkas belum dibuat.', 'info');
    else if (target.matches('[data-draft]')) showToast('Draft berhasil disimpan!', 'Data belum dikirim ke rekap.');
    else if (target.matches('[data-page-btn]')) notAvailable(target.textContent.trim() ? `Halaman ${target.textContent.trim()}` : target.getAttribute('aria-label'));
    else notAvailable(target.dataset.unavailable || target.textContent.trim());
  });

  /* ---------------------------------------------------------------- */
  /* Module forms: success dialog, then submit to the rekap page       */
  /* ---------------------------------------------------------------- */
  /* Validation messages in Bahasa Indonesia instead of the browser's language */
  const validationMessage = (field) => {
    const v = field.validity;
    if (v.valueMissing) return field.type === 'checkbox' ? 'Centang pernyataan untuk mengirim.' : field.type === 'radio' ? 'Pilih salah satu opsi.' : 'Kolom ini wajib diisi.';
    if (v.badInput || v.typeMismatch) return 'Format isian tidak valid.';
    if (v.rangeUnderflow) return `Nilai minimal ${field.min}.`;
    if (v.rangeOverflow) return `Nilai maksimal ${field.max}.`;
    if (v.stepMismatch) return 'Gunakan paling banyak satu angka desimal.';
    if (v.tooLong) return `Maksimal ${field.maxLength} karakter.`;
    return '';
  };
  document.addEventListener('invalid', (event) => {
    const field = event.target;
    field.setCustomValidity('');
    if (!field.validity.valid) field.setCustomValidity(validationMessage(field));
  }, true);
  document.addEventListener('input', (event) => event.target.setCustomValidity?.(''), true);
  document.addEventListener('change', (event) => event.target.setCustomValidity?.(''), true);

  const modal = $('#submit-modal');
  $$('[data-module-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (event.submitter?.matches('[data-submit-confirm]')) return; // sent from the dialog
      event.preventDefault();
      if (!form.reportValidity()) return;
      const button = event.submitter;
      if (button) button.disabled = true;
      window.setTimeout(() => {
        if (button) button.disabled = false;
        if (!modal) { form.submit(); return; }
        modal.hidden = false;
        $('[data-submit-confirm]', modal).focus();
      }, 900);
    });
    form.addEventListener('reset', () => window.setTimeout(() => form.dispatchEvent(new Event('rs:reset')), 0));
  });
  modal?.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') modal.hidden = true;
  });

  /* File inputs: show the chosen file name and size */
  $$('[data-file-input]').forEach((input) => {
    const display = document.getElementById(input.dataset.fileInput);
    const update = () => {
      const file = input.files?.[0];
      if (!display) return;
      if (display.matches('[data-file-label]')) {
        display.textContent = file ? `${file.name} (${(file.size / 1048576).toFixed(1)} MB)` : display.dataset.fileLabel;
        return;
      }
      display.classList.toggle('hidden', !file);
      display.classList.toggle('flex', Boolean(file));
      if (file) $('[data-file-name]', display).textContent = `${file.name} (${(file.size / 1048576).toFixed(1)} MB)`;
    };
    input.addEventListener('change', update);
    input.form?.addEventListener('rs:reset', update);
  });

  /* Temperature: live status from the configured bands */
  const suhuInput = $('#suhuInput');
  const suhuBadge = $('#suhuStatusBadge');
  if (suhuInput && suhuBadge) {
    const BANDS = [
      { test: (t) => t >= 27, tone: 'text-error', icon: 'circle-alert', text: 'Bahaya / Overheat — segera periksa unit pendingin' },
      { test: (t) => t > 24 || t < 18, tone: 'text-amber-700', icon: 'triangle-alert', text: 'Peringatan — suhu di luar batas optimal' },
      { test: () => true, tone: 'text-tertiary', icon: 'circle-check', text: 'Optimal & Sesuai Regulasi Server Data Center' },
    ];
    const update = () => {
      const value = Number.parseFloat(String(suhuInput.value).replace(',', '.'));
      const band = Number.isNaN(value) ? BANDS[2] : BANDS.find((b) => b.test(Math.round(value * 10) / 10));
      suhuBadge.classList.remove('text-error', 'text-amber-700', 'text-tertiary');
      suhuBadge.classList.add(band.tone);
      suhuBadge.querySelector('.rs-ic').outerHTML = iconHtml(band.icon).replace('class="rs-ic"', 'class="rs-ic text-[18px]"');
      $('[data-suhu-status]', suhuBadge).textContent = band.text;
    };
    suhuInput.addEventListener('input', update);
    suhuInput.form?.addEventListener('rs:reset', update);
  }

  /* Character counter */
  $$('[data-char-count]').forEach((field) => {
    const output = document.getElementById(field.dataset.charCount);
    const update = () => { output.textContent = `${field.value.length} Karakter`; };
    field.addEventListener('input', update);
    field.form?.addEventListener('rs:reset', update);
  });

  /* Radio groups that reveal an extra field */
  $$('[data-reveal-on]').forEach((wrapper) => {
    const [name, value] = wrapper.dataset.revealOn.split('=');
    const radios = $$(`input[type="radio"][name="${name}"]`);
    const update = () => wrapper.classList.toggle('hidden', !radios.some((r) => r.checked && r.value === value));
    radios.forEach((radio) => radio.addEventListener('change', update));
    radios[0]?.form?.addEventListener('rs:reset', update);
  });

  /* ---------------------------------------------------------------- */
  /* Password page                                                     */
  /* ---------------------------------------------------------------- */
  $$('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const input = document.getElementById(button.dataset.togglePassword);
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-pressed', String(show));
      button.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
      button.innerHTML = iconHtml(show ? 'eye-off' : 'eye');
    });
  });
  $$('[data-password-form]').forEach((form) => {
    const confirm = form.elements.namedItem('confirmPassword');
    const error = document.getElementById('confirmError');
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const empty = $$('input[type="password"], input[type="text"]', form).find((input) => input.value === '');
      const mismatch = form.elements.namedItem('newPassword').value !== confirm.value;
      error.hidden = !mismatch || Boolean(empty);
      confirm.setAttribute('aria-invalid', String(!error.hidden));
      $('[data-error-text]', error).textContent = 'Konfirmasi password tidak sama dengan password baru.';
      if (empty) { empty.focus(); showToast('Lengkapi semua kolom', 'Semua kolom password wajib diisi.', 'error'); return; }
      if (mismatch) { confirm.focus(); return; }
      form.reset();
      showToast('Password berhasil diperbarui', 'Perubahan masih simulasi; autentikasi belum terhubung.');
    });
  });

  /* ---------------------------------------------------------------- */
  /* Rekap filters (selects + quick search over the rendered rows)     */
  /* ---------------------------------------------------------------- */
  $$('[data-filter-scope]').forEach((scope) => {
    const rows = $$('tr[data-row]', scope);
    const empty = $('[data-filter-empty]', scope);
    const count = $('[data-filter-count]', scope);
    const selects = $$('select[data-filter]', scope);
    const search = $('[data-filter-search]', scope);
    let timer = null;
    const apply = () => {
      const query = (search?.value ?? '').trim().toLowerCase();
      let shown = 0;
      rows.forEach((row) => {
        const matches = selects.every((select) => select.value === '' || (row.dataset[select.dataset.filter] ?? '').split(' ').includes(select.value))
          && (query === '' || row.textContent.replace(/\s+/g, ' ').toLowerCase().includes(query));
        row.hidden = !matches;
        if (matches) shown += 1;
      });
      if (empty) empty.hidden = shown > 0;
      if (count) count.textContent = shown > 0 ? `1 - ${shown}` : '0';
    };
    selects.forEach((select) => select.addEventListener('change', apply));
    search?.addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(apply, 150); });
  });
})();
