/* IT Asset Management — Disposal Perangkat (Google Forms style).
 * One step, no request or approval: fill the form, confirm, and the device is archived. Nothing is deleted.
 * A request left open by the former flow is finished by the same form, or cancelled. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var DEV = null, R = null, MODE = '', finder = null, form = null, picker = null, kept = null;

  AS.ready().then(function () {
    AU.consumeFlash();
    render();
    var b = AU.qs('barcode') || AU.qs('new');
    var rid = AU.qs('id'); if (!b && rid) { var r = AS.getDisposal(rid); if (r) b = r.deviceId; }
    if (b) { var d = AS.get(b); if (d) finder.set(d); else finder.find(b); }
  }).catch(AU.fail);

  /* Requests left open by the former flow, to finish or cancel them. */
  function chips() {
    var open = AS.state().disposals.filter(function (r) { return r.status === 'pending' || r.status === 'approved'; });
    if (!open.length) return '';
    return GF.q({ name: 'open', title: 'Pengajuan lama yang belum selesai', body: '<div class="as-chip-list"><div class="as-chip-group">' +
      open.map(function (r) { return '<button type="button" class="gf-chip" data-pick="' + esc(r.deviceId) + '" title="' + esc(r.id) + '">' + esc(r.deviceName) + '</button>'; }).join('') + '</div></div>' });
  }

  function render() {
    DEV = null; R = null; MODE = ''; picker = null;
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: 'Disposal Perangkat',
        meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-disposal' }) +
      chips() +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset Perangkat', req: true, body: '<div data-finder></div>' }) +
      '<div data-body></div></form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, includeDisposed: true, goLabel: 'Pilih', scanTitle: 'Scan Barcode Perangkat',
      onPick: function (d) { pick(d); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form);
    form.addEventListener('click', function (e) { var b = e.target.closest('[data-pick]'); if (b) finder.set(AS.get(b.getAttribute('data-pick'))); });
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
    // Unsent answers come back after leaving the page (asked first), with their device. A link that opens another
    // device (?barcode=) is not interrupted.
    kept = GF.draft(form, { key: 'disposal', title: 'Disposal Perangkat',
      extra: function () { return DEV ? DEV.id : ''; },
      allow: function (id) { var b = AU.qs('barcode'); return !!AS.get(id) && (!b || AS.normId(b) === id); },
      label: function (dr) { var x = AS.get(dr.extra); return x ? 'Perangkat: ' + x.name + ' (' + x.id + ')' : ''; },
      restore: function (id) { var x = AS.get(id); if (x && (!DEV || DEV.id !== id)) finder.set(x); } });
    setTimeout(function () { if (!DEV) finder.input.focus(); }, 60);
  }

  function summary(r) {
    return GF.kv([['No. Disposal', AU.code(r.id), true], ['Status Disposal', AU.dspPill(r.status), true], ['Alasan', r.reason], ['Penjelasan', r.reasonDetail], ['Kondisi', r.condition],
      ['Dicatat', AU.fmtDateTime(r.requestedAt) + ' · ' + r.requestedBy],
      ['Disetujui', r.approvedAt && r.status !== 'rejected' ? AU.fmtDate(r.approvedAt) : ''],
      ['Dibatalkan', r.status === 'rejected' ? AU.fmtDate(r.approvedAt) : ''], ['Catatan', r.decisionNote],
      ['Tanggal Dihapuskan', r.disposedAt ? AU.fmtDate(r.disposedAt) : ''], ['Metode', r.method], ['No. Berita Acara', r.reference], ['Lampiran', AU.docsHtml(r.docs), true]]);
  }
  var backBtn = '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a><span class="gf-actions__spacer"></span><button type="button" class="gf-btn gf-btn--text" data-reset>Pilih perangkat lain</button></div>';

  function pick(d) {
    DEV = d; picker = null;
    history.replaceState(null, '', 'disposal?barcode=' + encodeURIComponent(d.id));
    var open = AS.openDisposal(d.id), svc = AS.openService(d.id), today = AU.today();
    R = open || (d.status === 'disposed' ? AS.getDisposal(d.disposalId) : null);
    MODE = d.status === 'disposed' ? 'archived' : svc ? 'blocked' : 'form';
    var html = GF.section(1, 'Data Perangkat') +
      GF.q({ name: 'devname', title: 'Nama Perangkat', badge: 'Otomatis', body: GF.ro(esc(d.name), AU.code(d.id) + (d.brand ? ' · ' + esc(d.brand + ' ' + d.model) : '')) }) +
      GF.q({ name: 'devcat', title: 'Kategori Perangkat', badge: 'Otomatis', body: GF.ro(esc(AU.catName(d))) }) +
      GF.q({ name: 'devdept', title: 'Departemen', badge: 'Otomatis', body: GF.ro(esc((d.location && d.location.dept) || d.department || '—')) }) +
      GF.q({ name: 'dstatus', title: 'Status Perangkat', badge: 'Otomatis', body: AU.statusPill(d.status) });

    if (MODE === 'archived') {
      html += GF.section(2, 'Arsip Disposal') +
        '<section class="gf-card">' + summary(R || {}) + '<div class="as-done__links" style="margin-top:14px"><a class="gf-link" href="' + AU.trackUrl(d.id) + '">' + ic('history') + 'Lihat riwayat lengkap perangkat</a></div></section>' + backBtn;
    } else if (MODE === 'blocked') {
      html += '<div class="gf-card as-notice as-notice--warn">' + ic('wrench') + '<div><b>Perangkat masih dalam servis</b> (' + esc(svc.id) + ' · ' + esc(AS.SVC_STATUS[svc.status].label) + '). Selesaikan laporan servis terlebih dahulu sebelum disposal. ' +
        '<a class="gf-link" href="progres-servis?id=' + encodeURIComponent(svc.id) + '">Buka Progres Servis</a></div></div>' + backBtn;
    } else {
      var lastSvc = AS.servicesOf(d.id)[0], past = AS.disposalsOf(d.id).filter(function (r) { return r !== open; }), v = open || {};
      html += GF.section(2, 'Data Disposal') +
        (open ? '<div class="gf-card as-notice as-notice--warn">' + ic('archive') + '<div><b>Ada pengajuan lama (' + esc(open.id) + ') yang belum selesai.</b> Isi formulir ini untuk langsung menghapuskan perangkat, atau <button type="button" class="gf-link as-linkbtn" data-cancel>batalkan pengajuan</button> bila perangkat tidak jadi dihapuskan.</div></div>' : '') +
        (lastSvc ? '<div class="gf-card as-notice as-notice--muted">' + ic('wrench') + '<div>Servis terakhir: <b>' + esc(lastSvc.id + ' · ' + lastSvc.type) + '</b> (' + esc(AS.SVC_STATUS[lastSvc.status].label) + ')' + (lastSvc.notes ? ' — ' + esc(lastSvc.notes) : '') + '</div></div>' : '') +
        GF.q({ name: 'reason', title: 'Alasan Disposal', req: true, body: GF.radio('reason', AS.DSP_REASONS.filter(function (x) { return x !== 'Lainnya'; }), v.reason || '', { other: true }) }) +
        GF.q({ name: 'reasonDetail', title: 'Penjelasan', req: true, body: GF.area('reasonDetail', { rows: 3, value: v.reasonDetail }) }) +
        GF.q({ name: 'condition', title: 'Kondisi Perangkat', req: true, body: GF.radio('condition', AS.DSP_CONDITIONS, v.condition || '') }) +
        GF.q({ name: 'date', title: 'Tanggal Disposal', req: true, body: GF.text('date', { type: 'date', value: today, max: today }) }) +
        GF.q({ name: 'method', title: 'Metode Disposal', req: true, body: GF.radio('method', AS.DSP_METHODS, '') }) +
        GF.q({ name: 'reference', title: 'No. Berita Acara', badge: 'Opsional', body: GF.text('reference', { mono: true }) }) +
        GF.q({ name: 'docs', title: 'Dokumen / Foto Pendukung', badge: 'Opsional', help: 'Maks. 6 file.', body: AU.fileField('docs', { max: 6 }) }) +
        (past.length ? '<section class="gf-card"><h3 class="gf-q__title">Catatan disposal sebelumnya</h3><ul class="as-list">' + past.map(function (r) { return '<li><div class="as-list__item"><span class="as-list__text"><b>' + esc(r.id + ' · ' + r.reason) + '</b><small>' + esc(r.decisionNote || r.reasonDetail) + '</small></span><span class="as-list__meta">' + esc(AU.fmtDate(r.requestedAt)) + '<br>' + AU.dspPill(r.status) + '</span></div></li>'; }).join('') + '</ul></section>' : '') +
        '<div class="gf-card as-notice as-notice--info">' + ic('info') + '<div>Setelah dikonfirmasi, status perangkat menjadi <b>Disposed</b> dan perangkat keluar dari daftar aset aktif. Data dan seluruh riwayatnya <b>tetap tersimpan</b> sebagai arsip.</div></div>' +
        GF.actions('Hapuskan Perangkat', { icon: 'archive', danger: true });
    }
    var box = form.querySelector('[data-body]');
    box.innerHTML = html; AU.render(box);
    if (MODE === 'form') picker = AU.filePicker(form, 'docs', { max: 6 });
    var cancel = box.querySelector('[data-cancel]');
    if (cancel) cancel.addEventListener('click', function () { cancelOpen(d, open); });
    var rs = box.querySelector('[data-reset]');
    if (rs) rs.addEventListener('click', function () {
      if (MODE !== 'form') { history.replaceState(null, '', 'disposal'); render(); return; }
      AU.confirm({ title: 'Kosongkan formulir?', text: 'Jawaban yang sudah diisi akan dihapus.', ok: 'Kosongkan' }).then(function (ok) { if (ok) { kept.clear(); history.replaceState(null, '', 'disposal'); render(); } });
    });
    setTimeout(function () { form.querySelector('.gf-section').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60);
  }

  function finish(title, text, r) {
    history.replaceState(null, '', 'disposal');
    GF.done(app, { title: title, text: text, html: summary(r),
      links: [{ id: 'again', label: 'Isi formulir lain', icon: 'rotate-ccw', fn: render }, { href: AU.trackUrl(r.deviceId), label: 'Lihat perangkat', icon: 'search' }, { href: 'rekap-disposal', label: 'Lihat rekap disposal', icon: 'chart-column' }] });
  }

  function cancelOpen(d, r) {
    AU.confirm({ title: 'Batalkan pengajuan ' + esc(r.id) + '?', ok: 'Ya, batalkan', text: esc(d.name) + ' tidak jadi dihapuskan dan kembali ke status sebelumnya.' }).then(function (yes) {
      if (!yes) return;
      try { AS.rejectDisposal(r.id, { approvedBy: AS.me(), note: 'Pengajuan lama dibatalkan.' }); } catch (err) { return AU.fail(err); }
      AS.save().then(function () { finish('Pengajuan dibatalkan', r.id + ' dibatalkan. ' + d.name + ' kembali berstatus ' + AS.STATUS[AS.get(d.id).status].label + '.', r); }, AU.fail);
    });
  }

  function submit() {
    GF.clear(form);
    if (!DEV) { GF.err(form, 'barcode', 'Scan atau pilih perangkat terlebih dahulu.'); GF.firstErr(form); return; }
    if (MODE !== 'form') return;
    var d = DEV, btn = form.querySelector('button[type="submit"]');
    var ok = GF.need(form, ['reason', 'reasonDetail', 'condition', 'date', 'method']), date = GF.val(form, 'date');
    if (date > AU.today()) { GF.err(form, 'date', 'Tanggal tidak boleh di masa depan.'); ok = false; }
    if (!ok) return GF.firstErr(form);
    var input = { deviceId: d.id, reason: GF.val(form, 'reason'), reasonDetail: GF.val(form, 'reasonDetail'), condition: GF.val(form, 'condition'), date: date, method: GF.val(form, 'method'), reference: GF.val(form, 'reference') };
    AU.confirm({ title: 'Yakin hapuskan ' + esc(d.name) + '?', danger: true, ok: 'Ya, hapuskan perangkat',
      text: 'Perangkat <code class="as-code">' + esc(d.id) + '</code> akan langsung berstatus <b>Disposed</b> dan dikeluarkan dari daftar aset aktif.<br><br>Alasan: <b>' + esc(input.reason) + '</b><br>Metode: <b>' + esc(input.method) + '</b><br><br>Tindakan ini <b>tidak dapat dibatalkan</b>. Data dan riwayatnya tetap tersimpan di arsip.' })
      .then(function (yes) {
        if (!yes) return;
        btn.disabled = true;
        (picker ? picker.upload('disposal') : Promise.resolve([])).then(function (docs) {
          var r;
          input.docs = docs;
          try { r = AS.dispose(input); } catch (err) { btn.disabled = false; return AU.fail(err); }
          AS.save().then(function () { finish('Perangkat telah dihapuskan', d.name + ' kini berstatus Disposed dan diarsipkan. Riwayat lokasi, servis, dan disposal tetap dapat dibuka.', r); }, function (err) { btn.disabled = false; AU.fail(err); });
        }, function (err) { btn.disabled = false; AU.fail(err); });
      });
  }
})();
