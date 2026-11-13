/* IT Asset Management — Serah Terima Perangkat (Google Forms style): devices, the two parties and their signatures
 * (typed, drawn or uploaded, then placed on the signature box), saved as a berita acara with a PDF.
 * ?barcode=<ID> starts with that device; ?id=<BAST-…> opens a saved one. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var LIST = [], form = null, finder = null, slots = {}, kept = null;
  var CONDITIONS = ['Baik, berfungsi normal', 'Baik, ada catatan', 'Rusak ringan'];
  // The signature window (Ketik / Gambar / Unggah) is ASign.dialog, shared with Form Clearance (aset-sign.js).
  var PAD_W = window.ASign.PAD_W, PAD_H = window.ASign.PAD_H;

  AS.ready().then(function () {
    AU.consumeFlash();
    var id = AU.qs('id'), r = id ? AS.getHandover(id) : null;
    if (r) return detail(r);
    render();
    var b = AU.qs('barcode');
    if (b) { var d = AS.get(b); if (d) finder.set(d); else finder.find(b); }
  }).catch(AU.fail);

  /* ---------------- signature slot (the window itself is ASign.dialog) ---------------- */

  /* One signature box: the signature is dragged to its place on the box and resized; blob() is what is saved. */
  function slot(box, label, nameOf) {
    box.innerHTML = '<div class="as-sig"><div class="as-sig__label">' + esc(label) + '</div><div class="as-sig__box"><canvas class="as-sig__pad" width="' + PAD_W + '" height="' + PAD_H + '" hidden></canvas>' +
      '<button type="button" class="as-sig__empty" data-sign>' + ic('pencil') + '<span>Klik untuk tanda tangan</span></button></div>' +
      '<div class="as-sig__tools" hidden><label>Ukuran <input type="range" min="15" max="100" data-size aria-label="Ukuran tanda tangan"></label><button type="button" class="as-textbtn" data-sign>Ganti</button><button type="button" class="as-textbtn" data-remove>Hapus</button></div>' +
      '<p class="as-sig__hint" hidden>Geser tanda tangan untuk meletakkannya.</p></div>';
    AU.render(box);
    var pad = box.querySelector('canvas'), empty = box.querySelector('.as-sig__empty'), tools = box.querySelector('.as-sig__tools'), hint = box.querySelector('.as-sig__hint'), size = box.querySelector('[data-size]');
    var img = null, cx = 0.5, cy = 0.5, w = 0.5, drag = null;
    function paint(g, guide) {
      g.fillStyle = '#fff'; g.fillRect(0, 0, PAD_W, PAD_H);
      if (guide) { g.strokeStyle = '#d6dedc'; g.setLineDash([8, 8]); g.beginPath(); g.moveTo(40, PAD_H - 44); g.lineTo(PAD_W - 40, PAD_H - 44); g.stroke(); g.setLineDash([]); }
      if (!img) return;
      var dw = w * PAD_W, dh = dw * img.height / img.width;
      g.drawImage(img, cx * PAD_W - dw / 2, cy * PAD_H - dh / 2, dw, dh);
    }
    function show() { var on = !!img; pad.hidden = !on; empty.hidden = on; tools.hidden = !on; hint.hidden = !on; if (on) paint(pad.getContext('2d'), true); }
    function set(c) { img = c; cx = 0.5; cy = 0.5; w = Math.min(0.7, 0.72 * PAD_H / c.height * c.width / PAD_W); size.value = Math.round(w * 100); show(); }
    function at(e) { var r = pad.getBoundingClientRect(); return [(e.clientX - r.left) / r.width, (e.clientY - r.top) / r.height]; }
    box.querySelectorAll('[data-sign]').forEach(function (b) { b.addEventListener('click', function () { window.ASign.dialog(nameOf(), set); }); });
    box.querySelector('[data-remove]').addEventListener('click', function () { img = null; show(); });
    size.addEventListener('input', function () { w = +size.value / 100; show(); });
    pad.addEventListener('pointerdown', function (e) { var p = at(e); drag = [p[0] - cx, p[1] - cy]; pad.setPointerCapture(e.pointerId); });
    pad.addEventListener('pointermove', function (e) { if (!drag) return; var p = at(e); cx = Math.min(1, Math.max(0, p[0] - drag[0])); cy = Math.min(1, Math.max(0, p[1] - drag[1])); show(); });
    ['pointerup', 'pointercancel'].forEach(function (ev) { pad.addEventListener(ev, function () { drag = null; }); });
    return { has: function () { return !!img; }, blob: function (file) {
      if (!img) return Promise.resolve([]);
      var out = document.createElement('canvas'); out.width = PAD_W; out.height = PAD_H; paint(out.getContext('2d'), false);
      return new Promise(function (ok) { out.toBlob(ok, 'image/png'); }).then(function (b) { return AS.upload([new File([b], file, { type: 'image/png' })], 'ttd'); });
    } };
  }

  /* ---------------- new serah terima ---------------- */

  function render() {
    LIST = []; slots = {};
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: 'Serah Terima Perangkat', meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-serah-terima' }) +
      GF.section(1, 'Perangkat') +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset', req: true, help: 'Scan atau ketik satu per satu. Bisa lebih dari satu perangkat.', body: '<div data-finder></div><div data-list></div>' }) +
      GF.section(2, 'Pihak Serah Terima') +
      GF.q({ name: 'fromName', title: 'Nama Yang Menyerahkan', req: true, body: GF.text('fromName', { value: AS.me() }) }) +
      GF.q({ name: 'fromRole', title: 'Jabatan / Unit Yang Menyerahkan', badge: 'Opsional', body: GF.text('fromRole', {}) }) +
      GF.q({ name: 'toName', title: 'Nama Yang Menerima', req: true, help: 'Otomatis menjadi penanggung jawab perangkat.', body: GF.text('toName', {}) }) +
      GF.q({ name: 'toRole', title: 'Jabatan Yang Menerima', badge: 'Opsional', body: GF.text('toRole', {}) }) +
      GF.q({ name: 'toDept', title: 'Departemen Yang Menerima', req: true, help: 'Otomatis menjadi departemen perangkat.', body: GF.text('toDept', { list: 'dl-depts', ph: 'Ketik atau pilih departemen' }) + GF.datalist('dl-depts', AS.deptNames()) }) +
      GF.section(3, 'Detail Serah Terima') +
      GF.q({ name: 'date', title: 'Tanggal Serah Terima', req: true, body: GF.text('date', { type: 'date', value: AU.today(), max: AU.today() }) }) +
      GF.q({ name: 'condition', title: 'Kondisi Perangkat', req: true, body: GF.radio('condition', CONDITIONS, CONDITIONS[0]) }) +
      GF.q({ name: 'note', title: 'Keterangan', badge: 'Opsional', body: GF.area('note', {}) }) +
      GF.section(4, 'Tanda Tangan') +
      GF.q({ name: 'signs', title: 'Letakkan Tanda Tangan', badge: 'Opsional', help: 'Bisa diketik, digambar, atau diunggah. Yang belum tanda tangan bisa dilengkapi nanti.', body: '<div class="as-sigs"><div data-slot="from"></div><div data-slot="to"></div></div>' }) +
      GF.actions('Simpan Serah Terima', { icon: 'save' }) + '</form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, goLabel: 'Tambah', scanTitle: 'Scan Barcode Perangkat',
      filter: function (d) {
        if (LIST.some(function (x) { return x.id === d.id; })) return d.name + ' sudah ada di daftar.';
        return ['servis', 'disposal'].indexOf(d.status) !== -1 ? d.name + ' berstatus ' + AS.STATUS[d.status].label + ', tidak dapat diserahterimakan.' : null;
      },
      onPick: function (d) { LIST.push(d); finder.input.value = ''; GF.err(form, 'barcode', ''); drawList(); finder.input.focus(); if (kept) kept.touch(); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form);
    slots.from = slot(form.querySelector('[data-slot="from"]'), 'Yang Menyerahkan', function () { return GF.val(form, 'fromName'); });
    slots.to = slot(form.querySelector('[data-slot="to"]'), 'Yang Menerima', function () { return GF.val(form, 'toName'); });
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
    form.querySelector('[data-reset]').addEventListener('click', function () {
      AU.confirm({ title: 'Kosongkan formulir?', text: 'Jawaban yang sudah diisi akan dihapus.', ok: 'Kosongkan' }).then(function (ok) { if (ok) { kept.clear(); history.replaceState(null, '', 'serah-terima'); render(); } });
    });
    form.querySelector('[data-list]').addEventListener('click', function (e) {
      var b = e.target.closest('[data-drop-dev]'); if (!b) return;
      LIST.splice(+b.getAttribute('data-drop-dev'), 1); drawList(); kept.touch();
    });
    // Unsent answers and the devices listed come back after leaving the page (asked first); signatures are not
    // kept. Opened with a device (?barcode=), "Pakai Data Tadi" adds the earlier devices to it.
    kept = GF.draft(form, { key: 'serah-terima', title: 'Serah Terima Perangkat', extraCounts: true,
      extra: function () { return LIST.map(function (d) { return d.id; }).join(','); },
      label: function (dr) { var n = dr.extra ? dr.extra.split(',').length : 0; return (n ? n + ' perangkat' : '') + (dr.values.toName ? (n ? ', diterima ' : 'Diterima ') + dr.values.toName : ''); },
      restore: function (ids) { (ids ? ids.split(',') : []).forEach(function (id) { var x = AS.get(id); if (x && !LIST.some(function (y) { return y.id === id; })) finder.set(x); }); } });
  }

  function drawList() {
    var box = form.querySelector('[data-list]');
    box.innerHTML = LIST.length ? '<ul class="as-list as-sig-devices">' + LIST.map(function (d, i) {
      return '<li><div class="as-list__item"><span class="as-tile as-tile--sm">' + ic(AU.catOf(d).icon) + '</span><span class="as-list__text"><b>' + esc(d.name) + '</b><small>' + esc([d.id, [d.brand, d.model].filter(Boolean).join(' '), d.serial].filter(Boolean).join(' · ')) + '</small></span>' +
        '<button type="button" class="as-iconbtn" data-drop-dev="' + i + '" aria-label="Lepas ' + esc(d.name) + '">' + ic('x') + '</button></div></li>';
    }).join('') + '</ul>' : '';
    AU.render(box);
  }

  function submit() {
    GF.clear(form);
    var ok = GF.need(form, ['fromName', 'toName', 'toDept', 'date', 'condition']), date = GF.val(form, 'date');
    if (!LIST.length) { GF.err(form, 'barcode', 'Tambahkan minimal satu perangkat.'); ok = false; }
    if (date > AU.today()) { GF.err(form, 'date', 'Tanggal tidak boleh di masa depan.'); ok = false; }
    if (!ok) return GF.firstErr(form);
    var toName = GF.val(form, 'toName'), toDept = GF.val(form, 'toDept'), btn = form.querySelector('button[type="submit"]');
    // Saved, then its PDF downloads at once on the page of the saved serah terima (?unduh=1, see detail).
    AU.confirm({ title: 'Simpan serah terima?', ok: 'Simpan & Unduh PDF', text: '<b>' + LIST.length + ' perangkat</b> akan tercatat di bawah tanggung jawab <b>' + esc(toName) + '</b>, departemen <b>' + esc(toDept) + '</b>. PDF serah terima langsung diunduh setelah tersimpan.' }).then(function (yes) {
      if (!yes) return;
      btn.disabled = true;
      var input = { deviceIds: LIST.map(function (d) { return d.id; }), date: date, fromName: GF.val(form, 'fromName'), fromRole: GF.val(form, 'fromRole'), toName: toName, toRole: GF.val(form, 'toRole'), toDept: toDept, condition: GF.val(form, 'condition'), note: GF.val(form, 'note') };
      slots.from.blob('ttd-menyerahkan.png').then(function (from) {
        return slots.to.blob('ttd-menerima.png').then(function (to) {
          input.signFrom = from; input.signTo = to;
          var r = AS.handover(input);
          return AS.save().then(function () { kept.clear(); AU.flash('Serah terima tersimpan', r.id + ' · PDF sedang diunduh'); location.href = 'serah-terima?id=' + encodeURIComponent(r.id) + '&unduh=1'; });
        });
      }).catch(function (err) { btn.disabled = false; AU.fail(err); });
    });
  }

  /* ---------------- a saved serah terima ---------------- */

  function detail(r) {
    var pdf = 'serah-terima/pdf?id=' + encodeURIComponent(r.id);
    // Just saved (?unduh=1): the PDF downloads once; the address is cleaned so a reload does not download it again.
    if (AU.qs('unduh') === '1') {
      history.replaceState(null, '', 'serah-terima?id=' + encodeURIComponent(r.id));
      setTimeout(function () { var a = document.createElement('a'); a.href = pdf; a.download = ''; document.body.appendChild(a); a.click(); a.remove(); }, 400);
    }
    document.title = r.id + ' - Serah Terima Perangkat - Portal Monitoring IT RS Awal Bros Botania';
    var sign = function (role, label, file, name) {
      return '<div>' + (file ? '<div class="as-sig"><div class="as-sig__label">' + esc(label) + '</div><div class="as-sig__box"><img class="as-sig__img" src="' + esc(AS.fileUrl(file)) + '" alt="Tanda tangan ' + esc(name) + '"></div></div>'
        : '<div data-slot="' + role + '"></div><button type="button" class="gf-btn gf-btn--outline as-sig__save" data-save="' + role + '">' + ic('save') + 'Simpan Tanda Tangan</button>') + '<p class="as-sig__name">' + esc(name) + '</p></div>';
    };
    app.innerHTML = '<div class="gf">' + GF.head({ title: 'Serah Terima Perangkat', meta: [['Nomor', r.id], ['Tanggal', AU.fmtDate(r.date)]], rekap: 'rekap-serah-terima' }) +
      GF.section(1, 'Pihak Serah Terima') + '<section class="gf-card">' + GF.kv([['Yang Menyerahkan', r.fromName + (r.fromRole ? ' · ' + r.fromRole : '')], ['Yang Menerima', r.toName + (r.toRole ? ' · ' + r.toRole : '')], ['Departemen', r.toDept], ['Kondisi', r.condition], ['Keterangan', r.note], ['Dicatat', AU.fmtDateTime(r.createdAt) + ' · ' + r.createdBy]]) + '</section>' +
      GF.section(2, 'Perangkat', '', r.devices.length + ' perangkat') + '<section class="gf-card"><ul class="as-list">' + r.devices.map(function (d) {
        return '<li><a class="as-list__item" href="' + AU.trackUrl(d.id) + '"><span class="as-list__text"><b>' + esc(d.name) + '</b><small>' + esc([d.id, [d.brand, d.model].filter(Boolean).join(' '), d.serial].filter(Boolean).join(' · ')) + '</small></span>' + ic('chevron-right') + '</a></li>';
      }).join('') + '</ul></section>' +
      GF.section(3, 'Tanda Tangan') + '<section class="gf-card"><div class="as-sigs">' + sign('from', 'Yang Menyerahkan', r.signFrom, r.fromName) + sign('to', 'Yang Menerima', r.signTo, r.toName) + '</div></section>' +
      '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a><span class="gf-actions__spacer"></span><a class="gf-btn gf-btn--text" href="serah-terima">Buat Baru</a><a class="gf-btn gf-btn--primary" href="' + pdf + '">' + ic('download') + 'Export PDF</a></div></div>';
    AU.render(app);
    app.querySelector('.gf-req-note').style.visibility = 'hidden'; // a saved document has nothing to fill in
    [['from', 'Yang Menyerahkan', r.fromName], ['to', 'Yang Menerima', r.toName]].forEach(function (p) {
      var box = app.querySelector('[data-slot="' + p[0] + '"]'); if (!box) return;
      var s = slot(box, p[1], function () { return p[2]; }), btn = app.querySelector('[data-save="' + p[0] + '"]');
      btn.addEventListener('click', function () {
        if (!s.has()) return AU.toast('Belum ada tanda tangan', 'Buat tanda tangan terlebih dahulu.', 'error');
        btn.disabled = true;
        s.blob('ttd-' + p[0] + '.png').then(function (file) {
          AS.signHandover(r.id, { role: p[0], file: file });
          return AS.save().then(function () { AU.toast('Tanda tangan tersimpan', p[2], 'success'); detail(AS.getHandover(r.id)); });
        }).catch(function (err) { btn.disabled = false; AU.fail(err); });
      });
    });
  }
})();
