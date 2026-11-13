(() => {
  'use strict';

  /* Recap of a form as a sheet (app/Views/form-dinamis/rekap-form.php). Columns, rows, statistic cards and the
     paging line come from the server as JSON. The filters live in the URL: every change replaces the address and
     loads the data again (search 300 ms after typing stops). Sorting by a header, and a read-only detail dialog
     with the history (a sent report is not changed or removed). Cells: { text, chip, tone, link, clamp }. */
  const store = window.DashboardStore;
  const page = document.querySelector('[data-recap]');
  if (!store || !page) return;
  const { $, $$, esc, icon, showToast, api, getJson, apiError } = store;

  const form = $('[data-filters]', page);
  const body = $('[data-fr-body]', page);
  const FIELDS = [...new Set([...form.elements].map((field) => field.name).filter(Boolean))];
  let sort = { urut: '', arah: '' };
  let pageNo = 1;
  let data = null;
  let request = 0;

  /* ---------- Filters ⇄ URL ---------- */
  // The page's own address parameter (a dynamic form's ?id=…) stays in the URL.
  const own = new URLSearchParams(window.location.search).get('id');
  const fromUrl = () => {
    const params = new URLSearchParams(window.location.search);
    FIELDS.forEach((key) => { if (form.elements[key]) form.elements[key].value = params.get(key) ?? (key === 'per' ? '25' : ''); });
    // Default view (FR-R1): this month; a dynamic form's history (empty data-this-month) shows every period.
    if (!params.get('periode') && !params.get('dari') && !params.get('sampai')) form.elements.periode.value = page.dataset.thisMonth;
    sort = { urut: params.get('urut') ?? '', arah: params.get('arah') ?? '' };
    pageNo = Math.max(1, Number(params.get('halaman')) || 1);
  };
  const query = () => {
    const params = new URLSearchParams(own ? { id: own } : {});
    FIELDS.forEach((key) => {
      const value = form.elements[key]?.value.trim() ?? '';
      if (value && !(key === 'per' && value === '25')) params.set(key, value);
    });
    if (sort.urut) { params.set('urut', sort.urut); params.set('arah', sort.arah || 'desc'); }
    if (pageNo > 1) params.set('halaman', String(pageNo));
    return params;
  };

  /* ---------- Drawing ---------- */
  const cell = (c) => {
    if (!c || (c.text ?? '') === '') return '<span class="fr-muted">—</span>';
    const text = c.link
      ? `<a class="fr-link" href="${esc(c.link)}" target="_blank" rel="noopener">${icon('paperclip', 'size-4')}${esc(c.text)}</a>`
      : `<span class="${c.clamp ? 'fr-clamp' : 'fr-value'}">${esc(c.text)}</span>`;
    if (c.chip && c.chip === c.text) return `<span class="fr-chip fr-chip--${esc(c.tone ?? 'plain')}">${esc(c.text)}</span>`;
    return c.chip ? `${text} <span class="fr-chip fr-chip--${esc(c.tone ?? 'plain')}">${esc(c.chip)}</span>` : text;
  };
  const header = (col) => {
    if (!col.sort) return `<th scope="col">${esc(col.label)}</th>`;
    const on = data.filters.sort === col.sort;
    const dir = on ? data.filters.dir : '';
    const aria = on ? ` aria-sort="${dir === 'asc' ? 'ascending' : 'descending'}"` : '';
    return `<th scope="col"${aria}><button type="button" class="fr-sort" data-sort="${esc(col.sort)}">${esc(col.label)}`
      + `${on ? icon(dir === 'asc' ? 'arrow-up' : 'arrow-down', 'size-3.5') : ''}</button></th>`;
  };
  // Numbers and times stay on one line.
  const NOWRAP = ['entry_no', 'waktu'];
  const table = () => '<div class="fr-table-wrap" role="region" aria-label="Tabel rekap" tabindex="0"><table class="fr-table"><thead><tr>'
    + '<th scope="col" class="fr-rownum">#</th>' + data.columns.map(header).join('') + '<th scope="col"><span class="sr-only">Detail</span></th></tr></thead><tbody>'
    + data.rows.map((row, i) => `<tr data-open="${row.id}"><td class="fr-rownum">${(data.filters.page - 1) * data.filters.per + i + 1}</td>${data.columns.map((col) => `<td${NOWRAP.includes(col.key) ? ' class="fr-nowrap"' : ''}>${cell(row.cells[col.key])}</td>`).join('')}`
      + `<td><button type="button" class="dash-btn dash-btn--ghost dash-btn--sm fr-open" data-open="${row.id}" aria-label="${esc(`Lihat detail ${row.cells.entry_no?.text ?? row.cells.session_no?.text ?? ''}`)}">${icon('eye', 'size-4')}Detail</button></td></tr>`).join('')
    + '</tbody></table></div>';
  const draw = () => {
    $('[data-fr-count]', page).textContent = data.count_text;
    const error = $('[data-filter-error]', page);
    error.hidden = !data.error;
    error.textContent = data.error ?? '';
    body.innerHTML = data.rows.length
      ? table()
      : `<div class="fr-empty">${icon('history', 'size-6')}<b>Tidak ada entri untuk filter ini</b><span>Ubah filter, atau <a href="?" data-reset>reset filter</a>.</span></div>`;
    body.removeAttribute('aria-busy');
    // Statistic cards (FR-R8: "–" without entries).
    $$('[data-stats] .fr-stat', page).forEach((tile, i) => {
      const stat = data.stats[i];
      if (!stat) return;
      $('span', tile).textContent = stat.label;
      $('b', tile).textContent = stat.value;
      $('small', tile).textContent = stat.note ?? '';
      tile.dataset.tone = stat.tone ?? '';
    });
    const pager = $('[data-fr-pager]', page);
    pager.hidden = data.pages < 2;
    $('[data-fr-page-info]', pager).textContent = `Halaman ${data.filters.page} dari ${data.pages}`;
    $('[data-fr-page="-1"]', pager).disabled = data.filters.page <= 1;
    $('[data-fr-page="1"]', pager).disabled = data.filters.page >= data.pages;
    $('[data-reset]', form).hidden = query().toString() === DEFAULT;
    exportLinks();
  };
  /* Export buttons (PRD §9): the filters on screen without paging; off with a note above the limits (FR-E12). */
  const exportLinks = () => {
    const box = $('[data-export]', page);
    if (!box) return;
    const fmt = (n) => Number(n).toLocaleString('id-ID');
    const limits = { xlsx: Number(box.dataset.limitXlsx), pdf: Number(box.dataset.limitPdf), cetak: Number(box.dataset.limitPdf) };
    const params = query();
    params.delete('halaman');
    params.delete('per');
    const notes = [];
    if (data.error) notes.push('Perbaiki filter sebelum mengekspor.');
    else if (!data.total) notes.push('Tidak ada data untuk diekspor.');
    else {
      if (data.total > limits.xlsx) notes.push(`Maksimal ${fmt(limits.xlsx)} baris untuk Excel. Persempit filter.`);
      if (data.total > limits.pdf) notes.push(`Maksimal ${fmt(limits.pdf)} baris untuk PDF. Persempit filter.`);
    }
    $$('[data-export-format]', box).forEach((link) => {
      const format = link.dataset.exportFormat;
      const off = Boolean(data.error) || !data.total || data.total > limits[format];
      const url = new URL(box.dataset.exportUrl, window.location.href);
      params.forEach((value, key) => url.searchParams.set(key, value));
      url.searchParams.set('format', format);
      if (off) {
        link.removeAttribute('href');
        link.setAttribute('aria-disabled', 'true');
      } else {
        link.href = url.pathname + url.search;
        link.removeAttribute('aria-disabled');
      }
    });
    const note = $('[data-export-note]', box);
    note.hidden = notes.length === 0;
    note.textContent = notes.join(' ');
  };
  // The address of the default view: the page's own ?id=… and this month (none for a dynamic form).
  const DEFAULT = new URLSearchParams({ ...(own ? { id: own } : {}), ...(page.dataset.thisMonth ? { periode: page.dataset.thisMonth } : {}) }).toString();
  const load = async (push = true) => {
    const params = query();
    if (push) window.history.replaceState(null, '', `${window.location.pathname}${params.toString() ? `?${params}` : ''}`);
    const mine = ++request;
    body.setAttribute('aria-busy', 'true');
    try {
      // The data address may carry its own ?id=…; the filters are added to it.
      const url = new URL(page.dataset.dataUrl, window.location.href);
      params.forEach((value, key) => url.searchParams.set(key, value));
      const answer = await getJson(url.pathname + url.search);
      if (mine !== request) return; // an older answer arriving late
      data = answer;
      pageNo = data.filters.page;
      draw();
    } catch (error) {
      if (mine === request) apiError(error);
    }
  };

  /* ---------- Filter events ---------- */
  let timer = 0;
  form.addEventListener('submit', (event) => { event.preventDefault(); pageNo = 1; load(); });
  form.addEventListener('input', (event) => {
    if (event.target.name !== 'q') return;
    window.clearTimeout(timer);
    timer = window.setTimeout(() => { pageNo = 1; load(); }, 300);
  });
  form.addEventListener('change', (event) => {
    const name = event.target.name;
    if (name === 'q') return;
    // A month and a date range exclude each other.
    if (name === 'periode' && event.target.value) { form.elements.dari.value = ''; form.elements.sampai.value = ''; }
    if ((name === 'dari' || name === 'sampai')) {
      form.elements.periode.value = '';
      if (!form.elements.dari.value || !form.elements.sampai.value) return; // wait for both dates
    }
    pageNo = 1;
    load();
  });
  page.addEventListener('click', (event) => {
    const reset = event.target.closest('[data-reset]');
    if (reset) {
      event.preventDefault();
      form.reset();
      form.elements.periode.value = page.dataset.thisMonth;
      sort = { urut: '', arah: '' };
      pageNo = 1;
      load();
      form.elements.q.focus();
      return;
    }
    const sorter = event.target.closest('[data-sort]');
    if (sorter) {
      // First click ascending, the next descending (FR-R6).
      const key = sorter.dataset.sort;
      sort = { urut: key, arah: data?.filters.sort === key && data.filters.dir === 'asc' ? 'desc' : 'asc' };
      pageNo = 1;
      load().then(() => $(`[data-sort="${CSS.escape(key)}"]`, page)?.focus());
      return;
    }
    const step = event.target.closest('[data-fr-page]');
    if (step && !step.disabled) {
      pageNo += Number(step.dataset.frPage);
      load().then(() => $('#fr-table-title').scrollIntoView({ block: 'start' }));
      return;
    }
    const open = event.target.closest('[data-open]');
    if (open && !event.target.closest('a')) openDetail(open.dataset.open, event.target.closest('button') ?? open);
  });
  window.addEventListener('popstate', () => { fromUrl(); load(false); });

  /* ---------- Detail (read only: a sent report is not changed or removed, like a disposal record) ---------- */
  const detail = $('#fr-detail');
  let opener = null;
  const closeOnBackdrop = (dialog) => dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const r = dialog.getBoundingClientRect();
    if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
  });
  closeOnBackdrop(detail);
  $$('[data-detail-close]', detail).forEach((button) => button.addEventListener('click', () => detail.close()));
  detail.addEventListener('close', () => { opener?.focus({ preventScroll: true }); });

  const openDetail = async (id, from) => {
    opener = from;
    let answer;
    try {
      answer = await getJson(`${page.dataset.detailUrl}/${encodeURIComponent(id)}`);
    } catch (error) {
      apiError(error);
      if (error.status === 404) load(false);
      return;
    }
    $('[data-detail-title]', detail).textContent = answer.title;
    $('[data-detail-sub]', detail).textContent = answer.sub;
    // A field { heading } starts a new group (a section of a dynamic form).
    const fields = answer.fields.map((f) => (f.heading !== undefined
      ? `</dl><h3 class="fr-detail__section">${esc(f.heading)}</h3><dl class="fr-detail__list">`
      : `<div class="fr-qa"><dt>${esc(f.label)}</dt><dd>${cell({ ...f, clamp: false })}</dd></div>`)).join('');
    const history = answer.history.map((h) => `<li><span class="fr-history__head"><b>${esc(h.action)}</b> · ${esc(h.at)}</span>`
      + (h.reason ? `<span class="fr-history__reason">Alasan: ${esc(h.reason)}</span>` : '')
      + (h.changes.length ? `<ul class="fr-history__changes">${h.changes.map((c) => `<li><span>${esc(c.field)}</span>: <s>${esc(c.old)}</s> → <b>${esc(c.new)}</b></li>`).join('')}</ul>` : '')
      + '</li>').join('');
    $('[data-detail-body]', detail).innerHTML = `<dl class="fr-detail__list">${fields}</dl>`
      + (history ? `<h3 class="fr-detail__section">Riwayat</h3><ul class="fr-history">${history}</ul>` : '');
    detail.showModal();
    $('[data-detail-close]', detail).focus();
  };

  /* "Tanda tangan": the signature block of the PDF and Cetak export, saved for this form on the server. */
  const signDialog = document.getElementById('fr-sign');
  if (signDialog) {
    const signForm = $('[data-sign-form]', signDialog);
    const saveSign = $('[data-sign-save]', signForm);
    const signError = (name, message) => {
      const box = $(`[data-error-for="${name}"]`, signForm);
      box.textContent = message;
      box.hidden = message === '';
      signForm.elements.namedItem(name).setAttribute('aria-invalid', String(message !== ''));
    };
    const fieldNames = [...signForm.elements].filter((el) => el.name).map((el) => el.name);
    let saved = Object.fromEntries(fieldNames.map((name) => [name, signForm.elements.namedItem(name).value]));
    $('[data-sign-open]', page).addEventListener('click', () => {
      fieldNames.forEach((name) => { signForm.elements.namedItem(name).value = saved[name] ?? ''; signError(name, ''); });
      signDialog.showModal();
      signForm.elements.namedItem(fieldNames[0]).focus();
    });
    $('[data-sign-cancel]', signForm).addEventListener('click', () => signDialog.close());
    signForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (saveSign.disabled) return;
      saveSign.disabled = true;
      try {
        const values = Object.fromEntries(fieldNames.map((name) => [name, signForm.elements.namedItem(name).value.trim()]));
        const answer = await api(signForm.dataset.signUrl, values);
        saved = answer.signature;
        signDialog.close();
        showToast('Tanda tangan tersimpan', 'Dipakai di Ekspor PDF dan Cetak berikutnya.');
      } catch (error) {
        const names = Object.keys(error.errors ?? {});
        names.forEach((name) => signError(name, error.errors[name]));
        if (names.length) signForm.elements.namedItem(names[0]).focus(); else apiError(error);
      } finally {
        saveSign.disabled = false;
      }
    });
  }

  fromUrl();
  // ?buka=<id> (a link from the dashboard's "Perlu perhatian", PRD FR-P6) opens that report's detail; the
  // parameter is then dropped from the address.
  const open = new URLSearchParams(window.location.search).get('buka');
  load(Boolean(open)).then(() => { if (open && /^\d+$/.test(open)) openDetail(open, null); });
})();
