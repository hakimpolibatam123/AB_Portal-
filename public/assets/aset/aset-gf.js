/*
 * IT Asset Management — Google Forms-style building blocks.
 * Produces the same markup as the portal's form-render.js (gf-card, gf-head, gf-section,
 * gf-q, gf-input, gf-select, gf-opt, gf-actions) so the asset forms match the existing forms.
 */
(function () {
  'use strict';
  var AU = window.AU, esc = AU.esc, ic = AU.ic;

  function head(o) {
    var meta = (o.meta || []).filter(function (m) { return m && m[1]; });
    return '<header class="gf-card gf-head"><h1>' + esc(o.title) + '</h1>' +
      (meta.length ? '<dl class="gf-meta">' + meta.map(function (m) { return '<div><dt>' + esc(m[0]) + '</dt><dd>' + esc(m[1]) + '</dd></div>'; }).join('') + '</dl>' : '') +
      '<div class="gf-head__foot"><span class="gf-req-note"><span class="gf-req">*</span> Wajib Diisi</span>' +
      '<span class="as-head-links"><a class="gf-link" href="../aset">' + ic('layout-grid') + 'Menu Aset IT</a>' +
      '<a class="gf-rekap" href="' + esc(o.rekap || 'rekap') + '">' + ic('chart-column') + 'Lihat Rekap</a></span></div></header>';
  }
  function section(no, title, help, chip, attrs) {
    return '<section class="gf-card gf-section"' + (attrs || '') + '><div class="gf-section__row"><span class="gf-section__no">' + no + '</span>' +
      '<div class="gf-section__text"><h2>' + esc(title) + '</h2></div>' +
      (chip ? '<span class="gf-section__chip">' + esc(chip) + '</span>' : '') + '</div></section>';
  }
  function q(o) {
    return '<section class="gf-card gf-q' + (o.cls ? ' ' + o.cls : '') + '" data-q="' + esc(o.name) + '"' + (o.hidden ? ' hidden' : '') + ' aria-labelledby="qt-' + esc(o.name) + '">' +
      '<div class="gf-q__head"><h3 class="gf-q__title" id="qt-' + esc(o.name) + '">' + esc(o.title) + (o.req ? ' <span class="gf-req" aria-hidden="true">*</span>' : '') + '</h3>' +
      (o.badge ? '<span class="gf-q__badge">' + esc(o.badge) + '</span>' : '') + '</div>' +
      (o.help ? '<p class="gf-q__help">' + o.help + '</p>' : '') +
      '<div class="gf-q__body">' + (o.body || '') + '</div>' +
      '<p class="gf-err" hidden>' + ic('circle-alert') + '<span class="as-errt">Pertanyaan ini wajib diisi</span></p></section>';
  }
  function attrs(name, o) {
    return ' id="g-' + esc(name) + '" name="' + esc(name) + '" aria-labelledby="qt-' + esc(name) + '"' + (o.ph ? ' placeholder="' + esc(o.ph) + '"' : '') +
      (o.list ? ' list="' + o.list + '"' : '') + (o.inputmode ? ' inputmode="' + o.inputmode + '"' : '') + (o.max ? ' max="' + o.max + '"' : '') + (o.min ? ' min="' + o.min + '"' : '') +
      (o.maxlength ? ' maxlength="' + o.maxlength + '"' : '') + (o.readonly ? ' readonly' : '') + ' autocomplete="off"';
  }
  function text(name, o) {
    o = o || {};
    var auto = o.type === 'date' || o.type === 'datetime-local' || o.type === 'time' || o.auto;
    return '<input class="gf-input' + (o.mono ? ' gf-mono' : '') + (auto ? ' gf-input--auto' : '') + '" type="' + (o.type || 'text') + '"' + attrs(name, o) + ' value="' + esc(o.value || '') + '">';
  }
  function area(name, o) {
    o = o || {};
    return '<textarea class="gf-textarea" rows="' + (o.rows || 2) + '"' + attrs(name, o) + '>' + esc(o.value || '') + '</textarea>';
  }
  /*
   * A "Lainnya" choice always comes with a box to write the answer in (shown once it is picked); GF.val() then
   * returns what was written. Lists that end in 'Lainnya' get this on their own.
   */
  var OTHER = 'Lainnya';
  function optValue(op) { return typeof op === 'object' ? op.value : op; }
  function withoutOther(options) { return options.filter(function (op) { return optValue(op) !== OTHER; }); }
  function hasOther(options) { return options.some(function (op) { return optValue(op) === OTHER; }); }
  function select(name, options, value, ph) {
    var other = hasOther(options), list = withoutOther(options);
    var known = list.map(optValue), isOther = other && value && known.indexOf(value) === -1;
    return '<select class="gf-select" id="g-' + esc(name) + '" name="' + esc(name) + '" aria-labelledby="qt-' + esc(name) + '"' + (other ? ' data-other-select' : '') + '><option value="">' + esc(ph || 'Pilih') + '</option>' +
      list.map(function (op) { var v = optValue(op), l = typeof op === 'object' ? op.label : op; return '<option value="' + esc(v) + '"' + (String(v) === String(value || '') ? ' selected' : '') + '>' + esc(l) + '</option>'; }).join('') +
      (other ? '<option value="__other"' + (isOther ? ' selected' : '') + '>' + OTHER + '</option>' : '') + '</select>' +
      (other ? '<input class="gf-input gf-input--other" type="text" name="' + esc(name) + '__other" value="' + esc(isOther ? value : '') + '" placeholder="Tulis jawaban lainnya" aria-label="Lainnya, sebutkan"' + (isOther ? '' : ' hidden') + '>' : '');
  }
  function radio(name, options, value, o) {
    o = o || {};
    if (hasOther(options)) { o.other = true; options = withoutOther(options); }
    var known = options.map(optValue);
    var isOther = o.other && value && known.indexOf(value) === -1;
    var html = options.map(function (op) {
      var v = typeof op === 'object' ? op.value : op, l = typeof op === 'object' ? op.label : op, n = typeof op === 'object' ? op.note : '';
      return '<label class="gf-opt"><input type="radio" name="' + esc(name) + '" value="' + esc(v) + '"' + (String(v) === String(value || '') ? ' checked' : '') + '>' +
        '<span class="gf-opt__text"><span class="gf-opt__label">' + esc(l) + '</span>' + (n ? '<span class="gf-opt__note">' + esc(n) + '</span>' : '') + '</span></label>';
    }).join('');
    if (o.other) {
      html += '<div class="gf-opt gf-opt--other"><input type="radio" name="' + esc(name) + '" value="__other" id="g-' + esc(name) + '-o"' + (isOther ? ' checked' : '') + ' aria-label="Lainnya">' +
        '<span class="gf-opt__text"><label class="gf-opt__label" for="g-' + esc(name) + '-o">' + OTHER + '</label>' +
        '<input class="gf-input gf-input--other" type="text" name="' + esc(name) + '__other" value="' + esc(isOther ? value : '') + '" placeholder="Tulis jawaban lainnya" aria-label="Lainnya, sebutkan"' + (isOther ? '' : ' hidden') + '></span></div>';
    }
    return '<div class="gf-opts' + (o.cols ? ' as-opts--cols' : '') + '" role="radiogroup" aria-labelledby="qt-' + esc(name) + '">' + html + '</div>';
  }
  function ro(textHtml, sub) {
    return '<div class="as-ro">' + (textHtml || '<span class="as-ro__empty">Terisi otomatis setelah barcode dipilih</span>') + (sub ? '<small>' + sub + '</small>' : '') + '</div>';
  }
  function actions(submitLabel, o) {
    o = o || {};
    return '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a>' +
      '<span class="gf-actions__spacer"></span>' +
      '<button type="button" class="gf-btn gf-btn--text" data-reset>' + esc(o.reset || 'Kosongkan formulir') + '</button>' +
      '<button type="submit" class="gf-btn ' + (o.danger ? 'gf-btn--primary as-btn-danger' : 'gf-btn--primary') + '">' + ic(o.icon || 'send') + esc(submitLabel || 'Kirim') + '</button></div>';
  }

  /* values & validation */
  function val(root, name) {
    var r = root.querySelector('[name="' + name + '"]:checked');
    var otherText = function () { return String((root.querySelector('[name="' + name + '__other"]') || {}).value || '').trim(); };
    if (r) return r.value === '__other' ? otherText() : r.value;
    if (root.querySelector('input[type="radio"][name="' + name + '"]')) return '';
    var el = root.querySelector('[name="' + name + '"]');
    if (el && el.value === '__other') return otherText();
    return el ? String(el.value || '').trim() : '';
  }
  function err(root, name, msg) {
    var card = root.querySelector('.gf-q[data-q="' + name + '"]'); if (!card) return;
    var e = card.querySelector(':scope > .gf-err');
    card.classList.toggle('is-error', !!msg);
    if (e) { e.hidden = !msg; if (msg) e.querySelector('.as-errt').textContent = msg; }
  }
  function clear(root) { root.querySelectorAll('.gf-q.is-error').forEach(function (c) { err(root, c.getAttribute('data-q'), ''); }); }
  function need(root, names) {
    var ok = true;
    names.forEach(function (n) {
      var card = root.querySelector('.gf-q[data-q="' + n + '"]');
      if (!card || card.hidden) return;
      if (!val(root, n)) { err(root, n, 'Pertanyaan ini wajib diisi'); ok = false; }
    });
    return ok;
  }
  function firstErr(root) {
    var c = root.querySelector('.gf-q.is-error:not([hidden])'); if (!c) return;
    c.scrollIntoView({ behavior: 'smooth', block: 'center' });
    var f = c.querySelector('input:not([type=hidden]):not([readonly]), select, textarea'); if (f) setTimeout(function () { f.focus({ preventScroll: true }); }, 250);
  }
  function show(root, name, on) { var c = root.querySelector('.gf-q[data-q="' + name + '"]'); if (c) c.hidden = !on; }
  /* clear a question's error as soon as it is answered */
  function wire(root) {
    var h = function (e) {
      var c = e.target.closest && e.target.closest('.gf-q');
      if (c && c.classList.contains('is-error')) err(root, c.getAttribute('data-q'), '');
      if (e.target.name && /__other$/.test(e.target.name)) { var r = root.querySelector('[name="' + e.target.name.replace(/__other$/, '') + '"][value="__other"]'); if (r) r.checked = true; }
      // Picking "Lainnya" (radio or list) opens its write-in box; another choice hides it.
      if (e.type === 'change' && e.target.name && !/__other$/.test(e.target.name) && (e.target.type === 'radio' || e.target.hasAttribute('data-other-select'))) {
        var box = root.querySelector('[name="' + e.target.name + '__other"]');
        if (box) {
          var on = e.target.value === '__other';
          box.hidden = !on;
          if (on) box.focus();
        }
      }
    };
    root.addEventListener('input', h); root.addEventListener('change', h);
  }
  /*
   * Unsent answers are kept in this browser while a form is filled, so leaving the page (or the login ending) loses
   * nothing: on the next visit the person is asked whether to use them again — never filled in silently. Only answers
   * that differ from what the form first showed are kept; uploaded files cannot be. Kept per page and per user for
   * DRAFT_DAYS days, and removed once the form is sent (done) or emptied.
   * o: { key, title, extra(): string kept beside the answers (e.g. the chosen device), allow(extra): ask at all?,
   *      restore(extra): runs before the answers are put back (e.g. picks the device), label(draft): one line shown,
   *      extraCounts: the extra alone is worth keeping (e.g. a list of devices built up without typing) }
   * The page calls touch() after a change that is not typed into a field (a device added to a list, a quick-fill chip).
   */
  var DRAFT_DAYS = 7, activeDraft = null;
  function readForm(form) {
    var v = {};
    Array.prototype.forEach.call(form.elements, function (el) {
      if (!el.name || el.disabled || /^(file|submit|button|reset|password|hidden)$/.test(el.type)) return;
      if (el.type === 'radio') { if (el.checked) v[el.name] = el.value; else if (!(el.name in v)) v[el.name] = ''; return; }
      if (el.type === 'checkbox') { var list = (v[el.name] || '').split('\n').filter(Boolean); if (el.checked) list.push(el.value); v[el.name] = list.join('\n'); return; }
      v[el.name] = el.value;
    });
    return v;
  }
  function fire(el) { el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); }
  /* Puts one kept answer back; false while its question is not on the form yet (it appears after an earlier answer). */
  function setField(form, name, value) {
    var els = Array.prototype.slice.call(form.querySelectorAll('[name="' + name + '"]'));
    if (!els.length) return false;
    if (els[0].type === 'radio') {
      var r = els.filter(function (x) { return x.value === value; })[0];
      if (!r) return value === '';
      if (!r.checked) { r.checked = true; fire(r); }
      return true;
    }
    if (els[0].type === 'checkbox') {
      var want = value.split('\n');
      els.forEach(function (c) { var on = want.indexOf(c.value) !== -1; if (c.checked !== on) { c.checked = on; fire(c); } });
      return true;
    }
    if (els[0].value !== value) { els[0].value = value; fire(els[0]); }
    return true;
  }
  function draft(form, o) {
    var key = 'as.draft:' + (window.AS ? window.AS.me() : '') + ':' + o.key;
    var base = {}, timer = null, quiet = false, dead = false;
    // What each question showed when it first appeared; only answers that differ from it are kept.
    var note = function () { var cur = readForm(form); Object.keys(cur).forEach(function (n) { if (!(n in base)) base[n] = cur[n]; }); return cur; };
    function save() {
      clearTimeout(timer); timer = null;
      if (quiet || dead) return;
      var cur = note(), changed = {}, extra = o.extra ? o.extra() : '';
      Object.keys(cur).forEach(function (n) { if (cur[n] !== base[n]) changed[n] = cur[n]; });
      try {
        if (!Object.keys(changed).length && !(o.extraCounts && extra)) localStorage.removeItem(key);
        else localStorage.setItem(key, JSON.stringify({ at: Date.now(), extra: extra, values: changed }));
      } catch (e) { /* private mode or full storage: the form still works, only nothing is kept */ }
    }
    function load() {
      try {
        var d = JSON.parse(localStorage.getItem(key) || 'null');
        if (d && d.values && (Object.keys(d.values).length || (o.extraCounts && d.extra)) && Date.now() - d.at < DRAFT_DAYS * 864e5) return d;
        localStorage.removeItem(key);
      } catch (e) {}
      return null;
    }
    // The answers were sent or emptied: forget them. The form may stay and be filled again (kept from then on).
    function remove() { clearTimeout(timer); timer = null; try { localStorage.removeItem(key); } catch (e) {} }
    function apply(d) {
      quiet = true;
      Promise.resolve(o.restore ? o.restore(d.extra) : null).then(function () {
        var left = Object.keys(d.values), pass = 0;
        (function step() {
          note(); // questions that just appeared start from what they show, before the kept answer goes in
          left = left.filter(function (n) { return !setField(form, n, d.values[n]); });
          if (left.length && ++pass < 6) { setTimeout(step, 120); return; }
          setTimeout(function () { quiet = false; save(); window.AU.toast('Isian dipulihkan', 'Periksa kembali sebelum dikirim. Berkas dan tanda tangan perlu diunggah ulang.', 'success'); }, 150);
        })();
      });
    }
    function ask(d) {
      var label = o.label ? o.label(d) : '';
      var m = window.AU.modal({ title: 'Lanjutkan isian sebelumnya?', size: 'sm',
        body: '<p class="pm-text" style="margin-top:0">Ada isian <b>' + esc(o.title) + '</b> yang belum dikirim, terakhir diubah ' + esc(window.AU.fmtDateTime(new Date(d.at).toISOString())) + '.</p>' +
          (label ? '<p class="pm-text">' + esc(label) + '</p>' : '') + '<p class="pm-help">Pakai data tadi untuk melanjutkan, atau mulai dari formulir kosong.</p>',
        foot: '<button type="button" class="rs-btn rs-btn--ghost" data-new>Mulai Baru</button><button type="submit" class="rs-btn rs-btn--primary">' + ic('history') + 'Pakai Data Tadi</button>',
        onSubmit: function (api2) { api2.close(); apply(d); } });
      m.$('[data-new]').addEventListener('click', function () { try { localStorage.removeItem(key); } catch (e) {} m.close(); });
    }
    // stop: the form was drawn again (the kept answers stay); clear: sent or emptied (they are removed).
    var later = function () { if (quiet || dead) return; clearTimeout(timer); timer = setTimeout(save, 300); };
    var api = { clear: remove, touch: later, stop: function () { dead = true; clearTimeout(timer); timer = null; } };
    if (activeDraft) activeDraft.stop();
    activeDraft = api;
    note();
    new MutationObserver(function () { if (!quiet && !dead) note(); }).observe(form, { childList: true, subtree: true });
    form.addEventListener('input', later); form.addEventListener('change', later);
    window.addEventListener('pagehide', function () { if (timer) save(); });
    var kept = load();
    if (kept && (!o.allow || o.allow(kept.extra))) setTimeout(function () { ask(kept); }, 0);
    return api;
  }

  /* Google Forms-style confirmation that replaces the form */
  function done(root, o) {
    if (activeDraft) { activeDraft.clear(); activeDraft.stop(); } // sent: nothing left to continue
    root.innerHTML = '<div class="gf"><div class="gf-card gf-head as-done"><span class="as-done__icon">' + ic('circle-check') + '</span><h1>' + esc(o.title) + '</h1>' +
      '<p class="gf-head__desc">' + esc(o.text) + '</p>' + (o.html || '') +
      '<div class="as-done__links">' + (o.links || []).map(function (l) {
        return l.href ? '<a class="gf-link" href="' + esc(l.href) + '">' + ic(l.icon || 'arrow-right') + esc(l.label) + '</a>'
          : '<button type="button" class="gf-link-btn" data-done="' + esc(l.id) + '">' + ic(l.icon || 'arrow-right') + esc(l.label) + '</button>';
      }).join('') + '</div></div></div>';
    AU.render(root);
    (o.links || []).forEach(function (l) { if (l.fn) { var b = root.querySelector('[data-done="' + l.id + '"]'); if (b) b.addEventListener('click', l.fn); } });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  /* readonly key/value block inside a question card */
  function kv(rows) {
    return '<dl class="as-dl as-dl--gf">' + rows.filter(function (r) { return r && r[1]; }).map(function (r) { return '<dt>' + esc(r[0]) + '</dt><dd>' + (r[2] ? r[1] : esc(r[1])) + '</dd>'; }).join('') + '</dl>';
  }
  function datalist(id, items) { return '<datalist id="' + id + '">' + items.map(function (t) { return '<option value="' + esc(t) + '">'; }).join('') + '</datalist>'; }
  /* The location question: a location is its department / unit only. Ruangan, Gedung and Lantai are no longer asked;
     a new location has them empty, older records keep theirs. */
  function locQs(prefix, loc) {
    loc = loc || {};
    // Free text with suggestions: a unit that is not in the list is simply typed (and suggested next time).
    return q({ name: prefix + 'dept', title: 'Departemen / Unit Kerja', req: true, help: 'Pilih dari daftar atau ketik nama unit / tempat baru.',
      body: text(prefix + 'dept', { value: loc.dept, list: 'dl-depts-' + prefix, ph: 'Ketik atau pilih unit kerja' }) + datalist('dl-depts-' + prefix, window.AS.deptNames()) });
  }
  function readLoc(root, prefix) { return { building: '', floor: '', dept: val(root, prefix + 'dept'), room: '' }; }

  window.GF = { head: head, section: section, q: q, text: text, area: area, select: select, radio: radio, ro: ro, actions: actions,
    val: val, err: err, clear: clear, need: need, firstErr: firstErr, show: show, wire: wire, done: done, kv: kv, datalist: datalist,
    locQs: locQs, readLoc: readLoc, draft: draft };
})();
