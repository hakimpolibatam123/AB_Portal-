/* IT Asset Management — Registrasi Perangkat (Google Forms style). ?code=<barcode> prefills, ?edit=<ID> edits. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var EDIT = null, keep = {}, invoices = null;

  app.addEventListener('click', function (e) { if (e.target.closest('[data-import]')) openImport(); });

  /* Import Excel: template, upload, a row-by-row check, then the valid rows are stored. */
  function openImport() {
    var file = null, valid = 0;
    var m = AU.modal({ title: 'Import Perangkat dari Excel', size: 'lg',
      body: '<ol class="as-import-steps"><li><b>Unduh template</b><p>Isi satu baris untuk satu perangkat. Petunjuknya ada di sheet "Petunjuk".</p><a class="rs-btn rs-btn--ghost" href="registrasi/template">' + ic('download') + 'Unduh Template Excel</a></li>' +
        '<li><b>Unggah file yang sudah diisi</b><p>Belum tersimpan sampai Anda menekan tombol Import.</p><label class="gf-drop as-sig-drop"><input type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-xlsx hidden>' + ic('upload') + '<span class="gf-drop__title" data-fname>Pilih file Excel (.xlsx)</span><span class="gf-drop__formats">Maks. 5 MB · maks. 1000 perangkat</span></label></li></ol>' +
        '<p class="pm-err" data-ierr hidden>' + ic('circle-alert') + '<span class="as-errt"></span></p><div data-iresult></div>',
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Batal</button><button type="submit" class="rs-btn rs-btn--primary" data-igo hidden>' + ic('upload') + '<span data-igo-t></span></button>',
      onSubmit: function () {
        if (!file || !valid) return;
        go.disabled = true; fail('');
        AS.importFile(file, true).then(function (r) {
          AU.flash('Import berhasil', r.valid + ' perangkat didaftarkan' + (r.failed ? ', ' + r.failed + ' baris dilewati.' : '.'));
          location.href = 'rekap#perangkat';
        }, function (err) { go.disabled = false; fail(err.message); });
      } });
    var err = m.$('[data-ierr]'), result = m.$('[data-iresult]'), go = m.$('[data-igo]');
    function fail(t) { err.hidden = !t; err.querySelector('.as-errt').textContent = t || ''; }
    function show(r) {
      valid = r.valid;
      result.innerHTML = '<div class="as-import-sum">' + AU.pill({ tone: 'ok', label: r.valid + ' siap diimport' }) + (r.failed ? AU.pill({ tone: 'err', label: r.failed + ' bermasalah (dilewati)' }) : '') + '</div>' +
        '<div class="rs-table-wrap as-import-table"><table class="rs-table as-table"><thead><tr><th scope="col">Baris</th><th scope="col">ID Aset</th><th scope="col">Nama</th><th scope="col">Kategori</th><th scope="col">Departemen</th><th scope="col">Hasil</th></tr></thead><tbody>' +
        r.rows.map(function (x) { return '<tr' + (x.error ? ' class="as-import-bad"' : '') + '><td>' + x.row + '</td><td>' + esc(x.id || '—') + '</td><td>' + esc(x.name || '—') + '</td><td>' + esc(x.category || '—') + '</td><td>' + esc(x.department || '—') + '</td><td>' + (x.error ? esc(x.error) : 'Siap') + '</td></tr>'; }).join('') + '</tbody></table></div>';
      go.hidden = !valid; m.$('[data-igo-t]').textContent = 'Import ' + valid + ' Perangkat';
    }
    m.$('[data-xlsx]').addEventListener('change', function (e) {
      var f = e.target.files && e.target.files[0]; e.target.value = '';
      if (!f) return;
      file = null; valid = 0; go.hidden = true; result.innerHTML = ''; fail('');
      m.$('[data-fname]').textContent = 'Memeriksa ' + f.name + '…';
      AS.importFile(f, false).then(function (r) { file = f; m.$('[data-fname]').textContent = f.name; show(r); },
        function (x) { m.$('[data-fname]').textContent = 'Pilih file Excel (.xlsx)'; fail(x.message); });
    });
  }

  AS.ready().then(function () {
    AU.consumeFlash();
    var e = AU.qs('edit');
    if (e) {
      EDIT = AS.get(e);
      if (!EDIT || EDIT.status === 'disposed') { EDIT = null; AU.toast('Tidak dapat diedit', 'Perangkat ' + e + (AS.get(e) ? ' sudah dihapuskan.' : ' tidak ditemukan.'), 'error'); }
      else { document.title = 'Edit Data Perangkat - Portal Monitoring IT RS Awal Bros Botania'; var c = document.querySelector('.rsab-crumbs > span:last-child'); if (c) c.textContent = 'Edit Data Perangkat'; }
    }
    render(EDIT ? null : AU.qs('code'));
  }).catch(AU.fail);

  function render(prefill) {
    var d = EDIT || {}; keep = {};
    // ?code= from a scan on another form ("Daftarkan perangkat ini"): a valid new code is filled in and locked.
    var fixed = !EDIT && prefill && !idProblem(prefill) ? AS.normId(prefill) : '';
    var locked = EDIT && ['servis', 'disposal'].indexOf(EDIT.status) !== -1;
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: EDIT ? 'Edit Data Perangkat' : 'Registrasi Perangkat',
        meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())], EDIT ? ['ID Aset', EDIT.id] : null], rekap: 'rekap#perangkat' }) +
      (EDIT || fixed ? '' : '<section class="gf-card as-import-card"><div><b>Banyak perangkat sekaligus?</b><p>Isi template Excel, lalu import semuanya dalam satu kali unggah.</p></div><button type="button" class="gf-btn gf-btn--outline" data-import>' + ic('upload') + 'Import Excel</button></section>') +

      GF.section(1, 'Identitas Perangkat', '', null, ' data-sec="id"') +
      // Some devices (a laser pointer, a small accessory) carry no barcode label: the portal then makes the ID.
      (EDIT || fixed ? '' : GF.q({ name: 'idmode', title: 'Apakah perangkat memiliki label barcode?', req: true,
        body: GF.radio('idmode', [{ value: 'ya', label: 'Ya, ada label barcode' },
          { value: 'tidak', label: 'Tidak ada barcode', note: 'ID aset dibuat otomatis oleh sistem sesuai kategori (mis. AB-BTN-OTH-0001).' }], 'ya') }) +
        GF.q({ name: 'autoid', title: 'ID Aset', hidden: true, body: '<div data-autoid></div>' })) +
      GF.q({ name: 'id', title: 'Barcode / ID Aset', req: true,
        body: EDIT ? GF.ro(AU.code(EDIT.id))
          : '<div class="as-lookup--gf"><div class="as-lookup__row">' + GF.text('id', { mono: true, ph: 'Scan atau ketik barcode', value: fixed || prefill || '' })
              .replace('class="gf-input gf-mono"', 'class="gf-input gf-mono as-lookup__input"' + (fixed ? ' readonly aria-readonly="true"' : '')) +
            (fixed ? '' : '<button type="button" class="gf-btn gf-btn--outline" data-cam>' + ic('camera') + '<span>Scan Kamera</span></button>') + '</div>' +
            (fixed ? '<p class="gf-q__help">' + ic('lock') + ' Diisi otomatis dari hasil scan.</p>' : '') +
            '<p class="gf-status" data-idstatus hidden></p></div>' }) +
      GF.q({ name: 'category', title: 'Kategori Perangkat', req: true, body: GF.radio('category', AS.CATEGORIES.map(function (c) { return { value: c.key, label: c.label }; }), d.category, { cols: true }) }) +
      // A device that fits no category: "Lainnya" + its own type, suggested again for the next one.
      GF.q({ name: 'kind', title: 'Jenis Perangkat', req: true, hidden: d.category !== 'other',
        help: 'Untuk perangkat yang tidak ada di daftar kategori, mis. Remote Laser, Proyektor, Fingerprint. Pilih dari saran atau ketik baru.',
        body: GF.text('kind', { value: AS.kindOf(d), list: 'dl-kinds', ph: 'mis. Remote Laser' }) + GF.datalist('dl-kinds', AS.kinds()) }) +
      GF.q({ name: 'name', title: 'Nama Perangkat', req: true, body: GF.text('name', { value: d.name }) }) +
      GF.q({ name: 'department', title: 'Departemen', req: true, help: 'Departemen pemilik / pengguna perangkat. Pilih dari daftar atau ketik nama departemen.',
        body: GF.text('department', { value: d.department, list: 'dl-depts' }) + GF.datalist('dl-depts', departments()) }) +
      GF.q({ name: 'pic', title: 'Penanggung Jawab / Pengguna', badge: 'Opsional', help: 'Nama orang yang memakai atau bertanggung jawab atas perangkat ini.',
        body: GF.text('pic', { value: d.pic }) }) +
      GF.q({ name: 'brandmodel', title: 'Merek dan Model', req: true,
        body: '<div class="gf-inline"><label class="gf-sub" style="flex:1 1 200px"><span>Merek</span>' + GF.text('brand', { value: d.brand, list: 'dl-brands' }) + '</label>' +
          '<label class="gf-sub" style="flex:1 1 200px"><span>Model</span>' + GF.text('model', { value: d.model }) + '</label></div>' + GF.datalist('dl-brands', brands()) }) +
      GF.q({ name: 'serial', title: 'Serial Number', body: GF.text('serial', { value: d.serial, mono: true }) }) +

      '<div data-net></div><div data-spec></div>' +

      GF.section(0, 'Status & Pengadaan', '', null, ' data-sec="status"') +
      GF.q({ name: 'status', title: 'Status Perangkat', req: !locked,
        body: locked ? GF.ro(AU.statusPill(EDIT.status))
          : GF.radio('status', [{ value: 'aktif', label: 'Aktif' }, { value: 'cadangan', label: 'Cadangan' }, { value: 'rusak', label: 'Rusak' }], d.status || 'aktif') }) +
      GF.q({ name: 'purchaseDate', title: 'Tanggal Pembelian', badge: 'Opsional', body: GF.text('purchaseDate', { type: 'date', value: d.purchaseDate, max: AU.today() }) }) +
      GF.q({ name: 'invoices', title: 'Invoice Pembelian', badge: 'Opsional', help: 'Invoice, faktur, atau bukti pembelian perangkat (maks. 5 file).', body: AU.fileField('invoices', { max: 5 }) }) +
      GF.q({ name: 'notes', title: 'Catatan Tambahan', badge: 'Opsional', body: GF.area('notes', { value: d.notes }) }) +
      GF.actions(EDIT ? 'Simpan Perubahan' : 'Kirim', { icon: EDIT ? 'save' : 'send' }) +
      '</form>';
    AU.render(app);
    var form = app.querySelector('#gf-form');
    GF.wire(form);
    invoices = AU.filePicker(form, 'invoices', { max: 5, existing: EDIT ? EDIT.invoices || [] : [] });
    drawDynamic(); number(); idMode();

    form.addEventListener('change', function (e) {
      if (e.target.name === 'idmode') { idMode(); if (noCode()) { var c0 = form.querySelector('[name="category"]:checked') || form.querySelector('[name="category"]'); c0.focus(); } }
      if (e.target.name === 'category') {
        GF.show(form, 'kind', e.target.value === 'other');
        if (e.target.value === 'other') setTimeout(function () { form.querySelector('[name="kind"]').focus(); }, 0);
        capture();
        drawDynamic(); number(); idMode();
      }
      if (e.target.name === 'net_on' || e.target.name === 'spec_connection') { capture(); drawDynamic(); number(); }
    });
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(form); });
    form.querySelector('[data-reset]').addEventListener('click', function () {
      AU.confirm({ title: 'Kosongkan formulir?', text: 'Semua jawaban di formulir ini akan dihapus.', ok: 'Kosongkan' }).then(function (ok) { if (ok) { history.replaceState(null, '', location.pathname + (EDIT ? '?edit=' + encodeURIComponent(EDIT.id) : '')); render(); } });
    });
    var idEl = form.querySelector('input[name="id"]');
    if (idEl) {
      var t;
      // A USB scanner types the label link letter by letter: the box shows only its asset code once it is done
      // (Enter or leaving the box), never mid-way.
      var tidy = function () { if (/\//.test(idEl.value)) idEl.value = AS.normId(idEl.value); };
      idEl.addEventListener('input', function () { clearTimeout(t); t = setTimeout(checkId, 400); });
      idEl.addEventListener('change', function () { tidy(); checkId(); });
      idEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); tidy(); if (checkId()) { var c = form.querySelector('[name="category"]:checked') || form.querySelector('[name="category"]'); c.focus(); } } });
      var cam = form.querySelector('[data-cam]');
      if (cam) cam.addEventListener('click', function () {
        AU.scan({ title: 'Scan Barcode Perangkat Baru', camera: true, onResult: function (code) { idEl.value = code; checkId(); GF.err(form, 'id', ''); return true; } });
      });
      if (prefill) checkId(); else setTimeout(function () { idEl.focus(); }, 60);
    }
  }

  /* "Tidak ada barcode": hide the barcode box and show the ID the portal will give the device. */
  function noCode() { var f = app.querySelector('#gf-form'); return !EDIT && !!f && GF.val(f, 'idmode') === 'tidak'; }
  function idMode() {
    var form = app.querySelector('#gf-form'), box = form.querySelector('[data-autoid]');
    if (!box) return;
    var on = noCode(), cat = GF.val(form, 'category');
    GF.show(form, 'id', !on); GF.show(form, 'autoid', on);
    if (on) GF.err(form, 'id', '');
    box.innerHTML = GF.ro(cat ? AU.code(AS.nextId(cat)) : '<span class="as-ro__empty">Pilih kategori perangkat untuk melihat ID</span>',
      'Dibuat otomatis oleh sistem. Tulis atau tempel ID ini pada perangkat agar mudah dicari.');
    AU.render(box);
  }

  /* The hospital's departments plus any other one already typed for a device. */
  function departments() {
    return AS.deptNames();
  }
  function brands() { var s = {}; AS.devices({ includeDisposed: true }).forEach(function (d) { if (d.brand) s[d.brand] = 1; }); return Object.keys(s).sort(); }
  function idProblem(code) {
    if (!AS.normId(code)) return 'Pertanyaan ini wajib diisi';
    var bad = AS.codeProblem(code); if (bad) return bad;
    code = AS.normId(code);
    var d = AS.get(code); if (d) return 'Barcode sudah terdaftar sebagai ' + d.name + (d.status === 'disposed' ? ' (sudah dihapuskan).' : '.');
    return null;
  }
  function checkId() {
    var form = app.querySelector('#gf-form'), el = form.querySelector('input[name="id"]'), st = form.querySelector('[data-idstatus]');
    if (!el) return true;
    var v = AS.normId(el.value);
    if (!v) { st.hidden = true; return false; }
    var p = idProblem(v), d = AS.get(v);
    st.hidden = false;
    st.className = 'gf-status ' + (p ? 'err' : 'ok');
    st.innerHTML = p ? ic('circle-x') + '<span>' + esc(p) + (d ? ' <a class="gf-link" href="' + AU.trackUrl(d.id) + '">Lihat perangkat</a>' : '') + '</span>'
      : ic('circle-check') + '<span>' + esc(v) + ' tersedia</span>';
    AU.render(st);
    return !p;
  }

  /* network + category-specific questions */
  function capture() {
    app.querySelectorAll('[data-net] [name], [data-spec] [name]').forEach(function (el) {
      if (/__other$/.test(el.name)) return; // its text is read with the choice below
      // A "Lainnya" answer is kept as what was written (the questions are drawn again from these values).
      var v = el.value === '__other' ? GF.val(app, el.name) : el.value;
      if (el.type === 'radio') { if (el.checked) keep[el.name] = v; } else keep[el.name] = v;
    });
  }
  function kv(n, dflt) { return keep[n] != null ? keep[n] : dflt; }
  function netOn(c) {
    if (!c) return false;
    if (c.net === true) return true;
    if (c.key === 'printer') return /Jaringan|Wi-Fi/.test(kv('spec_connection', (EDIT && EDIT.spec && EDIT.spec.connection) || ''));
    var v = kv('net_on', EDIT ? (EDIT.ip || EDIT.mac ? 'ya' : 'tidak') : '');
    return v === 'ya';
  }
  function drawDynamic() {
    var form = app.querySelector('#gf-form'), c = AS.CAT[GF.val(form, 'category')];
    var netBox = form.querySelector('[data-net]'), specBox = form.querySelector('[data-spec]');
    var spec = (EDIT && EDIT.spec) || {};
    if (!c || c.net === false) netBox.innerHTML = '';
    else {
      var on = netOn(c);
      netBox.innerHTML = GF.section(0, 'Jaringan', '', null, ' data-sec="net"') +
        (c.net === 'optional' && c.key !== 'printer' ? GF.q({ name: 'net_on', title: 'Apakah perangkat terhubung ke jaringan?', req: true, body: GF.radio('net_on', [{ value: 'ya', label: 'Ya, memiliki IP Address' }, { value: 'tidak', label: 'Tidak' }], kv('net_on', EDIT ? (EDIT.ip || EDIT.mac ? 'ya' : 'tidak') : '')) }) : '') +
        (c.key === 'printer' && !on ? '' : '') +
        (on ? GF.q({ name: 'ip', title: 'IP Address', req: c.net === true && !c.dhcp, body: GF.text('ip', { mono: true, maxlength: 45, value: kv('ip', EDIT ? EDIT.ip : '') }) }) +
          GF.q({ name: 'mac', title: 'MAC Address', body: GF.text('mac', { mono: true, maxlength: 17, value: kv('mac', EDIT ? EDIT.mac : '') }) }) : '');
    }
    if (!c || !c.spec.length) specBox.innerHTML = '';
    else {
      specBox.innerHTML = GF.section(0, 'Detail ' + c.label, '', 'Opsional', ' data-sec="spec"') +
        c.spec.map(function (s) {
          var n = 'spec_' + s.key, v = kv(n, spec[s.key] || '');
          var body = s.type === 'select' ? (s.netSwitch || s.options.length <= 5 ? GF.radio(n, s.options, v) : GF.select(n, s.options, v))
            : GF.text(n, { value: v, mono: s.mono, inputmode: s.inputmode, ph: s.ph, list: s.options ? 'dl-' + n : '', type: s.type === 'date' ? 'date' : 'text' }) +
              (s.options ? GF.datalist('dl-' + n, s.options) : '');
          return GF.q({ name: n, title: s.label, help: s.help, body: body });
        }).join('');
    }
    AU.render(netBox); AU.render(specBox);
  }
  function number() { var i = 0; app.querySelectorAll('.gf-section__no').forEach(function (n) { n.textContent = ++i; }); }

  function submit(form) {
    GF.clear(form); capture();
    var c = AS.CAT[GF.val(form, 'category')];
    var ok = GF.need(form, ['category', 'name', 'department', 'status', 'net_on']);
    var auto = noCode(), id = EDIT ? EDIT.id : auto ? '' : AS.normId(GF.val(form, 'id'));
    if (!EDIT && !auto) { var p = idProblem(id); if (p) { GF.err(form, 'id', p); ok = false; } }
    var kind = c && c.key === 'other' ? GF.val(form, 'kind') : '';
    if (c && c.key === 'other' && !kind) { GF.err(form, 'kind', 'Isi jenis perangkat, mis. Remote Laser.'); ok = false; }
    if (!GF.val(form, 'brand') || !GF.val(form, 'model')) { GF.err(form, 'brandmodel', 'Isi merek dan model perangkat.'); ok = false; }
    var ip = GF.val(form, 'ip'), mac = GF.val(form, 'mac'), serial = GF.val(form, 'serial');
    if (c && netOn(c)) {
      if (form.querySelector('.gf-q[data-q="ip"] .gf-req') && !ip) { GF.err(form, 'ip', 'IP Address wajib untuk perangkat jaringan.'); ok = false; }
      // IP and MAC are free text (no format check); only a value already used by another device is refused.
      if (ip) { var cf = AS.conflicts('ip', ip, EDIT && EDIT.id); if (cf) { GF.err(form, 'ip', 'IP sudah dipakai oleh ' + cf.name + ' (' + cf.id + ').'); ok = false; } }
      if (mac) { var cm = AS.conflicts('mac', mac, EDIT && EDIT.id); if (cm) { GF.err(form, 'mac', 'MAC sudah tercatat pada ' + cm.name + ' (' + cm.id + ').'); ok = false; } }
    } else { ip = ''; mac = ''; }
    if (serial) { var cs = AS.conflicts('serial', serial, EDIT && EDIT.id); if (cs) { GF.err(form, 'serial', 'Serial number sudah tercatat pada ' + cs.name + ' (' + cs.id + ').'); ok = false; } }
    var pd = GF.val(form, 'purchaseDate');
    if (pd && pd > AU.today()) { GF.err(form, 'purchaseDate', 'Tanggal pembelian tidak boleh di masa depan.'); ok = false; }
    if (!ok) { GF.firstErr(form); return; }

    var sp = {}; (c.spec || []).forEach(function (s) {
      var v = GF.val(form, 'spec_' + s.key);
      if (v && s.unit && /^\d+([.,]\d+)?$/.test(v)) v = v.replace(',', '.') + ' ' + s.unit; // "23.8" → "23.8 inch"
      if (v) sp[s.key] = s.key === 'pairedWith' ? AS.normId(v) : v;
    });
    if (kind) sp.kind = kind;
    var data = { name: GF.val(form, 'name'), department: GF.val(form, 'department'), pic: GF.val(form, 'pic'), category: c.key, brand: GF.val(form, 'brand'), model: GF.val(form, 'model'), serial: serial, ip: ip, mac: mac,
      purchaseDate: pd, notes: GF.val(form, 'notes'), spec: sp };
    var st = GF.val(form, 'status'), btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    // Purchase invoices go to the portal first; the device then lists them (kept ones + new uploads).
    invoices.upload('invoice').then(function (list) {
      data.invoices = list;
      try {
        if (EDIT) {
          if (st) data.status = st;
          AS.update(EDIT.id, data);
          return AS.save().then(function () { AU.flash('Data perangkat diperbarui', EDIT.name); location.href = AU.trackUrl(EDIT.id); });
        }
        data.id = id; data.status = st || 'aktif';
        if (auto) data.autoId = true;
        var d = AS.register(data);
        return AS.save().then(function () { done(saved(d, data)); });
      } catch (err) { return Promise.reject(err); }
    }).catch(function (err) { btn.disabled = false; AU.fail(err); });
  }

  /* The device as the server stored it. An automatic ID may differ from the page's guess if another admin
     registered one at the same moment: then it is the newest device of that name and department. */
  function saved(d, data) {
    var got = AS.get(d.id);
    if (got && got.name === data.name && got.department === data.department) return got;
    if (!data.autoId) return got || d;
    return AS.devices({ includeDisposed: true }).filter(function (x) { return x.name === data.name && x.department === data.department; })
      .sort(function (a, b) { return String(b.createdAt).localeCompare(String(a.createdAt)); })[0] || d;
  }

  // ?dari=<form>: the form whose scan found the code unregistered; the done screen leads back to it.
  // Penempatan and Lacak are one page now (lacak), linked below for every new device.
  var FROM = { servis: 'Lanjutkan ke Servis Perangkat', disposal: 'Lanjutkan ke Disposal Perangkat' };
  var from = FROM[AU.qs('dari')] ? AU.qs('dari') : '';
  function done(d) {
    history.replaceState(null, '', location.pathname);
    var back = from ? [{ href: from + '?barcode=' + encodeURIComponent(d.id), label: FROM[from], icon: 'chevron-right' }] : [];
    GF.done(app, {
      title: 'Perangkat berhasil diregistrasi',
      text: d.name + ' tercatat dengan status ' + AS.STATUS[d.status].label + '.',
      html: GF.kv([['ID Aset', AU.code(d.id), true], ['Departemen', d.department], ['Penanggung Jawab', d.pic], ['Kategori', AU.catName(d)], ['Merek / Model', d.brand + ' ' + d.model], ['IP Address', d.ip], ['Didaftarkan', AU.fmtDateTime(d.createdAt) + ' · ' + d.createdBy],
        d.invoices && d.invoices.length ? ['Invoice Pembelian', AU.docsHtml(d.invoices), true] : null]),
      links: back.concat([{ href: AU.trackUrl(d.id), label: 'Lihat perangkat ini', icon: 'search' },
        { id: 'again', label: 'Registrasi perangkat lain', icon: 'rotate-ccw', fn: function () { render(); } },
        { href: 'rekap#perangkat', label: 'Lihat rekap', icon: 'chart-column' }])
    });
  }
})();
