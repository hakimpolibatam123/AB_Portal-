/* IT Asset Management — Penempatan & Lacak Perangkat (Google Forms style): lookup → info & history → move / new person
   in charge of a placed device. ?id=<ID> (or the old Penempatan ?barcode=) preselects; #pindah scrolls to the form. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var DEV = null, finder = null, form = null, kept = null;
  var RECENT_KEY = 'as.recentTracked';
  var TITLE = 'Penempatan & Lacak Perangkat';

  AS.ready().then(function () {
    AU.consumeFlash();
    shell();
    var id = AU.qs('id') || AU.qs('barcode');
    if (id) { var d = AS.get(id); if (d) open(d, location.hash === '#pindah' || !AU.qs('id')); else finder.find(id); }
  }).catch(AU.fail);

  function recent() { try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]').filter(function (x) { return AS.get(x); }); } catch (e) { return []; } }
  function pushRecent(id) { try { var r = recent().filter(function (x) { return x !== id; }); r.unshift(id); localStorage.setItem(RECENT_KEY, JSON.stringify(r.slice(0, 5))); } catch (e) {} }

  function shell() {
    var rec = recent();
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: TITLE,
        meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-perpindahan' }) +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset', req: true, body: '<div data-finder></div>' +
        (rec.length ? '<div class="gf-chips"><span class="gf-chips__label">Terakhir dibuka:</span>' + rec.map(function (id) { return '<button type="button" class="gf-chip" data-pick="' + esc(id) + '">' + esc(AS.get(id).name) + '</button>'; }).join('') + '</div>' : '') }) +
      '<div data-result></div></form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, includeDisposed: true, label: 'Barcode / ID Aset', goLabel: 'Pilih', scanTitle: 'Scan Barcode Perangkat',
      onPick: function (d) { open(d); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form);
    form.addEventListener('click', function (e) { var b = e.target.closest('[data-pick]'); if (b) finder.set(AS.get(b.getAttribute('data-pick'))); });
    form.addEventListener('submit', function (e) { e.preventDefault(); if (DEV && AS.placed(DEV)) submitMove(); });
    // An unsent move comes back after leaving the page (asked first), on its device. Opening another device is not
    // interrupted.
    kept = GF.draft(form, { key: 'lacak', title: 'Pindahkan / Perbarui Penempatan',
      extra: function () { return DEV ? DEV.id : ''; },
      allow: function (id) { var b = AU.qs('id') || AU.qs('barcode'); return !!AS.get(id) && AS.placed(AS.get(id)) && (!b || AS.normId(b) === id); },
      label: function (dr) { var x = AS.get(dr.extra); return x ? 'Perangkat: ' + x.name + ' (' + x.id + ')' : ''; },
      restore: function (id) { var x = AS.get(id); if (x && (!DEV || DEV.id !== id)) open(x, true); else { var s = form.querySelector('[data-sec="move"]'); if (s) s.scrollIntoView({ behavior: 'smooth', block: 'start' }); } } });
    if (!AU.qs('id') && !AU.qs('barcode')) setTimeout(function () { finder.input.focus(); }, 60);
  }

  function open(d, toMove) {
    DEV = d; pushRecent(d.id); finder.input.value = d.id; finder.error('');
    history.replaceState(null, '', AU.trackUrl(d.id));
    document.title = d.name + ' - ' + TITLE + ' - Portal Monitoring IT RS Awal Bros Botania';
    drawResult();
    var target = (toMove && form.querySelector('[data-sec="move"]')) || form.querySelector('[data-sec="info"]');
    if (target) setTimeout(function () { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 80);
  }

  function infoHtml(d) {
    var c = AU.catOf(d), moves = AS.movesOf(d.id), prev = moves[0] ? moves[0].from : null, dead = d.status === 'disposed';
    var since = moves[0] ? moves[0].at : d.createdAt;
    var openSvc = AS.openService(d.id), openDsp = AS.openDisposal(d.id), svcs = AS.servicesOf(d.id);
    var notice = '';
    if (dead) {
      var r = AS.getDisposal(d.disposalId) || {};
      notice = '<div class="gf-card as-notice as-notice--muted">' + ic('archive') + '<div><b>Perangkat sudah dihapuskan (disposed)</b> pada ' + esc(AU.fmtDate(d.disposedAt)) + (r.method ? ' — ' + esc(r.method) : '') +
        '. Tidak lagi dilacak secara aktif; seluruh riwayat tetap tersimpan sebagai arsip.</div></div>';
    } else if (openDsp) notice = '<div class="gf-card as-notice as-notice--warn">' + ic('archive') + '<div><b>Ada pengajuan disposal lama yang belum selesai</b> (' + esc(openDsp.id) + '). <a class="gf-link" href="disposal?barcode=' + encodeURIComponent(d.id) + '">Selesaikan atau batalkan</a></div></div>';
    else if (openSvc) notice = '<div class="gf-card as-notice as-notice--info">' + ic('wrench') + '<div><b>Sedang diservis</b> — ' + esc(openSvc.type) + ', ' + esc(AS.SVC_STATUS[openSvc.status].label) + ' · ' + esc(openSvc.technician) + (openSvc.dueDate ? ' ' + AU.dueBadge(openSvc) : '') + '. <a class="gf-link" href="progres-servis?id=' + encodeURIComponent(openSvc.id) + '">Ubah status di Progres Servis</a></div></div>';

    var specRows = (c.spec || []).filter(function (s) { return d.spec && d.spec[s.key]; }).map(function (s) { return [s.label, s.type === 'date' ? AU.fmtDate(d.spec[s.key]) : d.spec[s.key]]; });
    return GF.section(1, 'Informasi Perangkat', '', null, ' data-sec="info"') +
      '<section class="gf-card"><div class="as-devhead"><span class="as-tile">' + ic(c.icon) + '</span><div class="as-devhead__text"><b>' + esc(d.name) + '</b>' +
      '<div class="as-devhead__meta">' + AU.code(d.id) + '<span>' + esc(AU.catName(d)) + '</span>' + AU.statusPill(d.status) + '</div></div></div>' +
      GF.kv([['Departemen', d.department], ['Merek / Model', [d.brand, d.model].filter(Boolean).join(' ')], ['Serial Number', d.serial], ['IP Address', d.ip], ['MAC Address', d.mac], ['Penanggung Jawab', d.pic]].concat(specRows)
        .concat([['Tanggal Pembelian', d.purchaseDate ? AU.fmtDate(d.purchaseDate) : ''], d.invoices && d.invoices.length ? ['Invoice Pembelian', AU.docsHtml(d.invoices), true] : null, ['Terdaftar', AU.fmtDateTime(d.createdAt) + ' · ' + d.createdBy], ['Catatan', d.notes]])) +
      '<div class="as-done__links" style="margin-top:14px">' + (dead ? '' : '<a class="gf-btn gf-btn--outline as-editbtn" href="registrasi?edit=' + encodeURIComponent(d.id) + '">' + ic('pencil') + 'Edit Data Perangkat</a>') +
      '</div></section>' +
      (dead ? '' : '<section class="gf-card"><h3 class="gf-q__title" style="margin-bottom:14px">Tindak Lanjut</h3>' +
        '<div style="display:flex;flex-wrap:wrap;gap:10px">' +
        (openSvc ? '<a class="gf-btn gf-btn--primary" href="progres-servis?id=' + encodeURIComponent(openSvc.id) + '">' + ic('activity') + 'Progres Servis</a>'
          : '<a class="gf-btn gf-btn--primary" href="servis?barcode=' + encodeURIComponent(d.id) + '">' + ic('wrench') + 'Servis Perangkat</a>') +
        '<a class="gf-btn gf-btn--ghost" href="serah-terima?barcode=' + encodeURIComponent(d.id) + '">' + ic('file-text') + 'Serah Terima</a>' +
        '<a class="gf-btn gf-btn--ghost" href="disposal?barcode=' + encodeURIComponent(d.id) + '">' + ic('archive') + 'Disposal Perangkat</a>' +
        '</div></section>') +
      notice +
      '<section class="gf-card"><h3 class="gf-q__title" style="margin-bottom:12px">Lokasi Perangkat</h3><div class="as-locpair">' +
      nowBox(d, dead, since) +
      '<div class="as-locbox"><span class="as-locs__k">' + ic('history') + 'Lokasi sebelumnya</span>' + (prev ? '<span class="as-locs__v">' + esc(dep(prev)) + '</span>' +'<span class="as-locs__s rs-muted">Hingga ' + esc(AU.fmtDateTime(moves[0].at)) + '</span>' : '<span class="as-locs__s rs-muted">Belum pernah dipindahkan sejak registrasi.</span>') + '</div>' +
      '</div></section>' +
      '<section class="gf-card"><div class="gf-q__head"><h3 class="gf-q__title">Riwayat Perpindahan</h3><span class="gf-q__badge">' + moves.length + ' catatan</span></div>' +
      (moves.length ? '<ol class="as-tl" style="margin-top:6px">' + moves.map(function (m) {
        return '<li><span class="as-tl__dot"></span><div class="as-tl__head"><span class="as-move"><span>' + esc(dep(m.from)) + '</span>' + ic('arrow-right') + '<b>' + esc(dep(m.to)) + '</b></span><span class="as-tl__time">' + esc(AU.fmtDateTime(m.at)) + '</span></div>' +
          '<div class="as-tl__body">' + esc(m.reason) + (m.note ? ' — ' + esc(m.note) : '') + (m.picTo && m.picTo !== m.picFrom ? '<br>Penanggung jawab: ' + esc(m.picFrom || '—') + ' → ' + esc(m.picTo) : '') + '</div><div class="as-tl__by">Diperbarui oleh ' + esc(m.by) + '</div></li>';
      }).join('') + '</ol>' : '<p class="gf-q__help">Belum ada perpindahan. Perangkat masih di lokasi registrasi.</p>') + '</section>' +
      (svcs.length ? '<section class="gf-card"><div class="gf-q__head"><h3 class="gf-q__title">Riwayat Servis</h3><span class="gf-q__badge">' + svcs.length + ' laporan</span></div><ul class="as-list">' + svcs.slice(0, 4).map(function (t) {
        return '<li><a class="as-list__item" href="progres-servis?id=' + encodeURIComponent(t.id) + '"><span class="as-list__text"><b>' + esc(t.type) + ' · ' + esc(t.id) + '</b><small>' + esc(t.problem) + '</small></span><span class="as-list__meta">' + esc(AU.fmtDate(t.reportDate)) + '<br>' + AU.svcPill(t.status) + '</span></a></li>';
      }).join('') + '</ul></section>' : '');
  }

  /* A location as shown here: its department / unit only (Ruangan is no longer asked; an older room-only record
     shows its room). */
  function dep(l) { return (l && (l.dept || l.room)) || '—'; }

  /* "Di Departemen": where the device is (placed: its unit; not placed yet: its department from Registrasi),
     with its person in charge. */
  function nowBox(d, dead, since) {
    var placed = AS.placed(d), pic = '<span class="as-locs__s">Penanggung jawab: <b>' + esc(d.pic || '—') + '</b></span>';
    var body = placed
      ? '<span class="as-locs__v">' + esc(dep(d.location)) + '</span>' + pic + '<span class="as-locs__s rs-muted">Sejak ' + esc(AU.fmtDateTime(since)) + '</span>'
      : '<span class="as-locs__v">' + esc(d.department || '—') + '</span>' + pic;
    return '<div class="as-locbox as-locbox--now"><span class="as-locs__k">' + ic('map-pin') + (dead ? 'Departemen terakhir' : 'Di Departemen') + '</span>' + body + '</div>';
  }

  function moveHtml(d) {
    return GF.section(2, 'Pindahkan / Perbarui Penempatan', '', null, ' data-sec="move" id="pindah"') +
      GF.q({ name: 'prev', title: 'Lokasi Sebelumnya', badge: 'Otomatis', body: GF.ro(esc(dep(d.location))) }) +
      GF.locQs('to_', null) +
      GF.q({ name: 'at', title: 'Tanggal & Waktu Perpindahan', req: true, body: GF.text('at', { type: 'datetime-local', value: AU.nowLocal(), max: AU.nowLocal() }) }) +
      GF.q({ name: 'by', title: 'Diperbarui Oleh', req: true, body: GF.text('by', { value: AS.me(), list: 'dl-techs' }) + GF.datalist('dl-techs', AS.TECHNICIANS) }) +
      GF.q({ name: 'reason', title: 'Alasan Perpindahan', req: true, help: 'Tidak perlu diisi bila lokasi tetap dan hanya penanggung jawab yang diganti.', body: GF.radio('reason', AS.MOVE_REASONS.filter(function (r) { return r !== 'Lainnya'; }), '', { other: true }) }) +
      GF.q({ name: 'pic', title: 'Penanggung Jawab Baru', badge: 'Opsional', help: d.pic ? 'Saat ini: ' + d.pic + '. Kosongkan bila tidak berubah.' : '', body: GF.text('pic', {}) }) +
      GF.q({ name: 'note', title: 'Keterangan', badge: 'Opsional', body: GF.area('note', {}) }) +
      GF.actions('Simpan Penempatan', { icon: 'save', reset: 'Kosongkan bagian ini' });
  }

  function drawResult(confirmHtml) {
    var d = DEV, box = form.querySelector('[data-result]');
    // No location form for a disposed device, or one without a placement (its department is set in Registrasi).
    box.innerHTML = infoHtml(d) + (d.status === 'disposed' || (!AS.placed(d) && !confirmHtml) ? '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a></div>'
      : confirmHtml || moveHtml(d));
    AU.render(box);
    var rs = box.querySelector('[data-reset]');
    if (rs) rs.addEventListener('click', function () { kept.clear(); drawResult(); box.querySelector('[data-sec="move"]').scrollIntoView({ block: 'start' }); });
    box.querySelectorAll('[data-again]').forEach(function (b) { b.addEventListener('click', function () { drawResult(); form.querySelector('[data-sec="move"]').scrollIntoView({ behavior: 'smooth', block: 'start' }); }); });
    box.querySelectorAll('[data-other]').forEach(function (b) { b.addEventListener('click', function () { DEV = null; history.replaceState(null, '', 'lacak'); shell(); }); });
  }

  function doneCard(title, text, rows) {
    return '<div class="gf-card gf-head as-done" data-sec="move" id="pindah"><span class="as-done__icon">' + ic('circle-check') + '</span><h1>' + esc(title) + '</h1>' +
      '<p class="gf-head__desc">' + esc(text) + '</p>' + GF.kv(rows) +
      '<div class="as-done__links"><button type="button" class="gf-link-btn" style="padding:0" data-again>' + ic('rotate-ccw') + 'Kirim pembaruan lain</button>' +
      '<button type="button" class="gf-link-btn" style="padding:0" data-other>' + ic('search') + 'Pilih perangkat lain</button>' +
      '<a class="gf-link" href="rekap-perpindahan">' + ic('chart-column') + 'Lihat rekap</a></div></div>';
  }

  function saved(d, toast, card) {
    AS.save().then(function () {
      kept.clear(); // saved: nothing left to continue
      DEV = AS.get(d.id) || d;
      AU.toast(toast[0], toast[1], 'success');
      drawResult(card);
      var c = form.querySelector('[data-sec="move"]'); if (c) c.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, AU.fail);
  }

  function submitMove() {
    var d = DEV; GF.clear(form);
    var to = GF.readLoc(form, 'to_'), pic = GF.val(form, 'pic');
    var stay = AS.sameLoc(to, d.location);
    var ok = GF.need(form, stay ? ['to_dept', 'at', 'by'] : ['to_dept', 'at', 'by', 'reason']);
    if (ok && stay && (!pic || pic === (d.pic || ''))) { GF.err(form, 'to_dept', 'Lokasi baru sama dengan lokasi saat ini. Bila hanya mengganti penanggung jawab, isi Penanggung Jawab Baru.'); ok = false; }
    var at = GF.val(form, 'at'), atIso = at ? new Date(at).toISOString() : '';
    if (at && new Date(at).getTime() > Date.now() + 60000) { GF.err(form, 'at', 'Waktu tidak boleh di masa depan.'); ok = false; }
    var last = AS.movesOf(d.id)[0];
    if (at && last && atIso < last.at) { GF.err(form, 'at', 'Lebih awal dari perpindahan terakhir (' + AU.fmtDateTime(last.at) + ').'); ok = false; }
    if (!ok) { GF.firstErr(form); return; }
    if (stay) {
      // Same place, new person in charge: recorded as a placement update (no move in the history).
      var old = d.pic || '—';
      try { AS.place(d.id, { location: d.location, pic: pic, at: atIso, by: GF.val(form, 'by') }); } catch (err) { return AU.fail(err); }
      return saved(d, ['Penanggung jawab diperbarui', d.name + ': ' + pic], doneCard('Penanggung jawab diperbarui', 'Lokasi ' + d.name + ' tetap; penanggung jawabnya diganti.',
        [['Departemen', dep(d.location)], ['Penanggung jawab', old + ' → ' + pic], ['Tanggal & waktu', AU.fmtDateTime(atIso)], ['Diperbarui oleh', GF.val(form, 'by')]]));
    }
    var m;
    try { m = AS.move(d.id, { to: to, reason: GF.val(form, 'reason'), note: GF.val(form, 'note'), at: atIso, by: GF.val(form, 'by'), pic: pic }); }
    catch (err) { return AU.fail(err); }
    saved(d, ['Lokasi diperbarui', d.name + ' → ' + to.dept], doneCard('Lokasi berhasil diperbarui', 'Perpindahan ' + d.name + ' telah dicatat di riwayat.',
      [['Departemen sebelumnya', dep(m.from)], ['Departemen baru', dep(m.to)], ['Tanggal & waktu', AU.fmtDateTime(m.at)], ['Diperbarui oleh', m.by], ['Alasan', m.reason + (m.note ? ' — ' + m.note : '')]]));
  }
})();
