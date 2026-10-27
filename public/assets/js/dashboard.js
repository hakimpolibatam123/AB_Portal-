(() => {
  'use strict';

  /* Dashboard ("/"), frontend only. State, icons, toast and schedule rules come from dashboard-store.js
     (dummy data + sessionStorage; nothing is sent to the server). The sections are drawn here with the
     same markup as home/index.php and components/portal-card.php. Dialogs: components/dashboard-dialogs.php. */
  const store = window.DashboardStore;
  if (!store?.ready) return;
  const { $, $$, esc, uid, clone, icon, showToast, nowWib, ymd, isDue, cardStatus, scheduleLabel } = store;
  let state = store.load();
  const save = () => store.save(state);
  const sectionById = (id) => state.sections.find((section) => section.id === id);
  const linkSections = () => state.sections.filter((section) => section.kind === 'links');

  /* ---------- Rendering ---------- */
  const container = $('#dashboard-sections');
  let newId = null;
  let newTimer = 0;
  const cardHtml = (card, now) => {
    const isLink = card.kind === 'link';
    let href = card.url || null;
    if (isLink && href && !/^https?:\/\//i.test(href)) href = null;
    const status = !isLink && card.schedule ? cardStatus(card, now) : null;
    const rekap = !isLink && card.rekap && card.has_data ? card.rekap : null;
    const classes = ['portal-card', isLink ? '' : 'portal-card--form', card.hidden ? 'is-muted' : '', status && !status.due ? 'is-off-schedule' : '', newId === card.id ? 'is-new' : ''].filter(Boolean).join(' ');
    const data = { id: card.id, kind: card.kind, builtin: card.builtin, title: card.title, description: card.description, icon: card.icon, image: card.image, url: card.url, rekap, section: card.section, hidden: card.hidden };
    const title = href
      ? `<a class="card-link" href="${esc(href)}"${isLink ? ' target="_blank" rel="noopener noreferrer"' : ''}>${esc(card.title)}</a>`
      : `<button type="button" class="card-link" data-module="${esc(card.id)}" data-title="${esc(card.title)}" data-type="${isLink ? 'external' : 'internal'}" aria-haspopup="dialog">${esc(card.title)}</button>`;
    let foot = '';
    if (!isLink) {
      const button = rekap
        ? `<a class="card-rekap" href="${esc(rekap)}" aria-label="${esc(`Lihat rekap ${card.title}`)}">${icon('chart-column', 'size-4')}Lihat Rekap</a>`
        : `<span class="card-tip"><button type="button" class="card-rekap is-off" aria-disabled="true" data-no-rekap aria-label="${esc(`Lihat rekap ${card.title} (belum ada laporan)`)}" aria-describedby="tip-${esc(card.id)}">${icon('chart-column', 'size-4')}Lihat Rekap</button><span class="card-tip__box" role="tooltip" id="tip-${esc(card.id)}">Belum ada laporan</span></span>`;
      const text = status ? `<span class="card-status card-status--${status.tone}">${status.tone === 'ok' ? icon('check', 'size-3.5') : ''}${esc(status.text)}</span>` : '';
      foot = `<div class="card-foot">${button}${text}</div>`;
    }
    return `<article id="card-${esc(card.id)}" class="${classes}"${card.hidden ? ' data-hidden-card hidden' : ''} data-card="${esc(JSON.stringify(data))}">`
      + `<span class="card-flags">${isLink ? '' : (card.schedule ? `<span class="card-badge card-badge--${scheduleLabel(card.schedule).tone}">${esc(scheduleLabel(card.schedule).text)}</span>` : '<span class="card-badge bg-emerald-50 text-emerald-800">Form</span>')}${newId === card.id ? '<span class="card-chip card-chip--new" data-new-chip>Baru</span>' : ''}${card.hidden ? '<span class="card-chip">Disembunyikan</span>' : ''}</span>`
      + `<button type="button" class="card-menu-btn" data-card-menu aria-haspopup="menu" aria-expanded="false" aria-label="${esc(`Menu ${card.title}`)}">${icon('ellipsis', 'size-4')}</button>`
      + `<span class="card-icon tone-${esc(card.color)}">${card.image ? `<img class="card-icon-img" src="${esc(card.image)}" alt="" loading="lazy" referrerpolicy="no-referrer">` : icon(card.icon)}</span>`
      + `<h3 class="portal-card-title">${title}</h3>`
      + `<p class="portal-card-description">${esc(card.description)}${isLink && !href ? '<span class="card-note">Alamat belum diatur</span>' : ''}</p>`
      + `${foot}</article>`;
  };
  // "Jadwal Hari Ini" (default) or "Semua Form": a per-browser preference, not dashboard data.
  const FILTER_KEY = 'abportal.dashboardFilter';
  let filter = 'today';
  try { filter = window.localStorage.getItem(FILTER_KEY) === 'all' ? 'all' : 'today'; } catch { /* default */ }
  const dueToday = (card, now) => Boolean(card.schedule) && isDue(card.schedule, now.day);
  const sectionHtml = (section, index, total, now) => {
    const all = state.cards.filter((card) => card.section === section.id);
    const isForms = section.kind === 'forms';
    // Under "Jadwal Hari Ini" a form that is not due today is left out (hidden cards stay for their toggle).
    const cards = isForms && filter === 'today' ? all.filter((card) => card.hidden || dueToday(card, now)) : all;
    const hidden = cards.filter((card) => card.hidden).length;
    const visible = all.filter((card) => !card.hidden);
    const dueN = visible.filter((card) => dueToday(card, now)).length;
    const doneN = visible.filter((card) => dueToday(card, now) && card.done_today).length;
    const filterBar = isForms
      ? '<div class="section-filter"><div class="section-seg" role="group" aria-label="Filter form">'
        + `<button type="button" data-filter="today" aria-pressed="${filter === 'today'}">Jadwal Hari Ini</button>`
        + `<button type="button" data-filter="all" aria-pressed="${filter === 'all'}">Semua Form</button></div>`
        + `<span class="section-count"><i aria-hidden="true"></i><b>${dueN} dari ${visible.length} form dijadwalkan hari ini</b>${dueN ? ` · ${doneN} selesai` : ''}</span></div>`
      : '';
    const filteredEmpty = isForms && filter === 'today' && visible.length > 0 && cards.every((card) => card.hidden);
    const spacing = index === 0 ? 'pt-2' : (index === total - 1 ? 'pb-8 pt-4' : 'pt-4');
    const headingId = `section-${esc(section.id)}`;
    let actions = '';
    if (hidden || isForms) {
      actions = '<div class="section-actions">'
        + (hidden ? `<button type="button" class="section-textbtn" data-toggle-hidden aria-pressed="false" data-label-show="Tampilkan ${hidden} tersembunyi" data-label-hide="Sembunyikan ${hidden} tersembunyi">${icon('eye', 'size-3.5')}<span data-toggle-label>Tampilkan ${hidden} tersembunyi</span></button>` : '')
        + (isForms ? `<button type="button" class="dash-btn dash-btn--primary dash-btn--sm" data-open-add aria-haspopup="dialog">${icon('plus', 'size-4')}Tambah</button>` : '')
        + '</div>';
    }
    let body;
    if (filteredEmpty) {
      body = `<div class="section-empty">${icon('calendar-check', 'size-6 text-slate-400')}<b>Tidak ada form yang dijadwalkan hari ini</b>`
        + '<span>Form lain tetap bisa dibuka dari tampilan Semua Form.</span>'
        + `<button type="button" class="dash-btn dash-btn--ghost dash-btn--sm" data-filter="all">${icon('list-checks', 'size-4')}Lihat Semua Form</button></div>`
        + (cards.length ? `<div class="card-grid--forms grid grid-cols-1 gap-3.5">${cards.map((card) => cardHtml(card, now)).join('')}</div>` : '');
    } else if (!cards.length) {
      body = `<div class="section-empty">${icon(isForms ? 'clipboard-list' : 'link', 'size-6 text-slate-400')}`
        + `<b>${isForms ? 'Belum ada form di bagian ini' : 'Belum ada tautan di bagian ini'}</b>`
        + `<span>${isForms ? 'Buat form monitoring pertama untuk mulai mencatat.' : 'Tambahkan tautan lewat tombol + Tambah, tab Kategori &amp; Tautan.'}</span>`
        + (section.custom ? `<button type="button" class="section-textbtn section-textbtn--danger" data-delete-section="${esc(section.id)}">${icon('trash-2', 'size-3.5')}Hapus kategori ini</button>` : '')
        + '</div>';
    } else {
      body = (hidden === cards.length ? '<p class="section-empty section-empty--compact" data-all-hidden>Semua kartu di bagian ini disembunyikan.</p>' : '')
        + `<div class="${isForms ? 'card-grid--forms grid grid-cols-1 gap-3.5' : 'grid grid-cols-2 gap-3.5 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7'}">${cards.map((card) => cardHtml(card, now)).join('')}</div>`;
    }
    return `<section aria-labelledby="${headingId}" class="portal-category space-y-3 ${spacing}" data-section="${esc(section.id)}">`
      + `<div class="section-head"><h2 id="${headingId}" class="flex items-center gap-2 text-[11.7px] font-bold uppercase tracking-wider text-slate-500">${icon(section.icon, 'size-3.5 text-slate-400')}${esc(section.title)}</h2>${actions}</div>`
      + `${filterBar}${body}</section>`;
  };
  /* Progress ring and bar (components/progress-summary.php): visible Monitoring Harian forms due today. */
  const updateProgress = (now) => {
    const formSections = state.sections.filter((section) => section.kind === 'forms').map((section) => section.id);
    const due = state.cards.filter((card) => formSections.includes(card.section) && !card.hidden && dueToday(card, now));
    const target = due.length, done = due.filter((card) => card.done_today).length, ratio = target ? done / target : 0;
    const set = (name, text) => { const el = $(`[data-progress="${name}"]`); if (el) el.textContent = text; };
    set('pct', `${target ? Math.round(ratio * 100) : 0}%`);
    set('done', String(done));
    set('total', `/ ${target} Form`);
    set('ratio', `${done}/${target} Selesai`);
    set('note', !target ? 'Belum ada form harian wajib' : done === target ? 'Semua form hari ini sudah diisi' : `${target - done} form belum diinput hari ini`);
    const ring = $('[data-progress="ring"]');
    if (ring) ring.setAttribute('stroke-dashoffset', (2 * Math.PI * 30 * (1 - ratio)).toFixed(3));
    const bar = $('[data-progress="bar"]');
    if (bar) bar.style.width = `${(ratio * 100).toFixed(3)}%`;
    const meter = $('[data-progress="meter"]');
    if (meter) {
      meter.setAttribute('aria-valuemax', String(Math.max(1, target)));
      meter.setAttribute('aria-valuenow', String(done));
      meter.setAttribute('aria-valuetext', `${done} dari ${target} form selesai`);
    }
  };
  const render = () => {
    const now = nowWib();
    container.innerHTML = state.sections.map((section, i) => sectionHtml(section, i, state.sections.length, now)).join('');
    updateProgress(now);
    // An image icon that cannot be loaded falls back to the card's list icon.
    $$('img.card-icon-img', container).forEach((img) => img.addEventListener('error', () => {
      const card = state.cards.find((item) => `card-${item.id}` === img.closest('article').id);
      img.outerHTML = icon(card?.icon ?? 'link');
    }, { once: true }));
    const fresh = newId ? document.getElementById(`card-${newId}`) : null;
    if (fresh) {
      fresh.scrollIntoView({ block: 'center' });
      $('.card-link', fresh)?.focus({ preventScroll: true });
      window.clearTimeout(newTimer);
      newTimer = window.setTimeout(() => {
        newId = null;
        const card = $('.portal-card.is-new', container);
        card?.classList.remove('is-new');
        $('[data-new-chip]', card ?? document)?.remove();
      }, 8000);
    }
  };
  const commit = (cardId = null) => { save(); newId = cardId; render(); };
  render();

  /* ---------- Demo reset and category removal ---------- */
  document.addEventListener('click', (event) => {
    const filterButton = event.target.closest('[data-filter]');
    if (filterButton) {
      filter = filterButton.dataset.filter === 'all' ? 'all' : 'today';
      try { window.localStorage.setItem(FILTER_KEY, filter); } catch { /* not remembered */ }
      newId = null;
      render();
      $(`.section-seg [data-filter="${filter}"]`)?.focus();
      return;
    }
    if (event.target.closest('[data-reset-demo]')) {
      state = store.reset();
      newId = null;
      render();
      showToast('Data contoh diatur ulang', 'Dashboard kembali ke data awal.', 'info');
      return;
    }
    const remove = event.target.closest('[data-delete-section]');
    if (remove) {
      const section = sectionById(remove.dataset.deleteSection);
      if (!section?.custom || state.cards.some((card) => card.section === section.id)) return;
      state.sections = state.sections.filter((item) => item !== section);
      commit();
      showToast('Kategori dihapus', section.title);
    }
  });

  /* ---------- "Lihat Rekap" without data: the tooltip also shows on tap (no hover on phones) ---------- */
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-no-rekap]');
    if (!button) return;
    event.preventDefault();
    const tip = button.closest('.card-tip');
    tip.classList.add('is-show');
    window.setTimeout(() => tip.classList.remove('is-show'), 1800);
  });

  /* ---------- Hidden cards per section ---------- */
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toggle-hidden]');
    if (!button) return;
    const section = button.closest('[data-section]');
    const show = button.getAttribute('aria-pressed') !== 'true';
    button.setAttribute('aria-pressed', String(show));
    $('[data-toggle-label]', button).textContent = show ? button.dataset.labelHide : button.dataset.labelShow;
    section.classList.toggle('is-showing-hidden', show);
    $$('[data-hidden-card]', section).forEach((card) => { card.hidden = !show; });
  });

  /* ---------- Card menu ---------- */
  const menu = $('#card-menu');
  let menuButton = null;
  const closeMenu = (focusBack = false) => {
    if (!menuButton) return;
    menu.hidden = true;
    menu.replaceChildren();
    menuButton.setAttribute('aria-expanded', 'false');
    if (focusBack) menuButton.focus();
    menuButton = null;
  };
  const placeMenu = () => {
    if (!menuButton) return;
    const r = menuButton.getBoundingClientRect();
    if (r.bottom < 0 || r.top > window.innerHeight) { closeMenu(); return; }
    const left = Math.min(Math.max(8, r.right - menu.offsetWidth), window.innerWidth - menu.offsetWidth - 8);
    let top = r.bottom + 6;
    if (top + menu.offsetHeight > window.innerHeight - 8) top = Math.max(8, r.top - menu.offsetHeight - 6);
    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
  };
  const menuItem = (action, label, iconName, { href = null, disabled = false, note = '', danger = false } = {}) => {
    const item = document.createElement(href && !disabled ? 'a' : 'button');
    item.setAttribute('role', 'menuitem');
    item.dataset.action = action;
    if (href && !disabled) item.href = href; else item.type = 'button';
    if (disabled) item.setAttribute('aria-disabled', 'true');
    if (danger) item.classList.add('is-danger');
    item.innerHTML = icon(iconName, 'size-4');
    item.append(label);
    if (note) { const small = document.createElement('small'); small.textContent = note; item.append(small); }
    return item;
  };
  const cardById = (id) => state.cards.find((card) => card.id === id);
  // A dynamic form card opens its fill-in page; "Lihat Rekap" opens its responses (once there are any).
  const formLinks = (id) => ({
    url: `${menu.dataset.fillUrl}?id=${encodeURIComponent(id)}`,
    rekap: `${menu.dataset.responsesUrl}?id=${encodeURIComponent(id)}`,
  });
  /* Items per card: built-in form (an existing module page) · dynamic form · link · built-in link. */
  const menuItems = (card) => {
    const visibility = card.hidden
      ? menuItem('show', 'Tampilkan di dashboard', 'eye')
      : menuItem('hide', 'Sembunyikan dari dashboard', 'eye-off');
    const remove = () => [document.createElement('hr'), menuItem('delete', 'Hapus', 'trash-2', { danger: true })];
    if (card.kind === 'link') {
      return card.builtin
        ? [menuItem('edit', 'Edit', 'pencil'), visibility]
        : [menuItem('edit', 'Edit', 'pencil'), menuItem('move', 'Pindah Kategori', 'folder-input'), visibility, ...remove()];
    }
    const rekap = card.rekap && card.has_data
      ? menuItem('rekap', 'Lihat Rekap', 'chart-column', { href: card.rekap })
      : menuItem('rekap', 'Lihat Rekap', 'chart-column', { disabled: true, note: 'Belum ada laporan' });
    const editCard = menuItem('editcard', 'Edit Kartu', 'pencil');
    const editForm = menuItem('editform', 'Edit Isi Form', 'file-pen-line', { href: `${menu.dataset.editorUrl}?id=${encodeURIComponent(card.id)}` });
    return card.kind === 'builtin'
      ? [rekap, editCard, menuItem('schedule', 'Jadwal', 'calendar-days', { href: `${menu.dataset.editorUrl}?id=${encodeURIComponent(card.id)}&tab=jadwal` }), visibility]
      : [rekap, editForm, editCard, menuItem('duplicate', 'Duplikat', 'copy'), visibility, ...remove()];
  };
  const openMenu = (button) => {
    if (menuButton === button) { closeMenu(true); return; }
    closeMenu();
    const card = cardById(JSON.parse(button.closest('[data-card]').dataset.card).id);
    if (!card) return;
    menu.replaceChildren(...menuItems(card));
    menu.dataset.cardId = card.id;
    menu.hidden = false;
    menuButton = button;
    button.setAttribute('aria-expanded', 'true');
    placeMenu();
    $('[role="menuitem"]', menu)?.focus({ preventScroll: true });
  };
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-card-menu]');
    if (button) { event.preventDefault(); openMenu(button); return; }
    if (menuButton && !menu.contains(event.target)) closeMenu();
  });
  menu.addEventListener('keydown', (event) => {
    const items = $$('[role="menuitem"]', menu);
    const i = items.indexOf(document.activeElement);
    if (event.key === 'ArrowDown') { event.preventDefault(); items[(i + 1) % items.length].focus(); }
    else if (event.key === 'ArrowUp') { event.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
    else if (event.key === 'Home') { event.preventDefault(); items[0].focus(); }
    else if (event.key === 'End') { event.preventDefault(); items[items.length - 1].focus(); }
    else if (event.key === 'Escape') { event.preventDefault(); closeMenu(true); }
    else if (event.key === 'Tab') closeMenu();
  });
  menu.addEventListener('click', (event) => {
    const item = event.target.closest('[role="menuitem"]');
    if (!item) return;
    if (item.getAttribute('aria-disabled') === 'true') {
      event.preventDefault();
      closeMenu(true);
      showToast('Belum ada laporan untuk form ini', 'Rekap tersedia setelah form diisi dan dikirim.', 'info');
      return;
    }
    const card = cardById(menu.dataset.cardId);
    const action = item.dataset.action;
    if (!card || ['rekap', 'editform', 'schedule'].includes(action)) return; // normal links
    closeMenu();
    if (action === 'hide' || action === 'show') setHidden(card, action === 'hide');
    else if (action === 'duplicate') duplicateForm(card);
    else if (action === 'move') openMove(card);
    else if (action === 'delete') openDelete(card);
    else if (action === 'edit') openLinkEdit(card);
    else if (action === 'editcard') openCardEdit(card);
  });
  window.addEventListener('resize', () => closeMenu());
  window.addEventListener('scroll', placeMenu, true);

  /* ---------- Card actions ---------- */
  const focusCard = (id) => $('.card-link', document.getElementById(`card-${id}`) ?? document)?.focus({ preventScroll: true });
  const setHidden = (card, hidden) => {
    card.hidden = hidden;
    commit();
    if (hidden) {
      // The card is out of view now; keep the keyboard near it, on the section's "tersembunyi" toggle.
      $(`[data-section="${CSS.escape(card.section)}"] [data-toggle-hidden]`)?.focus({ preventScroll: true });
      showToast('Kartu disembunyikan', 'Tampilkan lagi lewat tombol "tersembunyi" di judul bagian.');
    } else {
      focusCard(card.id);
      showToast('Kartu ditampilkan lagi', card.title);
    }
  };
  const duplicateForm = (card) => {
    const id = uid('form');
    const copy = { ...clone(card), id, ...formLinks(id), title: `${card.title} (Salinan)`.slice(0, 60), hidden: false, done_today: false, has_data: false };
    state.cards.splice(state.cards.indexOf(card) + 1, 0, copy);
    if (state.forms?.[card.id]) state.forms[copy.id] = { ...clone(state.forms[card.id]), title: `${state.forms[card.id].title} (Salinan)` };
    commit(copy.id);
    showToast('Form diduplikat', copy.title);
  };

  // Small dialogs: close with Batal, Escape or a click outside.
  $$('#move-dialog, #delete-dialog').forEach((small) => {
    $$('[data-dialog-close]', small).forEach((button) => button.addEventListener('click', () => small.close()));
    small.addEventListener('click', (event) => {
      if (event.target !== small) return;
      const r = small.getBoundingClientRect();
      if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) small.close();
    });
  });

  const moveDialog = $('#move-dialog');
  const openMove = (card) => {
    const list = $('[data-move-list]', moveDialog);
    $$('.move-option', list).forEach((option) => option.remove());
    linkSections().forEach((section) => {
      const label = document.createElement('label');
      label.className = 'move-option';
      label.innerHTML = `<input type="radio" name="section" value="${esc(section.id)}"${section.id === card.section ? ' checked' : ''}>${icon(section.icon, 'size-4')}<span>${esc(section.title)}</span>`;
      list.append(label);
    });
    $('[data-card-name]', moveDialog).textContent = card.title;
    moveDialog.dataset.cardId = card.id;
    moveDialog.showModal();
    ($('input:checked', list) ?? $('input', list))?.focus();
  };
  $('form', moveDialog).addEventListener('submit', (event) => {
    event.preventDefault();
    const card = cardById(moveDialog.dataset.cardId);
    const target = sectionById($('input[name="section"]:checked', moveDialog)?.value ?? '');
    moveDialog.close();
    if (!card || !target || target.id === card.section) return;
    // A moved card goes to the end of its new section.
    state.cards.splice(state.cards.indexOf(card), 1);
    card.section = target.id;
    state.cards.push(card);
    commit(card.id);
    showToast('Kartu dipindahkan', `${card.title} sekarang di ${target.title}.`);
  });

  const deleteDialog = $('#delete-dialog');
  const deleteGo = $('[data-delete-go]', deleteDialog);
  const deleteInput = $('#delete-confirm-input');
  const openDelete = (card) => {
    const isForm = card.kind !== 'link';
    $('[data-delete-title]', deleteDialog).textContent = isForm ? 'Hapus form ini?' : 'Hapus tautan ini?';
    const text = $('[data-delete-text]', deleteDialog);
    const name = document.createElement('b');
    name.textContent = card.title;
    text.replaceChildren(isForm ? 'Form ' : 'Kartu ', name, isForm
      ? ' akan dihapus dari dashboard. Laporan yang sudah tersimpan tetap disimpan untuk audit.'
      : ' akan dihapus dari dashboard. Website tujuannya tidak terpengaruh.');
    $('[data-delete-confirm]', deleteDialog).hidden = !isForm;
    $('[data-delete-name]', deleteDialog).textContent = card.title;
    deleteInput.value = '';
    deleteGo.disabled = isForm;
    deleteDialog.dataset.cardId = card.id;
    deleteDialog.showModal();
    (isForm ? deleteInput : $('[data-dialog-close]', deleteDialog)).focus();
  };
  // A form is deleted only after its title is typed exactly.
  deleteInput.addEventListener('input', () => {
    deleteGo.disabled = deleteInput.value.trim() !== (cardById(deleteDialog.dataset.cardId)?.title ?? '').trim();
  });
  $('form', deleteDialog).addEventListener('submit', (event) => {
    event.preventDefault();
    const card = cardById(deleteDialog.dataset.cardId);
    if (!card || deleteGo.disabled) return;
    deleteDialog.close();
    state.cards = state.cards.filter((item) => item !== card);
    if (state.forms) delete state.forms[card.id];
    const section = card.section;
    commit();
    $(`[data-section="${CSS.escape(section)}"] .card-link, [data-section="${CSS.escape(section)}"] [data-open-add], [data-section="${CSS.escape(section)}"] .section-empty button`)?.focus({ preventScroll: true });
    showToast(card.kind === 'link' ? 'Tautan dihapus' : 'Form dihapus', card.title);
  });

  /* ---------- "Tambah" dialog: tabs Form | Kategori & Tautan ---------- */
  const dialog = $('#add-dialog');
  if (!dialog) return;
  const form = $('#link-form');
  const field = (name) => form.elements.namedItem(name);
  const submit = $('[data-submit]', dialog);
  const iconGrid = $('[data-icon-grid]', form);
  const newCat = $('[data-newcat]', form);
  const openUrl = $('[data-open-url]', form);
  const tabs = $$('[data-add-tab]', dialog);
  let imageState = 'none'; // none | loading | ok | error, for the "URL gambar" preview

  const validUrl = (value) => {
    // http or https with a host (internal names such as http://his are allowed)
    try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) && url.hostname !== ''; } catch { return false; }
  };
  const setTab = (name, focus = false) => {
    tabs.forEach((tab) => {
      const on = tab.dataset.addTab === name;
      tab.setAttribute('aria-selected', String(on));
      tab.tabIndex = on ? 0 : -1;
      document.getElementById(tab.getAttribute('aria-controls')).hidden = !on;
      if (on && focus) tab.focus();
    });
    // One "Simpan" button in the footer saves the visible tab's form.
    submit.setAttribute('form', name === 'form' ? 'card-form' : 'link-form');
  };
  dialog.addEventListener('click', (event) => {
    const tab = event.target.closest('[data-add-tab]');
    if (tab) setTab(tab.dataset.addTab);
    if (event.target.closest('[data-dialog-close]')) dialog.close();
  });
  $('[data-add-tabs]', dialog).addEventListener('keydown', (event) => {
    if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
    event.preventDefault();
    const i = tabs.findIndex((tab) => tab.getAttribute('aria-selected') === 'true');
    const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (i + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
    setTab(tabs[next].dataset.addTab, true);
  });

  const setError = (name, message) => {
    const box = $(`[data-error-for="${name}"]`, form);
    if (box) { box.textContent = message; box.hidden = message === ''; }
    const input = field(name);
    if (input instanceof HTMLElement) input.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
  };
  const clearErrors = () => {
    $$('[data-error-for]', form).forEach((box) => { box.textContent = ''; box.hidden = true; });
    $$('[aria-invalid]', form).forEach((input) => input.setAttribute('aria-invalid', 'false'));
    $('[data-form-error]', form).hidden = true;
  };
  const iconSource = () => $('input[name="icon_source"]:checked', form).value;
  const fillSections = (selected) => {
    const select = field('section_id');
    select.replaceChildren(...linkSections().map((section) => new Option(section.title, section.id)), new Option('+ Buat kategori baru', '__new'));
    select.value = selected && sectionById(selected)?.kind === 'links' ? selected : (linkSections()[0]?.id ?? '__new');
    select.dataset.last = select.value;
    newCat.hidden = select.value !== '__new';
  };
  const preview = () => {
    const url = field('url').value.trim();
    $('[data-preview-title]', form).textContent = field('title').value.trim() || 'Judul tautan';
    $('[data-preview-description]', form).textContent = field('description').value.trim();
    $('[data-preview-note]', form).hidden = validUrl(url);
    $('[data-count-for="link-description"]', form).textContent = `${field('description').value.length}/60`;
    if (validUrl(url)) { openUrl.href = url; openUrl.removeAttribute('aria-disabled'); }
    else { openUrl.removeAttribute('href'); openUrl.setAttribute('aria-disabled', 'true'); }
    const box = $('[data-preview-icon]', form);
    const imageUrl = field('icon_url').value.trim();
    if (iconSource() === 'url' && validUrl(imageUrl)) {
      if (box.querySelector('img')?.getAttribute('src') === imageUrl) return;
      const img = document.createElement('img');
      img.className = 'card-icon-img';
      img.alt = '';
      img.referrerPolicy = 'no-referrer';
      imageState = 'loading';
      img.addEventListener('load', () => { imageState = 'ok'; setError('icon_url', ''); });
      img.addEventListener('error', () => { imageState = 'error'; setError('icon_url', 'Gambar tidak dapat dimuat dari alamat ini. Periksa URL-nya atau pilih icon dari daftar.'); box.innerHTML = icon('image'); });
      img.src = imageUrl;
      box.replaceChildren(img);
    } else {
      imageState = 'none';
      const checked = $('input[name="icon"]:checked', iconGrid);
      box.innerHTML = icon(iconSource() === 'url' ? 'image' : (checked?.value ?? 'link'));
    }
  };
  const setIconSource = (source) => {
    $(`input[name="icon_source"][value="${source}"]`, form).checked = true;
    $$('[data-icon-panel]', form).forEach((panel) => { panel.hidden = panel.dataset.iconPanel !== source; });
    preview();
  };
  const filterIcons = (scope, query) => {
    const words = query.toLowerCase().trim();
    let shown = 0;
    $$('[data-icon-grid] .icon-option', scope).forEach((option) => {
      option.hidden = words !== '' && !option.dataset.keywords.includes(words);
      if (!option.hidden) shown++;
    });
    $('[data-icon-empty]', scope).hidden = shown > 0;
  };
  // Scroll an icon list (not the dialog) so its chosen icon is in view.
  const showChosenIcon = (scope) => {
    const grid = $('[data-icon-grid]', scope);
    const chosen = $('input[name="icon"]:checked', grid)?.parentElement;
    if (chosen) grid.scrollTop += chosen.getBoundingClientRect().top - grid.getBoundingClientRect().top - 4;
  };

  /* Form tab: a new form card, or "Edit Kartu" of a form card (built-in or dynamic). */
  const cardForm = $('#card-form');
  const cardField = (name) => cardForm.elements.namedItem(name);
  const cardGrid = $('[data-icon-grid]', cardForm);
  const cardPreview = () => {
    const base = editing && editing.kind !== 'link' ? editing : { has_data: false, done_today: false, rekap: null, schedule: { type: 'daily', days: [], month_day: 1, year_md: '', dates: [], start: '', end: '', deadline: '', reminder: false } };
    const fake = {
      ...base, id: 'pratinjau', kind: base.kind ?? 'form', section: 'harian', hidden: false, image: null,
      title: cardField('title').value.trim() || 'Judul Form',
      description: cardField('description').value.trim(),
      icon: $('input[name="icon"]:checked', cardGrid)?.value ?? 'clipboard-list',
      color: $('input[name="color"]:checked', cardForm)?.value ?? 'teal',
    };
    const slot = $('[data-card-preview]', cardForm);
    const keepNew = newId;
    newId = null; // the preview never shows the "Baru" chip
    slot.innerHTML = cardHtml(fake, nowWib());
    newId = keepNew;
    // The preview is a picture of the card: nothing in it can be clicked or focused.
    $$('a, button', slot).forEach((el) => { el.removeAttribute('href'); el.tabIndex = -1; el.removeAttribute('data-card-menu'); el.removeAttribute('data-no-rekap'); el.removeAttribute('data-module'); });
    $('article', slot)?.removeAttribute('id');
    $('[data-count-for="card-description"]', cardForm).textContent = `${cardField('description').value.length}/60`;
  };
  const setCardError = (name, message) => {
    const box = $(`[data-error-for="${name}"]`, cardForm);
    if (box) { box.textContent = message; box.hidden = message === ''; }
    const input = cardField(name);
    if (input instanceof HTMLElement) input.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
  };
  const resetCardForm = (card) => {
    cardForm.reset();
    $$('[data-error-for]', cardForm).forEach((box) => { box.textContent = ''; box.hidden = true; });
    $$('[aria-invalid]', cardForm).forEach((input) => input.setAttribute('aria-invalid', 'false'));
    cardField('title').value = card?.title ?? '';
    cardField('description').value = card?.description ?? '';
    cardField('keterangan').value = card ? (state.forms?.[card.id]?.description ?? '') : '';
    // Built-in modules keep their page text in their own PHP view (only the card is edited here).
    $('[data-keterangan-field]', cardForm).hidden = card?.kind === 'builtin';
    $('[data-card-note]', cardForm).hidden = Boolean(card);
    ($(`input[name="icon"][value="${CSS.escape(card?.icon ?? 'clipboard-list')}"]`, cardGrid) ?? $('input[name="icon"][value="clipboard-list"]', cardGrid)).checked = true;
    ($(`input[name="color"][value="${CSS.escape(card?.color ?? 'teal')}"]`, cardForm) ?? $('input[name="color"][value="teal"]', cardForm)).checked = true;
    filterIcons(cardForm, '');
  };

  let editing = null; // the card being edited, or null when adding
  /* mode: no card = "Tambah" (both tabs) · link card = "Edit Tautan" · form card = "Edit Kartu" */
  const openAdd = (card = null) => {
    editing = card;
    const tab = card ? (card.kind === 'link' ? 'link' : 'form') : 'form';
    const linkCard = card?.kind === 'link' ? card : null;
    form.reset();
    clearErrors();
    fillSections(linkCard?.section ?? null);
    // Built-in links keep their category (they can be edited and hidden, not moved or deleted).
    field('section_id').disabled = Boolean(linkCard?.builtin);
    $('[data-dialog-title]', dialog).textContent = card ? (linkCard ? 'Edit Tautan' : 'Edit Kartu') : 'Tambah';
    $('[data-add-tabs]', dialog).hidden = Boolean(card);
    field('title').value = linkCard?.title ?? '';
    field('description').value = linkCard?.description ?? '';
    field('url').value = linkCard?.url ?? '';
    field('icon_url').value = linkCard?.image ?? '';
    ($(`input[name="icon"][value="${CSS.escape(linkCard?.icon ?? 'link')}"]`, iconGrid) ?? $('input[name="icon"][value="link"]', iconGrid)).checked = true;
    filterIcons(form, '');
    setIconSource(linkCard?.image ? 'url' : 'list');
    resetCardForm(card && !linkCard ? card : null);
    cardPreview();
    setTab(tab);
    dialog.showModal();
    $('.dash-dialog__body', dialog).scrollTop = 0;
    (tab === 'form' ? cardField('title') : field('title')).focus();
    showChosenIcon(form);
    showChosenIcon(cardForm);
  };
  document.addEventListener('click', (event) => { if (event.target.closest('[data-open-add]')) openAdd(); });
  const openLinkEdit = (card) => openAdd(card);
  const openCardEdit = (card) => openAdd(card);
  dialog.addEventListener('close', () => { editing = null; });

  cardForm.addEventListener('input', (event) => {
    if (event.target.matches('[data-icon-search]')) { filterIcons(cardForm, event.target.value); return; }
    if (event.target.name) setCardError(event.target.name, '');
    cardPreview();
  });
  cardForm.addEventListener('change', () => cardPreview());
  cardForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const title = cardField('title').value.trim().replace(/\s+/g, ' ');
    const description = cardField('description').value.trim().replace(/\s+/g, ' ');
    const keterangan = cardField('keterangan').value.trim();
    const errors = {};
    if (!title) errors.title = 'Judul Form wajib diisi.';
    else if (title.length > 60) errors.title = 'Judul Form maksimal 60 karakter.';
    if (description.length > 60) errors.description = 'Deskripsi maksimal 60 karakter.';
    if (keterangan.length > 500) errors.keterangan = 'Keterangan maksimal 500 karakter.';
    Object.entries(errors).forEach(([name, message]) => setCardError(name, message));
    const first = ['title', 'description', 'keterangan'].find((name) => errors[name]);
    if (first) { cardField(first).focus(); return; }
    const values = {
      title,
      description,
      icon: $('input[name="icon"]:checked', cardGrid)?.value ?? 'clipboard-list',
      color: $('input[name="color"]:checked', cardForm)?.value ?? 'teal',
    };
    const card = editing;
    dialog.close();
    state.forms ??= {};
    if (card) {
      Object.assign(card, values);
      if (card.kind === 'form') state.forms[card.id] = { ...(state.forms[card.id] ?? { questions: [] }), title, description: keterangan };
      commit();
      focusCard(card.id);
      showToast('Kartu diperbarui', card.title);
      return;
    }
    // A new form starts without questions, scheduled every day from today (Edit Isi Form sets both).
    const newFormId = uid('form');
    const added = {
      id: newFormId, section: 'harian', kind: 'form', builtin: false, ...values, image: null, ...formLinks(newFormId), hidden: false,
      schedule: { type: 'daily', days: [], month_day: 1, year_md: '', dates: [], start: ymd(nowWib().day), end: '', deadline: '', reminder: false },
      done_today: false, has_data: false,
    };
    const formsSection = state.sections.find((section) => section.kind === 'forms');
    added.section = formsSection?.id ?? 'harian';
    // New form cards go after the last form card.
    const lastForm = state.cards.map((item) => item.section).lastIndexOf(added.section);
    state.cards.splice(lastForm + 1, 0, added);
    state.forms[added.id] = { title, description: keterangan, questions: [] };
    commit(added.id);
    showToast('Kartu berhasil ditambahkan', 'Atur pertanyaan dan jadwal lewat menu ⋯ > Edit Isi Form.');
  });

  form.addEventListener('input', (event) => {
    const target = event.target;
    if (target.matches('[data-icon-search]')) { filterIcons(form, target.value); return; }
    if (target.name && target.name !== 'category_title') setError(target.name, '');
    preview();
  });
  form.addEventListener('change', (event) => {
    const target = event.target;
    if (target.name === 'section_id') {
      if (target.value === '__new') { newCat.hidden = false; field('category_title').value = ''; setError('category_title', ''); field('category_title').focus(); }
      else { target.dataset.last = target.value; newCat.hidden = true; setError('section_id', ''); }
    } else if (target.name === 'icon_source') setIconSource(target.value);
    else if (target.name === 'icon') { setError('icon', ''); preview(); }
  });
  openUrl.addEventListener('click', (event) => {
    if (openUrl.getAttribute('aria-disabled') === 'true') { event.preventDefault(); field('url').focus(); }
  });
  $('[data-newcat-cancel]', form).addEventListener('click', () => {
    const select = field('section_id');
    select.value = select.dataset.last && select.dataset.last !== '__new' ? select.dataset.last : (linkSections()[0]?.id ?? '__new');
    newCat.hidden = select.value !== '__new';
    select.focus();
  });
  $('[data-newcat-save]', form).addEventListener('click', () => {
    const title = field('category_title').value.trim().replace(/\s+/g, ' ').toUpperCase();
    const error = !title ? 'Nama kategori wajib diisi.'
      : title.length > 32 ? 'Nama kategori maksimal 32 karakter.'
        : state.sections.some((section) => section.title.toUpperCase() === title) ? 'Nama kategori sudah dipakai.' : '';
    setError('category_title', error);
    if (error) { field('category_title').focus(); return; }
    const section = { id: uid('kategori'), title, icon: $('input[name="category_icon"]:checked', form)?.value ?? 'folder-open', kind: 'links', custom: true };
    state.sections.push(section);
    commit();
    fillSections(section.id);
    showToast('Kategori dibuat', `${title} tampil sebagai bagian baru di dashboard.`);
    field('title').focus();
  });

  const validate = () => {
    const errors = {};
    const title = field('title').value.trim();
    if (field('section_id').value === '__new') errors.section_id = 'Isi Nama Kategori lalu klik Simpan kategori.';
    else if (!sectionById(field('section_id').value)) errors.section_id = 'Pilih kategori.';
    if (!title) errors.title = 'Judul wajib diisi.';
    else if (title.length > 40) errors.title = 'Judul maksimal 40 karakter.';
    if (field('description').value.trim().length > 60) errors.description = 'Deskripsi maksimal 60 karakter.';
    if (!validUrl(field('url').value.trim())) errors.url = 'Gunakan alamat lengkap yang diawali http:// atau https://';
    if (iconSource() === 'list' && !$('input[name="icon"]:checked', iconGrid)) errors.icon = 'Pilih icon dari daftar.';
    if (iconSource() === 'url') {
      if (!validUrl(field('icon_url').value.trim())) errors.icon_url = 'Isi URL gambar yang diawali http:// atau https://';
      else if (imageState === 'error') errors.icon_url = 'Gambar tidak dapat dimuat dari alamat ini. Periksa URL-nya atau pilih icon dari daftar.';
    }
    return errors;
  };
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (submit.disabled) return;
    clearErrors();
    const errors = validate();
    const names = Object.keys(errors);
    if (names.length) {
      names.forEach((name) => setError(name, errors[name]));
      const first = ['section_id', 'title', 'description', 'url', 'icon', 'icon_url'].find((name) => errors[name]);
      if (first === 'section_id' && field('section_id').value === '__new') field('category_title').focus();
      else if (first === 'icon') ($('input[name="icon"]', iconGrid)).focus();
      else field(first).focus();
      return;
    }
    const section = sectionById(field('section_id').value);
    const values = {
      title: field('title').value.trim().replace(/\s+/g, ' '),
      description: field('description').value.trim().replace(/\s+/g, ' '),
      icon: $('input[name="icon"]:checked', iconGrid)?.value ?? 'link',
      image: iconSource() === 'url' ? field('icon_url').value.trim() : null,
      url: field('url').value.trim(),
    };
    const card = editing;
    dialog.close();
    if (card) {
      Object.assign(card, values);
      // A changed category puts the card at the end of its new section.
      if (!card.builtin && card.section !== section.id) {
        state.cards.splice(state.cards.indexOf(card), 1);
        card.section = section.id;
        state.cards.push(card);
      }
      commit();
      focusCard(card.id);
      showToast('Tautan diperbarui', card.title);
      return;
    }
    const added = { id: uid('tautan'), section: section.id, kind: 'link', builtin: false, ...values, color: 'teal', rekap: null, hidden: false };
    state.cards.push(added);
    commit(added.id);
    showToast('Kartu berhasil ditambahkan', `${added.title} tampil di ${section.title}.`);
  });
})();
