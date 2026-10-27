(() => {
  'use strict';

  /* Dynamic forms, frontend only (app/Views/form-dinamis/fill.php and respons.php). The form definition
     and its responses live in the shared browser state (dashboard-store.js: dummy data + sessionStorage).
     The fill-in page validates the answers in the browser, keeps a submitted answer there and marks the
     form done today on the dashboard; the responses page lists those answers. Nothing is sent to the server. */
  const store = window.DashboardStore;
  if (!store?.ready) return;
  const { $, $$, esc, uid, icon, showToast, scheduleLabel } = store;
  const BLOCKS = ['section', 'title', 'image'];
  const validUrl = (value) => { try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && url.hostname !== ''; } catch { return false; } };

  const page = $('[data-form-fill]') ?? $('[data-form-responses]');
  if (!page) return;
  const state = store.load();
  if (!state.responses || Array.isArray(state.responses)) state.responses = {};
  const formId = page.dataset.formId;
  const card = state.cards.find((item) => item.id === formId && item.kind === 'form');
  const form = state.forms?.[formId];
  if (!card || !form) { $('[data-fill-missing]').hidden = false; return; }
  const questions = (form.questions ?? []).filter((q) => !BLOCKS.includes(q.type));
  const responsesUrl = (page.dataset.responsesUrl ?? '') + `?id=${encodeURIComponent(formId)}`;

  /* ====================================================================== */
  /* Responses page                                                          */
  /* ====================================================================== */
  if (page.matches('[data-form-responses]')) {
    const rows = [...(state.responses[formId] ?? [])].sort((a, b) => String(b.at).localeCompare(String(a.at)));
    const when = new Intl.DateTimeFormat('id-ID', { timeZone: 'Asia/Jakarta', day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' });
    $('[data-fr-root]').hidden = false;
    const tile = $('[data-fr-tile]');
    tile.className = `card-icon tone-${card.color} fr-head__icon`;
    tile.innerHTML = icon(card.icon);
    $('[data-fr-title]').textContent = form.title || card.title;
    $('[data-fr-fill]').href = `${page.dataset.fillUrl}?id=${encodeURIComponent(formId)}`;
    $('[data-fr-count]').textContent = `${rows.length} respons`;
    const value = (answer) => {
      if (Array.isArray(answer)) return answer.length ? answer.join(', ') : '';
      if (typeof answer === 'number') return answer.toLocaleString('id-ID'); // 72,5
      return answer == null ? '' : String(answer);
    };
    $('[data-fr-body]').innerHTML = rows.length
      ? '<div class="fr-table-wrap" role="region" aria-label="Tabel respons" tabindex="0"><table class="fr-table"><thead><tr><th scope="col">Waktu (WIB)</th>'
        + questions.map((q) => `<th scope="col">${esc(q.title)}</th>`).join('') + '</tr></thead><tbody>'
        + rows.map((row) => `<tr><td class="fr-nowrap">${esc(when.format(new Date(row.at)))}</td>`
          + questions.map((q) => { const v = value(row.answers?.[q.id]); return `<td>${v ? esc(v) : '<span class="fr-muted">—</span>'}</td>`; }).join('') + '</tr>').join('')
        + '</tbody></table></div>'
      : `<div class="fr-empty">${icon('table-2', 'size-6')}<b>Belum ada respons</b><span>Respons akan tampil di sini setelah form dikirim.</span></div>`;
    return;
  }

  /* ====================================================================== */
  /* Fill-in page                                                            */
  /* ====================================================================== */
  const el = $('[data-fill-form]');
  const name = (q) => `q-${q.id}`;
  const req = (q) => (q.required ? '<span class="gf-req" aria-hidden="true">*</span>' : '');
  const help = (q) => (q.help ? `<p class="gf-help" id="${name(q)}-help">${esc(q.help)}</p>` : '');
  const image = (q) => (q.image && validUrl(q.image) ? `<img class="fd-qimg" src="${esc(q.image)}" alt="" referrerpolicy="no-referrer" loading="lazy">` : '');
  const describedBy = (q) => (q.help ? ` aria-describedby="${name(q)}-help"` : '');
  const aria = (q) => `${describedBy(q)}${q.required ? ' aria-required="true"' : ''}`;
  const chips = (q) => (q.chips?.length
    ? `<div class="gf-chips"><span class="gf-chips-label">Rekomendasi Cepat:</span>${q.chips.map((chip) => `<button type="button" class="gf-chip" data-chip="${esc(chip.replace(/^\+\s*/, ''))}" data-chip-for="${name(q)}" data-chip-mode="${q.type === 'paragraph' ? 'append' : 'set'}">${esc(chip)}</button>`).join('')}</div>`
    : '');
  const choice = (q) => {
    const type = q.type === 'checkbox' ? 'checkbox' : 'radio';
    return '<div class="gf-options">'
      + (q.options ?? []).map((option) => `<label class="gf-option"><input type="${type}" name="${name(q)}" value="${esc(option)}"><span class="gf-option-text"><span class="gf-option-label">${esc(option)}</span></span></label>`).join('')
      + (q.other ? `<label class="gf-option"><input type="${type}" name="${name(q)}" value="__other" data-other-toggle><span class="gf-option-text"><span class="gf-option-label">Lainnya:</span><input class="gf-input gf-option-other" name="${name(q)}-other" aria-label="Jawaban lainnya untuk ${esc(q.title)}" disabled></span></label>` : '')
      + '</div>';
  };
  const scale = (q) => {
    const values = Array.from({ length: (+q.max || 5) - (+q.min || 0) + 1 }, (_, k) => (+q.min || 0) + k);
    return `<div class="fd-scale">${q.minLabel ? `<span class="fd-scale__end">${esc(q.minLabel)}</span>` : ''}<div class="fd-scale__options">`
      + values.map((v) => `<label class="fd-scale__option"><span>${v}</span><input type="radio" name="${name(q)}" value="${v}" aria-label="${v}${v === +q.min && q.minLabel ? ` (${esc(q.minLabel)})` : ''}${v === +q.max && q.maxLabel ? ` (${esc(q.maxLabel)})` : ''}"></label>`).join('')
      + `</div>${q.maxLabel ? `<span class="fd-scale__end">${esc(q.maxLabel)}</span>` : ''}</div>`;
  };
  const rating = (q) => `<div class="fd-rating" data-rating>${Array.from({ length: +q.max || 5 }, (_, k) => k + 1)
    .map((v) => `<label class="fd-star"><input type="radio" name="${name(q)}" value="${v}" class="sr-only"><span class="sr-only">${v} bintang</span>${icon('star', 'size-7')}</label>`).join('')}</div>`;
  const body = (q) => {
    switch (q.type) {
      case 'short': return `${chips(q)}<input class="gf-input" id="${name(q)}" name="${name(q)}" autocomplete="off" placeholder="Jawaban Anda"${aria(q)}>`;
      case 'paragraph': return `${chips(q)}<textarea class="gf-input" id="${name(q)}" name="${name(q)}" rows="3" placeholder="Jawaban Anda"${aria(q)}></textarea>`;
      case 'radio': case 'checkbox': return choice(q);
      case 'select': return `<select class="gf-select" id="${name(q)}" name="${name(q)}"${aria(q)}><option value="">Pilih</option>${(q.options ?? []).map((option) => `<option value="${esc(option)}">${esc(option)}</option>`).join('')}</select>`;
      // A text field with the decimal keypad: "21,5" and "21.5" are both accepted (a number field drops the comma).
      case 'number': return `<input class="gf-input fd-short" type="text" inputmode="decimal" autocomplete="off" id="${name(q)}" name="${name(q)}" placeholder="Jawaban Anda"${aria(q)}>`;
      case 'date': return `<input class="gf-input fd-short" type="date" id="${name(q)}" name="${name(q)}"${aria(q)}>`;
      case 'time': return `<input class="gf-input fd-short" type="time" id="${name(q)}" name="${name(q)}"${aria(q)}>`;
      case 'scale': return scale(q);
      case 'rating': return rating(q);
      case 'file': return `<label class="gf-drop" for="${name(q)}"><span class="gf-drop-icon">${icon('file-up', 'size-6')}</span>`
        + '<span class="gf-drop-text">Pilih file bukti dari perangkat</span>'
        + `<span class="gf-drop-note">Mendukung format: PDF, JPG, PNG (maksimal ${esc(q.maxMb || 10)} MB). File tidak diunggah ke server pada data contoh.</span>`
        + `<span class="gf-file-btn">${icon('plus', 'size-4')}Tambah berkas</span>`
        + `<input class="sr-only" type="file" id="${name(q)}" name="${name(q)}" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" data-max-mb="${esc(q.maxMb || 10)}"${aria(q)}></label>`
        + `<p class="gf-file-name" data-file-name hidden>${icon('check', 'size-4')}<span></span></p>`;
      default: return '';
    }
  };
  // Single controls get a real <label>; groups (options, scale, rating) a fieldset with a legend.
  const GROUPS = ['radio', 'checkbox', 'scale', 'rating'];
  const questionCard = (q) => {
    const title = `<span>${esc(q.title)}</span>${req(q)}`;
    if (GROUPS.includes(q.type)) {
      return `<fieldset class="gf-card" data-question="${esc(q.id)}"${describedBy(q)}><legend class="sr-only">${esc(q.title)}${q.required ? ' (wajib diisi)' : ''}</legend>`
        + `<div class="gf-q-head"><span class="gf-q-title" aria-hidden="true">${title}</span></div>${help(q)}${image(q)}<div class="gf-body">${body(q)}</div></fieldset>`;
    }
    return `<div class="gf-card" data-question="${esc(q.id)}"><div class="gf-q-head"><label class="gf-q-title" for="${name(q)}">${title}</label></div>${help(q)}${image(q)}<div class="gf-body">${body(q)}</div></div>`;
  };
  const header = () => {
    const label = scheduleLabel(card.schedule);
    const meta = [['Jadwal', label.text]];
    if (card.schedule?.deadline) meta.push(['Batas pengisian', `${card.schedule.deadline} WIB`]);
    return '<section class="gf-card gf-card--header" aria-labelledby="fill-title"><p class="gf-eyebrow">RS AWAL BROS GROUP • IT BOTANIA</p>'
      + `<h1 class="gf-title" id="fill-title">${esc(form.title || card.title)}</h1>${form.description ? `<p class="gf-desc">${esc(form.description)}</p>` : ''}`
      + `<div class="gf-meta">${meta.map(([k, v]) => `<div class="min-w-0"><span class="gf-meta-label">${esc(k)}</span><div class="gf-meta-value">${esc(v)}</div></div>`).join('')}</div>`
      + `<div class="gf-header-foot">${questions.some((q) => q.required) ? '<span class="gf-required-note">* Wajib diisi</span>' : '<span></span>'}`
      + `<div class="gf-links"><a class="gf-rekap" href="${esc(responsesUrl)}" aria-label="${esc(`Lihat rekap ${form.title || card.title}`)}">${icon('table-2', 'gf-rekap__icon')}Lihat Rekap</a></div></div></section>`;
  };
  const render = () => {
    let sectionNo = 0;
    const items = (form.questions ?? []).map((q) => {
      if (q.type === 'section') {
        sectionNo++;
        return `<div class="gf-card gf-section"><div class="gf-section-head"><span class="gf-section-num" aria-hidden="true">${sectionNo}</span>`
          + `<div class="gf-section-text"><h2 class="gf-section-title">${esc(q.title)}</h2>${q.help ? `<p class="gf-section-desc">${esc(q.help)}</p>` : ''}</div>`
          + `${q.chip ? `<span class="gf-pill">${esc(q.chip)}</span>` : ''}</div></div>`;
      }
      if (q.type === 'title') return `<div class="gf-card fd-title"><h2>${esc(q.title)}</h2>${q.help ? `<p class="gf-help">${esc(q.help)}</p>` : ''}</div>`;
      if (q.type === 'image') return q.image && validUrl(q.image) ? `<figure class="gf-card fd-figure"><img src="${esc(q.image)}" alt="${esc(q.title)}" referrerpolicy="no-referrer" loading="lazy">${q.title ? `<figcaption>${esc(q.title)}</figcaption>` : ''}</figure>` : '';
      return questionCard(q);
    }).join('');
    const empty = questions.length ? '' : `<div class="gf-card fd-empty">${icon('list-checks', 'size-6')}<b>Form ini belum memiliki pertanyaan</b><span>Tambahkan pertanyaan lewat menu ⋯ &gt; Edit Isi Form di dashboard.</span></div>`;
    el.innerHTML = header() + items + empty
      + `<div class="gf-actions"><a class="gf-btn gf-btn--outline" href="${esc(page.dataset.dashboardUrl)}">${icon('arrow-left', 'size-4')}<span>Kembali ke Portal Terpadu</span></a>`
      + `<div class="gf-actions-end"><button type="reset" class="gf-btn gf-btn--text">Kosongkan form</button><button type="submit" class="gf-btn gf-btn--primary"${questions.length ? '' : ' disabled'}>${icon('send', 'size-4')}<span>Kirim</span></button></div></div>`;
    el.hidden = false;
  };
  render();

  /* ---------- Interaction ---------- */
  const cardOf = (q) => $(`[data-question="${CSS.escape(q.id)}"]`, el);
  const clearError = (box) => {
    box.classList.remove('is-invalid');
    $('.gf-error', box)?.remove();
    $$('[aria-invalid]', box).forEach((input) => input.removeAttribute('aria-invalid'));
  };
  const setError = (q, message) => {
    const box = cardOf(q);
    clearError(box);
    box.classList.add('is-invalid');
    const p = document.createElement('p');
    p.className = 'gf-error';
    p.id = `${name(q)}-error`;
    p.innerHTML = icon('circle-alert', 'size-4');
    p.append(message);
    box.append(p);
    $$('input, select, textarea', box).forEach((input) => {
      input.setAttribute('aria-invalid', 'true');
      input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), p.id].filter(Boolean).join(' '));
    });
  };
  el.addEventListener('click', (event) => {
    const chip = event.target.closest('[data-chip]');
    if (!chip) return;
    const input = document.getElementById(chip.dataset.chipFor);
    input.value = chip.dataset.chipMode === 'append' && input.value.trim() ? `${input.value.trim()}\n${chip.dataset.chip}` : chip.dataset.chip;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.focus();
  });
  el.addEventListener('change', (event) => {
    const input = event.target;
    // "Lainnya" turns its text box on
    if (input.matches('input[type="radio"], input[type="checkbox"]')) {
      const group = input.name;
      const toggle = $(`[name="${CSS.escape(group)}"][data-other-toggle]`, el);
      if (toggle) {
        const text = $(`[name="${CSS.escape(`${group}-other`)}"]`, el);
        text.disabled = !toggle.checked;
        if (toggle.checked && input === toggle) text.focus();
      }
    }
    const stars = input.closest('[data-rating]');
    if (stars) $$('.fd-star', stars).forEach((star, i) => star.classList.toggle('is-on', i < +input.value));
    if (input.type === 'file') {
      const out = $('[data-file-name]', input.closest('.gf-body'));
      const file = input.files[0];
      out.hidden = !file;
      if (file) $('span', out).textContent = `${file.name} (${(file.size / 1048576).toFixed(1)} MB)`;
    }
    const box = input.closest('.gf-card.is-invalid');
    if (box) clearError(box);
  });
  el.addEventListener('input', (event) => { const box = event.target.closest('.gf-card.is-invalid'); if (box) clearError(box); });
  el.addEventListener('reset', () => window.setTimeout(() => {
    $$('.gf-card.is-invalid', el).forEach(clearError);
    $$('[data-file-name]', el).forEach((out) => { out.hidden = true; });
    $$('.fd-star.is-on', el).forEach((star) => star.classList.remove('is-on'));
    $$('.gf-option-other', el).forEach((text) => { text.disabled = true; });
  }, 0));

  /* ---------- Validation and sending ---------- */
  const answerOf = (q) => {
    const fields = el.elements;
    if (q.type === 'checkbox') {
      const picked = $$(`[name="${CSS.escape(name(q))}"]:checked`, el).map((input) => input.value);
      const other = fields.namedItem(`${name(q)}-other`)?.value.trim() ?? '';
      return { value: picked.map((v) => (v === '__other' ? other : v)).filter(Boolean), otherMissing: picked.includes('__other') && !other };
    }
    if (['radio', 'scale', 'rating'].includes(q.type)) {
      const picked = $(`[name="${CSS.escape(name(q))}"]:checked`, el)?.value ?? '';
      const other = fields.namedItem(`${name(q)}-other`)?.value.trim() ?? '';
      return { value: picked === '__other' ? other : picked, otherMissing: picked === '__other' && !other };
    }
    if (q.type === 'file') {
      const file = fields.namedItem(name(q)).files[0];
      return { value: file ? file.name : '', tooBig: file && file.size > (+q.maxMb || 10) * 1048576 };
    }
    return { value: fields.namedItem(name(q)).value.trim() };
  };
  el.addEventListener('submit', (event) => {
    event.preventDefault();
    const answers = {};
    let first = null;
    questions.forEach((q) => {
      const { value, otherMissing, tooBig } = answerOf(q);
      const empty = Array.isArray(value) ? value.length === 0 : value === '';
      let message = '';
      if (otherMissing) message = 'Tuliskan jawaban untuk pilihan Lainnya.';
      else if (tooBig) message = `Ukuran file melebihi ${q.maxMb || 10} MB.`;
      else if (q.required && empty) message = 'Pertanyaan ini wajib diisi.';
      else if (q.type === 'number' && !empty && !/^-?\d+([.,]\d+)?$/.test(value)) message = 'Isi dengan angka, mis. 21,5.';
      if (message) { setError(q, message); first ??= q; } else { clearError(cardOf(q)); answers[q.id] = q.type === 'number' && !empty ? Number(value.replace(',', '.')) : value; }
    });
    if (first) {
      const box = cardOf(first);
      box.scrollIntoView({ block: 'center' });
      $('input:not([disabled]), select, textarea', box)?.focus({ preventScroll: true });
      showToast('Periksa jawaban', 'Masih ada pertanyaan yang belum diisi dengan benar.', 'error');
      return;
    }
    (state.responses[formId] ??= []).push({ id: uid('respons'), at: new Date().toISOString(), answers });
    // The dashboard card now has a report and counts as done today.
    card.done_today = true;
    card.has_data = true;
    card.rekap = responsesUrl;
    store.save(state);
    el.reset();
    const dialog = $('#fill-success');
    const text = $('[data-fill-success-text]', dialog);
    const title = document.createElement('b');
    title.textContent = form.title || card.title;
    text.replaceChildren('Jawaban form ', title, ' tersimpan di rekap (data contoh di tab browser ini).');
    $('[data-fill-success-rekap]', dialog).href = responsesUrl;
    dialog.showModal();
    $('[data-fill-success-rekap]', dialog).focus();
  });
})();
