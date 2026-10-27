(() => {
  'use strict';

  /* "Edit Isi Form" (app/Views/form-editor/index.php), frontend only. The form definition lives in the
     shared browser state (dashboard-store.js: dummy data + sessionStorage). The page edits a working
     copy and writes it back on "Simpan Form"; nothing is sent to the server. Tab Pertanyaan: question
     cards (11 answer types), required toggle, image (by URL), "Bagian", titles, images and the
     "Rekomendasi Cepat" chips of text answers. Tab Jadwal follows in the next milestone. */
  const store = window.DashboardStore;
  const page = document.querySelector('[data-form-editor]');
  if (!store?.ready || !page) return;
  const { $, $$, esc, uid, clone, icon, showToast, scheduleLabel, scheduleSummary, MONTHS, MONTHS_LONG, DAY_SHORT, DAY_LONG, nowWib, ymd, parseYmd, isDue } = store;

  const TYPES = [
    ['short', 'Jawaban singkat', 'align-left'], ['paragraph', 'Paragraf', 'text'],
    ['radio', 'Pilihan ganda', 'circle-dot'], ['checkbox', 'Kotak centang', 'square-check-big'], ['select', 'Drop-down', 'chevron-down'],
    ['file', 'Upload file', 'file-up'], ['date', 'Tanggal', 'calendar'], ['time', 'Waktu', 'clock'],
    ['scale', 'Skala linier', 'sliders-horizontal'], ['number', 'Angka', 'hash'], ['rating', 'Rating', 'star'],
  ];
  const CHOICE = ['radio', 'checkbox', 'select'];
  const TEXT = ['short', 'paragraph'];
  const BLOCKS = ['section', 'title', 'image']; // items without an answer
  const validUrl = (value) => { try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && url.hostname !== ''; } catch { return false; } };

  const state = store.load();
  const formId = page.dataset.formId;
  const card = state.cards.find((item) => item.id === formId && item.kind !== 'link');
  if (!card) { $('[data-fe-missing]').hidden = false; return; }
  const builtin = card.kind === 'builtin';
  state.forms ??= {};
  const original = state.forms[formId] ?? { title: card.title, description: '', questions: [] };

  /* ---------- Items ---------- */
  // An item of another type keeps its text, help, image and required mark, plus options/chips where they fit.
  const retype = (q, type) => {
    const next = { id: q.id ?? uid('q'), type, title: q.title ?? '', help: q.help ?? '', required: Boolean(q.required) };
    if (q.image) next.image = q.image;
    if (CHOICE.includes(type)) {
      next.options = q.options?.length ? [...q.options] : ['Opsi 1'];
      if (type !== 'select' && q.other) next.other = true;
    }
    if (TEXT.includes(type) && q.chips?.length) next.chips = [...q.chips];
    if (type === 'scale') Object.assign(next, { min: q.min === 0 ? 0 : 1, max: Math.min(10, Math.max(2, +q.max || 5)), minLabel: q.minLabel ?? '', maxLabel: q.maxLabel ?? '' });
    if (type === 'rating') next.max = Math.min(10, Math.max(3, +q.max || 5));
    if (type === 'file') next.maxMb = Math.min(10, Math.max(1, +q.maxMb || 10));
    return next;
  };
  const block = (q) => ({ id: q.id ?? uid(q.type), type: q.type, title: q.title ?? '', help: q.help ?? '', ...(q.type === 'section' ? { chip: q.chip ?? '' } : {}), ...(q.image ? { image: q.image } : {}) });
  const normalize = (form) => ({
    title: form.title ?? card.title,
    description: form.description ?? '',
    questions: (form.questions ?? []).map((q) => (BLOCKS.includes(q.type) ? block(q) : retype(q, TYPES.some((t) => t[0] === q.type) ? q.type : 'short'))),
  });
  let F = normalize(clone(original));
  // Working copy of the card's schedule (tab Jadwal); written back on "Simpan Form".
  const scheduleOf = (s) => ({ type: 'daily', days: [], month_day: 1, year_md: '', dates: [], start: '', end: '', deadline: '', reminder: false, ...clone(s ?? {}) });
  let SCH = scheduleOf(card.schedule);
  let dirty = false;
  let active = -1;

  /* ---------- Drawing ---------- */
  const list = $('[data-fe-list]');
  const isQuestion = (q) => !BLOCKS.includes(q.type);
  const typeSelect = (q, i) => `<label class="sr-only" for="q${i}-type">Tipe pertanyaan</label><select id="q${i}-type" class="dash-input fe-type" data-field="type">`
    + TYPES.map(([value, label]) => `<option value="${value}"${q.type === value ? ' selected' : ''}>${label}</option>`).join('') + '</select>';
  const foot = (q, i, n, label) => `<div class="fe-foot">`
    + `<button type="button" class="fe-icon" data-act="up" aria-label="Pindah ${esc(label)} ke atas" title="Pindah ke atas"${i === 0 ? ' disabled' : ''}>${icon('arrow-up', 'size-4')}</button>`
    + `<button type="button" class="fe-icon" data-act="down" aria-label="Pindah ${esc(label)} ke bawah" title="Pindah ke bawah"${i === n - 1 ? ' disabled' : ''}>${icon('arrow-down', 'size-4')}</button>`
    + '<span class="fe-sep" aria-hidden="true"></span>'
    + `<button type="button" class="fe-icon" data-act="copy" aria-label="Duplikat ${esc(label)}" title="Duplikat">${icon('copy', 'size-4')}</button>`
    + `<button type="button" class="fe-icon fe-icon--danger" data-act="delete" aria-label="Hapus ${esc(label)}" title="Hapus">${icon('trash-2', 'size-4')}</button>`
    + (isQuestion(q) ? `<span class="fe-sep" aria-hidden="true"></span><label class="fe-switch"><input type="checkbox" data-field="required"${q.required ? ' checked' : ''}><span class="fe-switch__track" aria-hidden="true"></span><span>Wajib diisi</span></label>` : '')
    + '</div>';
  const imageBlock = (q, i, standalone = false) => {
    if (!standalone && q.image === undefined) return '';
    const value = q.image ?? '';
    return `<div class="fe-image"><label class="fe-label" for="q${i}-image">URL gambar</label>`
      + `<div class="fe-image__row"><input id="q${i}-image" class="dash-input" type="url" inputmode="url" data-field="image" value="${esc(value)}" placeholder="https://.../gambar.png" autocomplete="off">`
      + (standalone ? '' : `<button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-act="image-remove">${icon('trash-2', 'size-4')}Hapus gambar</button>`) + '</div>'
      + `<img class="fe-image__preview" alt="" referrerpolicy="no-referrer"${validUrl(value) ? ` src="${esc(value)}"` : ' hidden'}>`
      + '<p class="fe-help" data-image-error hidden>Gambar tidak dapat dimuat dari alamat ini.</p></div>';
  };
  const mark = (q, j) => `<span class="fe-mark fe-mark--${q.type}" aria-hidden="true">${q.type === 'select' ? `${j + 1}.` : ''}</span>`;
  const options = (q) => '<div class="fe-options">'
    + q.options.map((option, j) => `<div class="fe-option">${mark(q, j)}<input class="fe-line" data-option="${j}" value="${esc(option)}" aria-label="Opsi ${j + 1}"><button type="button" class="fe-icon" data-act="option-remove" data-option-index="${j}" aria-label="Hapus opsi ${j + 1}"${q.options.length === 1 ? ' disabled' : ''}>${icon('x', 'size-4')}</button></div>`).join('')
    + (q.other ? `<div class="fe-option">${mark(q, q.options.length)}<span class="fe-other">Lainnya…</span><button type="button" class="fe-icon" data-act="other-remove" aria-label="Hapus opsi Lainnya">${icon('x', 'size-4')}</button></div>` : '')
    + `<div class="fe-option fe-option--add">${mark(q, q.options.length)}<button type="button" class="fe-link" data-act="option-add">Tambah opsi</button>`
    + (q.type !== 'select' && !q.other ? `<span class="fe-or">atau</span><button type="button" class="fe-link fe-link--teal" data-act="other-add">Tambah opsi 'Lainnya'</button>` : '') + '</div></div>';
  const chips = (q) => (q.chips
    ? `<div class="fe-chips"><div class="fe-chips__head"><span>Rekomendasi Cepat</span><button type="button" class="fe-link" data-act="chips-off">Hapus baris</button></div><div class="fe-chips__list">`
      + q.chips.map((chip, j) => `<span class="fe-chip"><input data-chip="${j}" value="${esc(chip)}" size="${Math.max(6, chip.length)}" aria-label="Rekomendasi ${j + 1}"><button type="button" data-act="chip-remove" data-chip-index="${j}" aria-label="Hapus rekomendasi ${j + 1}">${icon('x', 'size-3.5')}</button></span>`).join('')
      + `<button type="button" class="fe-chip-add" data-act="chip-add">${icon('plus', 'size-3.5')}Tambah</button></div></div>`
    : `<button type="button" class="fe-link fe-link--teal fe-chips-on" data-act="chips-on">${icon('plus', 'size-4')}Rekomendasi Cepat</button>`);
  const range = (from, to, value, field, label) => `<select class="dash-input fe-small" data-field="${field}" aria-label="${label}">`
    + Array.from({ length: to - from + 1 }, (_, k) => from + k).map((v) => `<option value="${v}"${+value === v ? ' selected' : ''}>${v}</option>`).join('') + '</select>';
  const answer = (q, i) => {
    switch (q.type) {
      case 'radio': case 'checkbox': case 'select': return options(q);
      case 'short': return `${chips(q)}<div class="fe-answer">Teks jawaban singkat</div>`;
      case 'paragraph': return `${chips(q)}<div class="fe-answer fe-answer--long">Teks jawaban panjang</div>`;
      case 'number': return '<div class="fe-answer fe-answer--short">Angka</div>';
      case 'date': return `<div class="fe-answer fe-answer--short">${icon('calendar', 'size-4')}Hari, bulan, tahun</div>`;
      case 'time': return `<div class="fe-answer fe-answer--short">${icon('clock', 'size-4')}Waktu</div>`;
      case 'file': return `<div class="fe-file">${icon('file-up', 'size-5')}<span>Responden mengunggah 1 file (PDF, JPG, PNG). Maks <label class="sr-only" for="q${i}-mb">Ukuran maksimal file (MB)</label><input id="q${i}-mb" class="dash-input fe-small fe-number" type="number" min="1" max="10" data-field="maxMb" value="${esc(q.maxMb)}"> MB.</span></div>`;
      case 'scale': return `<div class="fe-scale">${range(0, 1, q.min, 'min', 'Nilai terendah')}<span>sampai</span>${range(2, 10, q.max, 'max', 'Nilai tertinggi')}</div>`
        + `<div class="fe-scale-labels"><label><span>${esc(q.min)}</span><input class="fe-line" data-field="minLabel" value="${esc(q.minLabel)}" placeholder="Label (opsional)"></label>`
        + `<label><span>${esc(q.max)}</span><input class="fe-line" data-field="maxLabel" value="${esc(q.maxLabel)}" placeholder="Label (opsional)"></label></div>`;
      case 'rating': return `<div class="fe-scale"><span>Jumlah bintang</span>${range(3, 10, q.max, 'max', 'Jumlah bintang')}<span class="fe-stars" aria-hidden="true">${icon('star', 'size-5').repeat(+q.max || 5)}</span></div>`;
      default: return '';
    }
  };
  const item = (q, i, n, sectionNo, questionNo) => {
    const on = i === active ? ' is-active' : '';
    if (q.type === 'section') {
      const label = `bagian ${sectionNo}`;
      return `<section class="fe-card fe-section${on}" data-index="${i}" aria-label="Bagian ${sectionNo}">`
        + `<div class="fe-section__row"><span class="fe-section__no" aria-hidden="true">${sectionNo}</span><div class="fe-section__text">`
        + `<input class="fe-section__title" data-field="title" value="${esc(q.title)}" placeholder="JUDUL BAGIAN" aria-label="Judul bagian ${sectionNo}">`
        + `<input class="fe-line" data-field="help" value="${esc(q.help)}" placeholder="Deskripsi singkat bagian" aria-label="Deskripsi bagian ${sectionNo}"></div>`
        + `<input class="fe-section__chip" data-field="chip" value="${esc(q.chip)}" placeholder="LABEL" size="12" aria-label="Label kecil di kanan bagian ${sectionNo}"></div>`
        + `<p class="fe-help">Pertanyaan di bawah bagian ini masuk ke kelompok ${sectionNo}.</p>${foot(q, i, n, label)}</section>`;
    }
    if (q.type === 'title') {
      return `<section class="fe-card fe-q${on}" data-index="${i}" aria-label="Judul">`
        + `<input class="fe-q__title" data-field="title" value="${esc(q.title)}" placeholder="Judul" aria-label="Judul">`
        + `<input class="fe-line" data-field="help" value="${esc(q.help)}" placeholder="Deskripsi (opsional)" aria-label="Deskripsi judul">${foot(q, i, n, 'judul')}</section>`;
    }
    if (q.type === 'image') {
      return `<section class="fe-card fe-q${on}" data-index="${i}" aria-label="Gambar">`
        + `<input class="fe-q__title" data-field="title" value="${esc(q.title)}" placeholder="Judul gambar (opsional)" aria-label="Judul gambar">`
        + `${imageBlock(q, i, true)}${foot(q, i, n, 'gambar')}</section>`;
    }
    return `<section class="fe-card fe-q${on}" data-index="${i}" aria-label="Pertanyaan ${questionNo}">`
      + `<div class="fe-q__top"><input class="fe-q__title" data-field="title" value="${esc(q.title)}" placeholder="Pertanyaan" aria-label="Teks pertanyaan ${questionNo}">`
      + `<button type="button" class="fe-icon" data-act="image-add" aria-label="Tambah gambar ke pertanyaan ${questionNo}" title="Tambah gambar"${q.image !== undefined ? ' hidden' : ''}>${icon('image', 'size-5')}</button>${typeSelect(q, i)}</div>`
      + `<input class="fe-line fe-q__help" data-field="help" value="${esc(q.help)}" placeholder="Deskripsi (opsional)" aria-label="Deskripsi pertanyaan ${questionNo}">`
      + `${imageBlock(q, i)}<div class="fe-q__answer">${answer(q, i)}</div>${foot(q, i, n, `pertanyaan ${questionNo}`)}</section>`;
  };
  const headCard = () => '<section class="fe-card fe-head" aria-label="Judul dan keterangan form">'
    + `<label class="sr-only" for="fe-title">Judul form</label><input id="fe-title" class="fe-head__title" data-head="title" value="${esc(F.title)}" placeholder="Judul form" maxlength="150">`
    + `<label class="sr-only" for="fe-desc">Keterangan di Halaman Form</label><textarea id="fe-desc" class="fe-line fe-head__desc" data-head="description" rows="2" maxlength="500" placeholder="Keterangan di Halaman Form">${esc(F.description)}</textarea></section>`;
  const status = () => {
    const n = F.questions.filter(isQuestion).length;
    $('[data-fe-status]').textContent = `${builtin ? 'Form bawaan' : `${n} pertanyaan`} · Jadwal: ${scheduleLabel(SCH).text} · ${dirty ? 'Belum disimpan' : 'Tersimpan'}`;
    $('[data-fe-heading]').textContent = F.title.trim() || 'Form tanpa judul';
  };
  const render = (focus = null) => {
    let sectionNo = 0;
    let questionNo = 0;
    const n = F.questions.length;
    list.innerHTML = headCard() + F.questions.map((q, i) => {
      if (q.type === 'section') sectionNo++;
      if (isQuestion(q)) questionNo++;
      return item(q, i, n, sectionNo, questionNo);
    }).join('') + (n ? '' : `<div class="fe-card fe-empty">${icon('list-checks', 'size-6 text-slate-400')}<b>Belum ada pertanyaan</b><span>Gunakan tombol Tambah Pertanyaan untuk mulai menyusun form.</span></div>`);
    $$('img.fe-image__preview', list).forEach(watchImage);
    status();
    if (focus) {
      const target = $(focus, list);
      target?.focus();
      target?.closest('.fe-card')?.scrollIntoView({ block: 'nearest' });
    }
  };
  const watchImage = (img) => {
    const error = img.parentElement.querySelector('[data-image-error]');
    img.addEventListener('load', () => { error.hidden = true; });
    img.addEventListener('error', () => { error.hidden = false; img.hidden = true; });
  };
  const changed = () => { dirty = true; status(); };
  const at = (el) => +el.closest('[data-index]')?.dataset.index;
  const focusOf = (i, selector = '[data-field="title"]') => `[data-index="${i}"] ${selector}`;

  /* ---------- Editing ---------- */
  list.addEventListener('input', (event) => {
    const el = event.target;
    if (el.dataset.head) { F[el.dataset.head] = el.value; changed(); return; }
    const q = F.questions[at(el)];
    if (!q) return;
    if (el.dataset.option !== undefined) q.options[+el.dataset.option] = el.value;
    else if (el.dataset.chip !== undefined) { q.chips[+el.dataset.chip] = el.value; el.size = Math.max(6, el.value.length); }
    else if (['title', 'help', 'chip', 'minLabel', 'maxLabel'].includes(el.dataset.field)) q[el.dataset.field] = el.value;
    else if (el.dataset.field === 'image') {
      q.image = el.value.trim();
      const img = el.closest('.fe-image').querySelector('.fe-image__preview');
      el.closest('.fe-image').querySelector('[data-image-error]').hidden = true;
      if (validUrl(q.image)) { img.hidden = false; img.src = q.image; } else { img.hidden = true; img.removeAttribute('src'); }
    } else if (el.dataset.field === 'maxMb') q.maxMb = Math.min(10, Math.max(1, Math.round(+el.value) || 1));
    else return;
    changed();
  });
  list.addEventListener('change', (event) => {
    const el = event.target;
    const i = at(el);
    const q = F.questions[i];
    if (!q) return;
    if (el.dataset.field === 'type') { F.questions[i] = retype(q, el.value); changed(); render(focusOf(i, '[data-field="type"]')); }
    else if (el.dataset.field === 'required') { q.required = el.checked; changed(); }
    else if (['min', 'max'].includes(el.dataset.field)) { q[el.dataset.field] = +el.value; changed(); render(focusOf(i, `[data-field="${el.dataset.field}"]`)); }
    else if (el.dataset.field === 'maxMb') el.value = q.maxMb;
  });
  list.addEventListener('focusin', (event) => {
    const i = at(event.target);
    if (Number.isNaN(i) || i === active) return;
    active = i;
    $$('.fe-card.is-active', list).forEach((el) => el.classList.remove('is-active'));
    event.target.closest('.fe-card')?.classList.add('is-active');
  });
  list.addEventListener('click', (event) => {
    const button = event.target.closest('[data-act]');
    if (!button || button.disabled) return;
    const i = at(button);
    const q = F.questions[i];
    if (!q) return;
    const act = button.dataset.act;
    let focus = null;
    if (act === 'up' || act === 'down') {
      const to = act === 'up' ? i - 1 : i + 1;
      [F.questions[i], F.questions[to]] = [F.questions[to], F.questions[i]];
      active = to;
      focus = focusOf(to, `[data-act="${act}"]:not([disabled])`);
      if (to === 0 || to === F.questions.length - 1) focus = focusOf(to, '[data-field="title"]');
    } else if (act === 'copy') {
      F.questions.splice(i + 1, 0, { ...clone(q), id: uid(q.type === 'section' ? 'section' : 'q') });
      active = i + 1;
      focus = focusOf(i + 1);
    } else if (act === 'delete') {
      F.questions.splice(i, 1);
      active = Math.min(i, F.questions.length - 1);
      focus = active >= 0 ? focusOf(active) : '#fe-title';
      showToast('Item dihapus', 'Simpan Form untuk menerapkan perubahan.', 'info');
    } else if (act === 'option-add') { q.options.push(`Opsi ${q.options.length + 1}`); focus = focusOf(i, `[data-option="${q.options.length - 1}"]`); }
    else if (act === 'option-remove') { q.options.splice(+button.dataset.optionIndex, 1); focus = focusOf(i, `[data-option="${Math.max(0, +button.dataset.optionIndex - 1)}"]`); }
    else if (act === 'other-add') { q.other = true; focus = focusOf(i, '[data-act="other-remove"]'); }
    else if (act === 'other-remove') { delete q.other; focus = focusOf(i, '[data-act="option-add"]'); }
    else if (act === 'chips-on') { q.chips = ['Rekomendasi']; focus = focusOf(i, '[data-chip="0"]'); }
    else if (act === 'chips-off') { delete q.chips; focus = focusOf(i, '[data-act="chips-on"]'); }
    else if (act === 'chip-add') { q.chips.push(''); focus = focusOf(i, `[data-chip="${q.chips.length - 1}"]`); }
    else if (act === 'chip-remove') {
      q.chips.splice(+button.dataset.chipIndex, 1);
      if (!q.chips.length) delete q.chips;
      focus = focusOf(i, q.chips ? '[data-act="chip-add"]' : '[data-act="chips-on"]');
    } else if (act === 'image-add') { q.image = ''; focus = focusOf(i, '[data-field="image"]'); }
    else if (act === 'image-remove') { delete q.image; focus = focusOf(i, '[data-act="image-add"]'); }
    else return;
    changed();
    render(focus);
  });

  /* Toolbar: a new item goes below the selected one (or at the end). */
  $('.fe-tools').addEventListener('click', (event) => {
    const tool = event.target.closest('[data-tool]')?.dataset.tool;
    if (!tool) return;
    const added = tool === 'question' ? retype({ id: uid('q') }, 'short') : block({ type: tool, id: uid(tool) });
    if (tool === 'image') added.image = '';
    const index = active >= 0 && active < F.questions.length ? active + 1 : F.questions.length;
    F.questions.splice(index, 0, added);
    active = index;
    changed();
    render(focusOf(index, tool === 'image' ? '[data-field="image"]' : '[data-field="title"]'));
  });

  /* ---------- Tabs ---------- */
  const tabs = $$('[data-fe-tab]');
  const setTab = (name, focus = false) => tabs.forEach((tab) => {
    const on = tab.dataset.feTab === name;
    tab.setAttribute('aria-selected', String(on));
    tab.tabIndex = on ? 0 : -1;
    document.getElementById(tab.getAttribute('aria-controls')).hidden = !on;
    if (on && focus) tab.focus();
  });
  tabs.forEach((tab) => tab.addEventListener('click', () => setTab(tab.dataset.feTab)));
  $('.fe-tabs').addEventListener('keydown', (event) => {
    if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
    event.preventDefault();
    const visible = tabs.filter((tab) => !tab.hidden);
    const i = visible.findIndex((tab) => tab.getAttribute('aria-selected') === 'true');
    setTab(visible[(i + (event.key === 'ArrowRight' ? 1 : visible.length - 1)) % visible.length].dataset.feTab, true);
  });

  /* ---------- Tab Jadwal ---------- */
  const PATTERNS = [['daily', 'Setiap hari', 'calendar-days'], ['weekly', 'Mingguan', 'calendar-range'], ['monthly', 'Bulanan', 'calendar'], ['yearly', 'Tahunan', 'calendar-check'], ['dates', 'Tanggal tertentu', 'calendar-plus']];
  const WEEK = [1, 2, 3, 4, 5, 6, 0]; // Senin first
  const schedulePanel = $('[data-fe-schedule]');
  const today = nowWib().day;
  let calMonth = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), 1));
  const numberOptions = (from, to, value, names = null) => Array.from({ length: to - from + 1 }, (_, k) => from + k)
    .map((v) => `<option value="${v}"${+value === v ? ' selected' : ''}>${names ? names[v - 1] : v}</option>`).join('');
  const detail = () => {
    if (SCH.type === 'weekly') {
      return `<div class="fe-days" role="group" aria-label="Pilih hari">${WEEK.map((d) => `<button type="button" class="fe-day" data-day="${d}" aria-pressed="${SCH.days.map(Number).includes(d)}" aria-label="${DAY_LONG[d]}">${DAY_SHORT[d]}</button>`).join('')}</div>`;
    }
    if (SCH.type === 'monthly') {
      const last = SCH.month_day === 'last';
      return '<div class="fe-radios">'
        + `<label class="fe-radio"><input type="radio" name="sch-md" value="day"${last ? '' : ' checked'}> Tanggal <select id="sch-mday" class="dash-input fe-small" aria-label="Tanggal setiap bulan"${last ? ' disabled' : ''}>${numberOptions(1, 31, last ? 1 : SCH.month_day)}</select></label>`
        + `<label class="fe-radio"><input type="radio" name="sch-md" value="last"${last ? ' checked' : ''}> Hari terakhir bulan</label></div>`
        + '<p class="fe-help">Bulan yang lebih pendek memakai tanggal terakhirnya.</p>';
    }
    if (SCH.type === 'yearly') {
      const [month, day] = SCH.year_md.split('-').map(Number);
      return `<div class="fe-inline"><select id="sch-yday" class="dash-input fe-small" aria-label="Tanggal">${numberOptions(1, 31, day)}</select>`
        + `<select id="sch-ymonth" class="dash-input fe-small" aria-label="Bulan">${numberOptions(1, 12, month, MONTHS_LONG)}</select></div>`;
    }
    if (SCH.type === 'dates') {
      return `<div class="fe-inline"><input type="date" id="sch-date" class="dash-input fe-small" aria-label="Pilih tanggal"><button type="button" class="dash-btn dash-btn--outline dash-btn--sm" data-sch="date-add">${icon('plus', 'size-4')}Tambah tanggal</button></div>`
        + `<div class="fe-date-chips">${[...SCH.dates].sort().map((d) => { const day = parseYmd(d); return `<span class="fe-date-chip">${day.getUTCDate()} ${MONTHS[day.getUTCMonth()]} ${day.getUTCFullYear()}<button type="button" data-sch="date-remove" data-date="${d}" aria-label="Hapus tanggal ${day.getUTCDate()} ${MONTHS_LONG[day.getUTCMonth()]} ${day.getUTCFullYear()}">${icon('x', 'size-3.5')}</button></span>`; }).join('')}</div>`
        + '<p class="fe-help">Bisa juga klik tanggal di kalender di bawah.</p>';
    }
    return '';
  };
  const calendar = () => {
    const y = calMonth.getUTCFullYear(), m = calMonth.getUTCMonth();
    const lead = (new Date(Date.UTC(y, m, 1)).getUTCDay() + 6) % 7;
    const days = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
    const todayKey = ymd(today);
    const pick = SCH.type === 'dates';
    let cells = '<span></span>'.repeat(lead);
    for (let d = 1; d <= days; d++) {
      const day = new Date(Date.UTC(y, m, d)), key = ymd(day), due = isDue(SCH, day);
      const cls = `fe-cal__d${due ? ' is-due' : ''}${key === todayKey ? ' is-today' : ''}`;
      const label = `${d} ${MONTHS_LONG[m]}${due ? ', terjadwal' : ''}${key === todayKey ? ', hari ini' : ''}`;
      cells += pick
        ? `<button type="button" class="${cls}" data-sch="cal" data-date="${key}" aria-pressed="${due}" aria-label="${label}">${d}</button>`
        : `<span class="${cls}" aria-label="${label}" role="img">${d}</span>`;
    }
    return `<div class="fe-cal"><div class="fe-cal__head"><button type="button" class="fe-icon" data-sch="month" data-step="-1" aria-label="Bulan sebelumnya">${icon('chevron-left', 'size-4')}</button>`
      + `<b aria-live="polite">${MONTHS_LONG[m]} ${y}</b><button type="button" class="fe-icon" data-sch="month" data-step="1" aria-label="Bulan berikutnya">${icon('chevron-right', 'size-4')}</button></div>`
      + `<div class="fe-cal__grid">${WEEK.map((d) => `<i aria-hidden="true">${DAY_SHORT[d]}</i>`).join('')}${cells}</div>`
      + '<div class="fe-cal__legend"><span><i class="is-due"></i>Jadwal tampil</span><span><i class="is-today"></i>Hari ini</span></div></div>';
  };
  const renderSchedule = (focus = null) => {
    const dl = SCH.deadline || '14:00';
    schedulePanel.innerHTML = '<section class="fe-card"><h2 class="fe-h">Kapan form ini tampil di dashboard?</h2>'
      + `<div class="fe-patterns" role="radiogroup" aria-label="Pola jadwal">${PATTERNS.map(([value, label, name]) => `<label class="fe-pattern"><input type="radio" name="sch-type" value="${value}"${SCH.type === value ? ' checked' : ''}>${icon(name, 'size-5')}<span>${label}</span></label>`).join('')}</div>`
      + (detail() ? `<div class="fe-sch-detail">${detail()}<p class="dash-error" data-sch-error hidden></p></div>` : '') + '</section>'
      + '<section class="fe-card fe-sch-grid">'
      + `<div class="dash-field"><label for="sch-start">Mulai tanggal</label><input type="date" id="sch-start" class="dash-input" value="${esc(SCH.start)}"></div>`
      + `<div class="dash-field"><label class="fe-check"><input type="checkbox" id="sch-hasend"${SCH.end ? ' checked' : ''}> Berakhir tanggal</label><input type="date" id="sch-end" class="dash-input" value="${esc(SCH.end)}" aria-label="Berakhir tanggal"${SCH.end ? '' : ' disabled'}><p class="dash-error" data-sch-end-error hidden></p></div>`
      + `<div class="dash-field"><label class="fe-check"><input type="checkbox" id="sch-hasdl"${SCH.deadline ? ' checked' : ''}> Batas waktu pengisian</label><input type="time" id="sch-dl" class="dash-input" value="${esc(dl)}" aria-label="Jam batas pengisian"${SCH.deadline ? '' : ' disabled'}></div>`
      + `<div class="dash-field"><label class="fe-switch"><input type="checkbox" id="sch-rem"${SCH.reminder ? ' checked' : ''}${SCH.deadline ? '' : ' disabled'}><span class="fe-switch__track" aria-hidden="true"></span><span>Pengingat</span></label>`
      + `<p class="fe-help">Tampilkan penanda 'Belum Diisi' jika lewat jam ${esc(dl)}.${SCH.deadline ? '' : ' Aktifkan batas waktu dulu.'}</p></div></section>`
      + `<section class="fe-card fe-sch-sum"><div class="fe-sch-sum__text">${icon('calendar-check', 'size-5')}<p aria-live="polite">${esc(scheduleSummary(SCH))}</p></div>${calendar()}</section>`;
    if (focus) $(focus, schedulePanel)?.focus();
  };
  const scheduleChanged = (focus) => { changed(); renderSchedule(focus); };
  schedulePanel.addEventListener('change', (event) => {
    const el = event.target;
    if (el.name === 'sch-type') {
      SCH.type = el.value;
      if (SCH.type === 'yearly' && !SCH.year_md) SCH.year_md = ymd(today).slice(5);
      return scheduleChanged(`input[name="sch-type"][value="${el.value}"]`);
    }
    if (el.name === 'sch-md') { SCH.month_day = el.value === 'last' ? 'last' : (+$('#sch-mday', schedulePanel).value || 1); return scheduleChanged(`input[name="sch-md"][value="${el.value}"]`); }
    if (el.id === 'sch-mday') { SCH.month_day = +el.value; return scheduleChanged('#sch-mday'); }
    if (el.id === 'sch-yday' || el.id === 'sch-ymonth') {
      const day = +$('#sch-yday', schedulePanel).value, month = +$('#sch-ymonth', schedulePanel).value;
      SCH.year_md = `${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      return scheduleChanged(`#${el.id}`);
    }
    if (el.id === 'sch-start') { SCH.start = el.value; return scheduleChanged('#sch-start'); }
    if (el.id === 'sch-hasend') { SCH.end = el.checked ? (SCH.start || ymd(today)) : ''; return scheduleChanged('#sch-hasend'); }
    if (el.id === 'sch-end') { SCH.end = el.value; return scheduleChanged('#sch-end'); }
    if (el.id === 'sch-hasdl') { SCH.deadline = el.checked ? ($('#sch-dl', schedulePanel).value || '14:00') : ''; if (!el.checked) SCH.reminder = false; return scheduleChanged('#sch-hasdl'); }
    if (el.id === 'sch-dl') { SCH.deadline = el.value; return scheduleChanged('#sch-dl'); }
    if (el.id === 'sch-rem') { SCH.reminder = el.checked; changed(); }
  });
  schedulePanel.addEventListener('click', (event) => {
    const day = event.target.closest('[data-day]');
    if (day) {
      const d = +day.dataset.day;
      SCH.days = SCH.days.map(Number).includes(d) ? SCH.days.filter((x) => +x !== d) : [...SCH.days, d];
      return scheduleChanged(`[data-day="${d}"]`);
    }
    const button = event.target.closest('[data-sch]');
    if (!button) return;
    const toggleDate = (key) => { SCH.dates = SCH.dates.includes(key) ? SCH.dates.filter((x) => x !== key) : [...SCH.dates, key].sort(); };
    if (button.dataset.sch === 'month') {
      calMonth = new Date(Date.UTC(calMonth.getUTCFullYear(), calMonth.getUTCMonth() + +button.dataset.step, 1));
      renderSchedule(`[data-sch="month"][data-step="${button.dataset.step}"]`);
    } else if (button.dataset.sch === 'cal') { toggleDate(button.dataset.date); scheduleChanged(`[data-sch="cal"][data-date="${button.dataset.date}"]`); }
    else if (button.dataset.sch === 'date-remove') { toggleDate(button.dataset.date); scheduleChanged('#sch-date'); }
    else if (button.dataset.sch === 'date-add') {
      const value = $('#sch-date', schedulePanel).value;
      if (!parseYmd(value)) { $('#sch-date', schedulePanel).focus(); return; }
      if (!SCH.dates.includes(value)) toggleDate(value);
      const d = parseYmd(value);
      calMonth = new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), 1));
      scheduleChanged('#sch-date');
    }
  });
  const validateSchedule = () => {
    const missing = SCH.type === 'weekly' && !SCH.days.length ? 'Pilih minimal satu hari.'
      : SCH.type === 'dates' && !SCH.dates.length ? 'Tambahkan minimal satu tanggal.'
        : SCH.type === 'yearly' && !SCH.year_md ? 'Pilih tanggal setiap tahun.' : '';
    const badEnd = SCH.end && SCH.start && SCH.end < SCH.start;
    if (!missing && !badEnd) return true;
    setTab('schedule');
    renderSchedule();
    if (missing) {
      const box = $('[data-sch-error]', schedulePanel);
      box.textContent = missing;
      box.hidden = false;
      showToast('Jadwal belum lengkap', missing, 'error');
      ($('[data-day]', schedulePanel) ?? $('#sch-date', schedulePanel) ?? $('#sch-yday', schedulePanel))?.focus();
    } else {
      const box = $('[data-sch-end-error]', schedulePanel);
      box.textContent = 'Tanggal berakhir harus setelah tanggal mulai.';
      box.hidden = false;
      $('#sch-end', schedulePanel).setAttribute('aria-invalid', 'true');
      showToast('Tanggal berakhir tidak valid', 'Tanggal berakhir harus setelah tanggal mulai.', 'error');
      $('#sch-end', schedulePanel).focus();
    }
    return false;
  };
  const cleanSchedule = () => {
    const s = clone(SCH);
    s.days = s.type === 'weekly' ? [...new Set(s.days.map(Number))].sort((a, b) => a - b) : [];
    s.dates = s.type === 'dates' ? [...new Set(s.dates)].sort() : [];
    if (s.type !== 'yearly') s.year_md = '';
    if (s.type !== 'monthly') s.month_day = 1;
    if (!s.deadline) s.reminder = false;
    return s;
  };

  /* ---------- Save, cancel, unsaved changes ---------- */
  const fail = (title, description, selector) => {
    showToast(title, description, 'error');
    const target = selector ? $(selector, list) : null;
    if (target) { target.setAttribute('aria-invalid', 'true'); target.focus(); target.closest('.fe-card')?.scrollIntoView({ block: 'center' }); }
    return false;
  };
  const validate = () => {
    $$('[aria-invalid="true"]', list).forEach((el) => el.removeAttribute('aria-invalid'));
    if (!F.title.trim()) return fail('Judul form kosong', 'Isi judul form di bagian atas.', '#fe-title');
    for (let i = 0; i < F.questions.length; i++) {
      const q = F.questions[i];
      if (q.type !== 'image' && !q.title.trim()) return fail('Ada item tanpa teks', 'Isi teks pertanyaan, judul, atau bagian yang masih kosong.', focusOf(i));
      if (CHOICE.includes(q.type) && !q.options.some((option) => option.trim())) return fail('Minimal satu opsi', 'Pertanyaan pilihan perlu paling sedikit satu opsi.', focusOf(i, '[data-option="0"]'));
      if (q.image && !validUrl(q.image.trim())) return fail('URL gambar tidak valid', 'Gunakan alamat lengkap yang diawali http:// atau https://', focusOf(i, '[data-field="image"]'));
      if (q.type === 'image' && !q.image) return fail('Gambar belum dipilih', 'Isi URL gambar atau hapus item gambar ini.', focusOf(i, '[data-field="image"]'));
    }
    return true;
  };
  const cleaned = () => ({
    title: F.title.trim().replace(/\s+/g, ' '),
    description: F.description.trim(),
    questions: F.questions.map((q) => {
      const copy = clone(q);
      copy.title = copy.title.trim();
      copy.help = copy.help.trim();
      if (copy.image !== undefined) { copy.image = copy.image.trim(); if (!copy.image) delete copy.image; }
      if (copy.options) copy.options = copy.options.map((option) => option.trim()).filter(Boolean);
      if (copy.chips) { copy.chips = copy.chips.map((chip) => chip.trim()).filter(Boolean); if (!copy.chips.length) delete copy.chips; }
      return copy;
    }),
  });
  $('[data-fe-save]').addEventListener('click', () => {
    // Built-in modules: only the schedule is saved (their questions live in their own page).
    if (!builtin && !validate()) { setTab('questions'); return; }
    if (!validateSchedule()) return;
    if (!builtin) {
      const saved = cleaned();
      // The dashboard card follows the form title unless the card was renamed on its own (Edit Kartu).
      if (card.title === (original.title ?? card.title)) card.title = saved.title.slice(0, 60);
      original.title = saved.title;
      state.forms[formId] = saved;
      F = normalize(clone(saved));
    }
    card.schedule = cleanSchedule();
    SCH = scheduleOf(card.schedule);
    store.save(state);
    dirty = false;
    render();
    renderSchedule();
    showToast(builtin ? 'Jadwal tersimpan' : 'Form tersimpan', `${card.title} · ${scheduleLabel(card.schedule).text}`);
  });
  const discardBar = $('[data-fe-discard]');
  $('[data-fe-cancel]').addEventListener('click', () => {
    if (!dirty) { window.location.assign('/'); return; }
    discardBar.hidden = false;
    $('[data-fe-keep]').focus();
  });
  $('[data-fe-keep]').addEventListener('click', () => { discardBar.hidden = true; $('[data-fe-cancel]').focus(); });
  $('[data-fe-discard-go]').addEventListener('click', () => { dirty = false; window.location.assign('/'); });
  window.addEventListener('beforeunload', (event) => { if (dirty) { event.preventDefault(); event.returnValue = ''; } });

  /* ---------- Start ---------- */
  const tile = $('[data-fe-tile]');
  tile.className = `card-icon tone-${card.color} fe-sub__icon`;
  tile.innerHTML = icon(card.icon);
  $('[data-fe-root]').hidden = false;
  if (builtin) {
    // Built-in modules keep their questions in their own page; only the schedule is edited here.
    $('[data-fe-tab="questions"]').hidden = true;
    $('[data-fe-save]').lastChild.textContent = 'Simpan Jadwal';
    setTab('schedule');
  } else if (new URLSearchParams(window.location.search).get('tab') === 'jadwal') setTab('schedule');
  render();
  renderSchedule();
})();
