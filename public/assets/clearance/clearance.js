/* Form Clearance (ClearanceController) — the list of clearances, or one form. In the portal only the employee's
 * data and the Unit IT part are filled in: each access checked (Tidak ada masalah / Sudah dihapus, or Bermasalah)
 * with a short note, a general note, and the signature of Ka. Unit IT (typed, drawn or uploaded with the shared
 * window of aset-sign.js; it can be changed or removed). The export (on the list) is a PDF recap of every
 * employee recorded. Data comes from <script id="clearance-data">: {mode: list|form, list, record, definition}. */
(function () {
  'use strict';
  var AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var D = JSON.parse(document.getElementById('clearance-data').textContent);
  var API = document.body.getAttribute('data-clearance-api'), BASE = API.replace(/\/clearance$/, '');
  var DEF = D.definition;

  function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; }
  function enc(v) { return encodeURIComponent(v); }
  function post(path, body, json) {
    var headers = { Accept: 'application/json', 'X-CSRF-Token': csrf() };
    if (json) headers['Content-Type'] = 'application/json';
    return fetch(API + path, { method: 'POST', credentials: 'same-origin', headers: headers, body: json ? JSON.stringify(body) : body })
      .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { if (!r.ok) throw new Error(j.message || 'Permintaan gagal (' + r.status + ').'); return j; }); });
  }
  /* Same rule as Clearance::progress(). */
  function progress(r) {
    var it = r.it || {}, signed = !!it.sign, issue = Object.keys(it.access || {}).some(function (k) { return it.access[k] === 'issue'; });
    return { signed: signed, issue: issue, label: issue ? 'Ada akses bermasalah' : signed ? 'Sudah TTD IT' : 'Belum TTD IT' };
  }
  var MEMO = 'cl.ttd:' + (window.AS ? window.AS.me() : '');
  function memoGet() {
    try {
      var m = JSON.parse(localStorage.getItem(MEMO) || 'null');
      if (!m) return null;
      if (!(m.file && m.file.id)) m.file = null;
      return m.file || m.name ? m : null;
    } catch (e) { return null; }
  }
  function memoSet(m) {
    try { if (m) localStorage.setItem(MEMO, JSON.stringify(m)); else localStorage.removeItem(MEMO); } catch (e) {}
  }
  function progressPill(p) { return AU.pill({ tone: p.issue ? 'err' : p.signed ? 'ok' : 'warn', label: p.label }); }

  AU.consumeFlash();
  if (D.mode === 'list') drawList(); else drawForm(D.record);

  /* ---------------- list ---------------- */

  function drawList() {
    var all = D.list || [];
    // ?baru=<id>: the clearance just saved, marked in the list (the address is cleaned so a reload does not mark it again).
    var fresh = new URLSearchParams(location.search).get('baru') || '';
    if (fresh && history.replaceState) history.replaceState(null, '', API);
    app.innerHTML = '<section class="rs-card"><div class="rs-card__head"><div><h2>' + ic('user-check') + 'Daftar Clearance</h2><p data-count>' + all.length + ' clearance</p></div>' +
      '<div class="rs-actions">' + (all.length ? '<a class="rs-btn rs-btn--sm2" href="' + API + '/excel" title="Rekap semua karyawan yang didata (Excel)">' + ic('table-2') + 'Ekspor Excel</a>' +
        '<a class="rs-btn rs-btn--sm2" href="' + API + '/pdf" title="Rekap semua karyawan yang didata (PDF)">' + ic('download') + 'Ekspor PDF</a>' : '') +
      '<a class="rs-btn rs-btn--primary rs-btn--sm2" href="' + API + '/isi">' + ic('circle-plus') + 'Buat Clearance</a></div></div>' +
      (all.length ? '<div class="fr-filters" style="padding:14px 20px"><label class="as-search">' + ic('search') + '<input class="pm-input" type="search" placeholder="Cari nama, NIK, nomor…" data-q aria-label="Cari clearance"></label></div>' : '') +
      '<div data-table></div></section>';
    var box = app.querySelector('[data-table]'), q = app.querySelector('[data-q]');
    function draw() {
      var s = q ? q.value.trim().toLowerCase() : '';
      var rows = all.filter(function (r) { return !s || [r.id, r.name, r.nik, r.position].join(' ').toLowerCase().indexOf(s) !== -1; });
      box.innerHTML = !all.length
        ? '<div class="rs-empty">' + ic('user-check') + '<b>Belum ada clearance</b><span>Buat clearance untuk karyawan yang mengundurkan diri.</span><a class="rs-btn rs-btn--primary rs-btn--sm2" href="' + API + '/isi">' + ic('circle-plus') + 'Buat Clearance</a></div>'
        : !rows.length ? '<div class="rs-empty">' + ic('search') + '<b>Tidak ada yang cocok</b><span>Ubah kata kunci pencarian.</span></div>'
        : '<div class="rs-table-wrap"><table class="rs-table as-table"><thead><tr><th scope="col">No. Clearance</th><th scope="col">Karyawan</th><th scope="col">Bagian / Jabatan</th><th scope="col">Tgl. Pengunduran Diri</th><th scope="col">Unit IT</th><th scope="col">Diperbarui</th><th scope="col" class="as-td-tight"><span class="as-sr">Aksi</span></th></tr></thead><tbody>' +
          rows.map(function (r) {
            // A click on the row shows it in a window; the pencil edits it, the bin removes it.
            return '<tr data-id="' + esc(r.id) + '" tabindex="0" title="Lihat data"' + (r.id === fresh ? ' class="cl-row--saved" data-saved' : '') + '><td>' + AU.code(r.id) + '</td>' +
              '<td><span class="as-name"><b>' + esc(r.name) + '</b>' + (r.nik ? '<small>NIK ' + esc(r.nik) + '</small>' : '') + '</span></td>' +
              '<td>' + esc(r.position || '—') + '</td><td class="rs-nowrap">' + esc(r.resignDate ? AU.fmtDate(r.resignDate) : '—') + '</td>' +
              '<td>' + progressPill(r.progress) + '</td>' +
              '<td class="rs-nowrap"><span class="as-name" style="min-width:0"><span>' + esc(AU.fmtDate(r.updatedAt)) + '</span><small>' + esc(r.updatedBy || '') + '</small></span></td>' +
              '<td class="as-td-tight"><span class="as-rowacts cl-acts">' +
                '<button type="button" class="as-iconbtn cl-acts__del" data-del title="Hapus" aria-label="Hapus clearance ' + esc(r.name) + '">' + ic('trash-2') + '</button>' +
                '<a class="as-iconbtn" href="' + API + '/isi?id=' + enc(r.id) + '" title="Edit" aria-label="Edit clearance ' + esc(r.name) + '">' + ic('pencil') + '</a>' +
              '</span></td></tr>';
          }).join('') + '</tbody></table></div>';
      AU.render(box);
    }
    function recOf(el) { var tr = el.closest('tr[data-id]'), id = tr && tr.getAttribute('data-id'); return all.filter(function (x) { return x.id === id; })[0] || null; }
    // Removed: off the list and the counter, without reloading the page.
    function remove(r) {
      return AU.confirm({ title: 'Hapus clearance?', danger: true, ok: 'Ya, hapus',
        text: 'Clearance <b>' + esc(r.id) + '</b> atas nama <b>' + esc(r.name) + '</b> akan dihapus dan tidak ikut di rekap ekspor. Tindakan ini tidak bisa dibatalkan.' }).then(function (yes) {
        if (!yes) return false;
        return post('/hapus', { id: r.id }, true).then(function () {
          all = all.filter(function (x) { return x.id !== r.id; }); D.list = all;
          app.querySelector('[data-count]').textContent = all.length + ' clearance';
          draw();
          AU.toast('Clearance dihapus', r.name + ' · ' + r.id, 'success');
          return true;
        }, function (err) { AU.toast('Clearance belum terhapus', err.message, 'error'); return false; });
      });
    }
    if (q) q.addEventListener('input', draw);
    app.addEventListener('click', function (e) {
      var r = recOf(e.target); if (!r) return;
      if (e.target.closest('[data-del]')) { remove(r); return; }
      if (!e.target.closest('a, button')) view(r, remove);
    });
    app.addEventListener('keydown', function (e) { if (e.key === 'Enter' && e.target.matches && e.target.matches('tr[data-id]')) { var r = recOf(e.target); if (r) view(r, remove); } });
    draw(); AU.render(app);
    var saved = app.querySelector('[data-saved]');
    if (saved) saved.scrollIntoView({ block: 'center' });
    donePopup(fresh);
  }

  /* The "lihat" window of one clearance: the employee, each access Unit IT checked with its note, the signatures of
     the employee and Ka. Unit IT, and who made / changed it. Edit opens the form; Hapus asks first (remove). */
  var ACC_VIEW = { ok: { tone: 'ok', label: 'Tidak ada masalah' }, issue: { tone: 'err', label: 'Bermasalah' }, '': { tone: 'muted', label: 'Belum diperiksa' } };
  function viewSig(role, s, fallbackName) {
    return '<div class="cl-view__sig"><small class="cl-view__role">' + esc(role) + '</small>' + (s && s.file
      ? '<img src="' + esc(BASE + '/berkas/' + s.file.id) + '" alt="Tanda tangan ' + esc(role) + '"><b>' + esc(s.name || fallbackName || '—') + '</b>' + (s.at ? '<small>Ditandatangani ' + esc(AU.fmtDateTime(s.at)) + '</small>' : '')
      : '<p class="cl-view__none">Belum ditandatangani.</p>') + '</div>';
  }
  function view(r, remove) {
    var it = r.it || {}, acc = it.access || {}, notes = it.notes || {}, sign = it.sign || null;
    var m = AU.modal({ title: r.name, subtitle: esc(r.id), size: 'md',
      body: '<div class="cl-view">' + GF.kv([['No. Clearance', AU.code(r.id), true], ['Nama Karyawan', r.name], ['NIK', r.nik], ['Bagian / Jabatan', r.position],
          ['Tgl. Pengunduran Diri', r.resignDate ? AU.fmtDate(r.resignDate) : ''], ['Status Unit IT', progressPill(r.progress || progress(r)), true]]) +
        '<h3 class="as-sec-title">Akses Karyawan (Unit IT)</h3><ul class="cl-view__acc">' + DEF.access.map(function (a) {
          var s = ACC_VIEW[acc[a.key] || ''] || ACC_VIEW[''];
          return '<li><span>' + esc(a.label) + (notes[a.key] ? '<small>' + esc(notes[a.key]) + '</small>' : '') + '</span>' + AU.pill(s) + '</li>';
        }).join('') + '</ul>' +
        '<h3 class="as-sec-title">Tanda Tangan</h3><div class="cl-view__sigs">' + viewSig('Ka. Unit IT', sign, '') + viewSig('Karyawan yang mengundurkan diri', r.employeeSign, r.name) + '</div>' +
        '<p class="cl-view__meta">Dibuat ' + esc(AU.fmtDateTime(r.createdAt) + (r.createdBy ? ' · ' + r.createdBy : '')) + '<br>Diperbarui ' + esc(AU.fmtDateTime(r.updatedAt) + (r.updatedBy ? ' · ' + r.updatedBy : '')) + '</p></div>',
      note: '<button type="button" class="rs-btn rs-btn--danger" data-del-in>' + ic('trash-2') + 'Hapus</button>',
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Tutup</button><a class="rs-btn rs-btn--primary" href="' + API + '/isi?id=' + enc(r.id) + '">' + ic('pencil') + 'Edit</a>' });
    m.$('[data-del-in]').addEventListener('click', function () { remove(r).then(function (gone) { if (gone) m.close(); }); });
    return m;
  }

  // The "saved" window in the middle of the screen, once, after Simpan Clearance (sessionStorage cl.done).
  function donePopup(fresh) {
    var d = null;
    try { d = JSON.parse(sessionStorage.getItem('cl.done') || 'null'); sessionStorage.removeItem('cl.done'); } catch (e) {}
    if (!d || !fresh || d.id !== fresh) return;
    AU.modal({ title: 'Data tersimpan', size: 'sm',
      body: '<div class="cl-done">' + ic('circle-check') + '<b>Clearance ' + (d.isNew ? 'berhasil disimpan' : 'berhasil diperbarui') + '</b>' +
        '<span>' + esc(d.name) + '</span><span class="cl-done__meta">' + AU.code(d.id) + (d.progress ? progressPill(d.progress) : '') + '</span></div>',
      foot: '<a class="rs-btn rs-btn--ghost" href="' + API + '/isi">' + ic('circle-plus') + 'Buat Clearance Lagi</a><button type="button" class="rs-btn rs-btn--primary" data-x autofocus>OK</button>',
      onMount: function (m) { m.el.classList.add('cl-done-ov'); } });
  }

  /* ---------------- form ---------------- */

  function drawForm(rec) {
    // SIGN: Ka. Unit IT; EMP: the resigning employee (named after the employee, not remembered).
    var r = rec || {}, it = r.it || { access: {}, notes: {}, sign: null }, SIGN = it.sign || null, EMP = r.employeeSign || null, kept = null;
    // "Ingat TTD & nama": the remembered signature and name fill every form not signed yet (this browser, this user).
    var memo = memoGet(), signer = SIGN && SIGN.name ? SIGN.name : '';
    if (!SIGN && memo) { if (memo.file) SIGN = { file: memo.file }; signer = memo.name || ''; }
    var plain = function () { return JSON.stringify({ it: SIGN, emp: EMP }, function (k, v) { return k === 'url' ? undefined : v; }); }, SIGN0 = plain();

    var head = '<header class="gf-card gf-head"><h1>Formulir Clearance</h1><dl class="gf-meta"><div><dt>No.</dt><dd>' + esc(rec ? rec.id : 'Baru') + '</dd></div>' +
      (rec ? '<div><dt>Unit IT</dt><dd>' + esc(progress(rec).label) + '</dd></div><div><dt>Diperbarui</dt><dd>' + esc(AU.fmtDateTime(rec.updatedAt) + ' · ' + rec.updatedBy) + '</dd></div>' : '') + '</dl>' +
      '<p class="gf-head__desc">Isi data karyawan dan bagian Unit IT. Ekspor PDF dan Excel ada di Daftar Clearance: rekap semua karyawan yang sudah didata.</p>' +
      '<div class="gf-head__foot"><span class="gf-req-note"><span class="gf-req">*</span> Wajib Diisi</span><span class="as-head-links">' +
      '<a class="gf-link" href="' + API + '">' + ic('list') + 'Daftar Clearance</a></span></div></header>';
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' + head +
      GF.section(1, 'Data Karyawan', '') +
      GF.q({ name: 'name', title: 'Nama', req: true, body: GF.text('name', { value: r.name, maxlength: 150 }) }) +
      GF.q({ name: 'nik', title: 'NIK', badge: 'Opsional', body: GF.text('nik', { value: r.nik, maxlength: 50 }) }) +
      GF.q({ name: 'position', title: 'Bagian / Jabatan Terakhir', badge: 'Opsional', body: GF.text('position', { value: r.position, maxlength: 150 }) }) +
      GF.q({ name: 'resignDate', title: 'Tanggal Pengunduran Diri', badge: 'Opsional', help: 'Tampil di rekap ekspor PDF / Excel.', body: GF.text('resignDate', { type: 'date', value: r.resignDate }) }) +
      GF.section(2, 'Unit IT', '') +
      GF.q({ name: 'access', title: 'Akses Karyawan', badge: 'Opsional', help: 'Berdasarkan data yang ada pada kami sampai dengan form ini diajukan, kami menyatakan bahwa pemohon:', body: accessTable() }) +
      GF.q({ name: 'it_sign', title: 'Tanda Tangan', badge: 'Opsional', help: 'Karyawan yang mengundurkan diri dan Ka. Unit IT. Diketik, digambar, atau diunggah dari foto. Bisa diganti kapan saja.',
        body: '<div class="cl-sigs cl-sigs--pair"><div class="cl-sig" data-sig="emp"><div class="cl-sig__role">(Karyawan yang mengundurkan diri)</div><div class="cl-sig__box" data-sigbox></div>' +
          '<p class="cl-sig__name cl-sig__name--auto" data-empname></p><p class="cl-sig__hint">Nama mengikuti isian Nama di atas.</p></div>' +
          '<div class="cl-sig" data-sig="it"><div class="cl-sig__role">(Ka. Unit IT)</div><div class="cl-sig__box" data-sigbox></div>' +
          '<input class="gf-input cl-sig__name" name="it_signer" maxlength="100" placeholder="Nama Ka. Unit IT" aria-label="Nama Ka. Unit IT" value="' + esc(signer) + '">' +
          '<label class="cl-sig__remember"><input type="checkbox" data-remember' + (memo ? ' checked' : '') + '><span>Ingat TTD &amp; nama</span></label>' +
          '<p class="cl-sig__hint" data-remember-hint></p></div></div>' }) +
      '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="' + API + '">' + ic('arrow-left') + 'Kembali ke Daftar</a><span class="gf-actions__spacer"></span>' +
      '<button type="button" class="gf-btn gf-btn--text" data-reset>' + (rec ? 'Batalkan perubahan' : 'Kosongkan formulir') + '</button>' +
      '<button type="submit" class="gf-btn gf-btn--primary">' + ic('save') + 'Simpan Clearance</button></div></form>';
    var form = app.querySelector('#gf-form'), sigEl = form.querySelector('[data-sig="it"]'), empEl = form.querySelector('[data-sig="emp"]');
    var rememberEl = form.querySelector('[data-remember]'), signerEl = form.querySelector('[name="it_signer"]');
    GF.wire(form);
    paintSig();
    paintEmp();
    paintRemember();
    // The employee's name under their signature follows the Nama field.
    function paintEmpName() { var n = GF.val(form, 'name'); empEl.querySelector('[data-empname]').textContent = n || '(nama karyawan)'; }
    form.querySelector('[name="name"]').addEventListener('input', paintEmpName);
    paintEmpName();

    /* Remembered signature and name: kept while "Ingat TTD & nama" is ticked, follow a new signature or name,
       gone when unticked. A name alone is remembered too (the signature comes once it is made). */
    function remember() {
      var name = signerEl.value.trim();
      if (!rememberEl.checked) { memoSet(null); }
      else if (SIGN || name) { memoSet({ file: SIGN ? SIGN.file : null, name: name }); }
      else { memoSet(null); }
      paintRemember();
    }
    function paintRemember() {
      var m = memoGet();
      form.querySelector('[data-remember-hint]').textContent = !rememberEl.checked ? 'Centang agar TTD dan nama tidak perlu diisi ulang setiap mengisi.'
        : !m ? 'Buat tanda tangan atau isi nama dulu, lalu akan diingat.'
        : (m.file ? 'TTD' + (m.name ? ' dan nama (' + m.name + ')' : '') : 'Nama (' + m.name + ')') + ' otomatis terisi di setiap clearance yang belum ditandatangani.'
          + (m.file ? '' : ' Buat TTD agar ikut diingat.');
    }
    rememberEl.addEventListener('change', remember);
    signerEl.addEventListener('input', function () { if (rememberEl.checked) remember(); });
    AU.render(app);

    function accessTable() {
      return '<div class="cl-acc__tools"><button type="button" class="gf-btn gf-btn--outline as-editbtn cl-acc__all" data-accall="ok">' + ic('check-check') + 'Centang semua: Tidak ada masalah</button>' +
        '<button type="button" class="as-textbtn" data-accall="">Kosongkan pilihan</button></div>' +
        '<div class="cl-acc"><div class="cl-acc__row cl-acc__head"><span>Akses</span><span>Tidak ada masalah / Sudah dihapus</span><span>Bermasalah</span><span>Catatan</span></div>' +
        DEF.access.map(function (a) {
          var opt = function (v, l) { return '<label class="cl-acc__opt"><input type="radio" name="acc_' + a.key + '" value="' + v + '"' + ((it.access || {})[a.key] === v ? ' checked' : '') + '><span class="as-sr">' + esc(a.label) + ': ' + l + '</span></label>'; };
          return '<div class="cl-acc__row"><span>' + esc(a.label) + '</span>' + opt('ok', 'tidak ada masalah / sudah dihapus') + opt('issue', 'bermasalah') +
            '<input class="gf-input cl-acc__note" name="accnote_' + a.key + '" maxlength="' + DEF.noteMax + '" aria-label="Catatan ' + esc(a.label) + '" value="' + esc((it.notes || {})[a.key] || '') + '"></div>';
        }).join('') + '</div>';
    }
    /* One signature box: the picture with Ganti / Hapus, or the button to sign; who = 'it' | 'emp'. */
    function paintBox(el, S, who, busy) {
      var box = el.querySelector('[data-sigbox]');
      box.innerHTML = busy ? '<span class="cl-sig__busy">Mengunggah tanda tangan…</span>'
        : S ? '<img class="cl-sig__img" src="' + esc(S.url || BASE + '/berkas/' + S.file.id) + '" alt="Tanda tangan ' + (who === 'emp' ? 'karyawan' : 'Ka. Unit IT') + '">' +
          '<div class="cl-sig__tools"><button type="button" class="as-textbtn" data-sign="' + who + '">Ganti</button><button type="button" class="as-textbtn" data-unsign="' + who + '">Hapus</button></div>'
        : '<button type="button" class="cl-sig__empty" data-sign="' + who + '">' + ic('pencil') + '<span>Klik untuk tanda tangan</span></button>';
      AU.render(box);
    }
    function paintSig(busy) { paintBox(sigEl, SIGN, 'it', busy); }
    function paintEmp(busy) { paintBox(empEl, EMP, 'emp', busy); }
    function sign(who) {
      var emp = who === 'emp', nameEl = form.querySelector('[name="it_signer"]');
      window.ASign.dialog(emp ? GF.val(form, 'name') : nameEl.value.trim(), function (canvas, typedName) {
        (emp ? paintEmp : paintSig)(true);
        canvas.toBlob(function (blob) {
          var body = new FormData(); body.append('file', new File([blob], emp ? 'ttd-karyawan.png' : 'ttd-ka-unit-it.png', { type: 'image/png' }));
          post('/berkas', body, false).then(function (j) {
            var s = { file: j.file, url: canvas.toDataURL('image/png') };
            if (emp) { EMP = s; paintEmp(); }
            else {
              SIGN = s;
              if (typedName && !nameEl.value.trim()) nameEl.value = typedName;
              paintSig(); if (rememberEl.checked) remember();
            }
            if (kept) kept.touch();
          }, function (err) { (emp ? paintEmp : paintSig)(); AU.toast('Tanda tangan gagal diunggah', err.message, 'error'); });
        }, 'image/png');
      });
    }
    form.addEventListener('click', function (e) {
      var sb = e.target.closest('[data-sign]'), ub = e.target.closest('[data-unsign]');
      if (sb) return sign(sb.getAttribute('data-sign'));
      if (ub) { if (ub.getAttribute('data-unsign') === 'emp') { EMP = null; paintEmp(); } else { SIGN = null; paintSig(); } if (kept) kept.touch(); return; }
      var all = e.target.closest('[data-accall]');
      if (all) { // every access "Tidak ada masalah / Sudah dihapus", or none chosen
        var v = all.getAttribute('data-accall');
        DEF.access.forEach(function (a) {
          form.querySelectorAll('[name="acc_' + a.key + '"]').forEach(function (el) { el.checked = el.value === v; });
        });
        form.querySelector('[name="acc_' + DEF.access[0].key + '"]').dispatchEvent(new Event('change', { bubbles: true }));
        if (kept) kept.touch();
        return;
      }
      if (e.target.closest('[data-reset]')) {
        AU.confirm({ title: rec ? 'Batalkan perubahan?' : 'Kosongkan formulir?', text: rec ? 'Isian kembali seperti yang terakhir disimpan.' : 'Semua jawaban di formulir ini akan dihapus.', ok: rec ? 'Batalkan' : 'Kosongkan' })
          .then(function (ok) { if (ok) { kept.clear(); drawForm(rec); } });
      }
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); save(); });

    function save() {
      GF.clear(form);
      if (!GF.need(form, ['name'])) return GF.firstErr(form);
      var out = { id: rec ? rec.id : '', name: GF.val(form, 'name'), nik: GF.val(form, 'nik'), position: GF.val(form, 'position'), resignDate: GF.val(form, 'resignDate'),
        it: { access: {}, notes: {}, sign: SIGN ? { file: SIGN.file, name: GF.val(form, 'it_signer') } : null },
        employeeSign: EMP ? { file: EMP.file } : null };
      DEF.access.forEach(function (a) { out.it.access[a.key] = GF.val(form, 'acc_' + a.key); out.it.notes[a.key] = GF.val(form, 'accnote_' + a.key); });
      var btn = form.querySelector('button[type="submit"]'); btn.disabled = true;
      post('/simpan', { clearance: out }, true).then(function (j) {
        kept.clear();
        // Done: back to the list (the saved one marked there), not the same form again.
        try { sessionStorage.setItem('cl.done', JSON.stringify({ id: j.clearance.id, name: j.clearance.name, progress: j.progress, isNew: !rec })); } catch (e) {}
        location.href = API + '?baru=' + enc(j.clearance.id);
      }, function (err) { btn.disabled = false; AU.toast('Clearance belum tersimpan', err.message, 'error'); });
    }

    // Unsent answers (and a new signature) come back after leaving the page, asked first (aset-gf.js GF.draft).
    kept = GF.draft(form, { key: 'clearance:' + (rec ? rec.id : 'baru'), title: rec ? 'Clearance ' + rec.name : 'Form Clearance', extraCounts: true,
      extra: function () { var j = plain(); return j === SIGN0 ? '' : j; },
      label: function (dr) { return dr.values.name ? 'Karyawan: ' + dr.values.name : ''; },
      // Kept signatures come back: {it, emp}, or an older draft's Ka. Unit IT signature alone ({file}).
      restore: function (x) {
        var s = null; try { s = JSON.parse(x || 'null'); } catch (e) {}
        if (!s) return;
        if (s.file && s.file.id) s = { it: s };
        if (s.it && s.it.file && s.it.file.id) { SIGN = s.it; paintSig(); }
        if (s.emp && s.emp.file && s.emp.file.id) { EMP = s.emp; paintEmp(); }
      } });
  }
})();
