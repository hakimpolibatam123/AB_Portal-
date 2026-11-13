/* IT Asset Management — Servis & Pemeliharaan (Google Forms style): only the report of a device to be serviced.
 * Scan or pick a device, then fill in the report. Following a service up (status, progress, history) is done on
 * Progres Servis (aset-progress.js): a device that already has an open service links there instead of a form.
 * ?barcode=<ID> preselects a device; an old link with ?id=<SRV-…> opens that service on Progres Servis. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var DEV = null, OPEN = null, finder = null, form = null, picker = null, kept = null;
  // A new report starts as Dalam Proses (Dilaporkan is not offered here); the other statuses can be chosen.
  var STATUS_OPTS = Object.keys(AS.SVC_STATUS).filter(function (k) { return k !== 'reported'; }).map(function (k) { return { value: k, label: AS.SVC_STATUS[k].label }; });
  var AFTER_OPTS = [{ value: 'aktif', label: 'Aktif' }, { value: 'cadangan', label: 'Cadangan' }, { value: 'rusak', label: 'Rusak' }];

  var oldId = AU.qs('id');
  if (oldId) { location.replace('progres-servis?id=' + encodeURIComponent(oldId)); return; }

  AS.ready().then(function () {
    AU.consumeFlash();
    render();
    var b = AU.qs('barcode') || AU.qs('new');
    if (b) { var d = AS.get(b); if (d) finder.set(d); else finder.find(b); }
  }).catch(AU.fail);

  function render() {
    DEV = null; OPEN = null;
    var open = AS.state().services.filter(function (t) { return !AS.svcClosed(t.status); }).length;
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: 'Servis & Pemeliharaan',
        meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-servis' }) +
      // Progress of the services is on its own page.
      '<a class="gf-card as-svcgo" href="progres-servis">' + ic('activity') + '<span><b>Progres Servis</b><small>' +
        (open ? open + ' servis sedang berjalan. Cek progres, ubah status, dan lihat riwayatnya di sini.' : 'Cek progres, ubah status, dan lihat riwayat servis.') + '</small></span>' + ic('chevron-right') + '</a>' +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset Perangkat', req: true, body: '<div data-finder></div>' }) +
      '<div data-body></div></form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, goLabel: 'Pilih', scanTitle: 'Scan Barcode Perangkat yang Diservis',
      filter: function (d) { return d.status === 'disposal' ? d.name + ' sedang dalam proses disposal — tidak dapat diservis.' : null; },
      onPick: function (d) { pick(d); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form);
    form.addEventListener('change', function (e) { if (e.target.name === 'status' || e.target.name === 'afterStatus') toggleDone(e.target.name === 'status'); });
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
    // Unsent answers come back after leaving the page (asked first), with the device they were for. A link that
    // opens another device (?barcode=) is not interrupted.
    kept = GF.draft(form, { key: 'servis', title: 'Servis & Pemeliharaan',
      extra: function () { return DEV ? DEV.id : ''; },
      allow: function (id) { var b = AU.qs('barcode'), x = AS.get(id); return !!x && !AS.openService(id) && (!b || AS.normId(b) === id); },
      label: function (dr) { var x = AS.get(dr.extra); return x ? 'Perangkat: ' + x.name + ' (' + x.id + ')' : ''; },
      restore: function (id) { var x = AS.get(id); if (x && (!DEV || DEV.id !== id)) finder.set(x); } });
    setTimeout(function () { if (!DEV) finder.input.focus(); }, 60);
  }

  /*
   * Selesai or Ditolak closes the service: its date and the device's status afterwards are asked (a rejected
   * service — e.g. warranty claim refused — suggests Rusak). Ending as Rusak (or Ditolak) needs a berita acara.
   */
  function needsReport() {
    var st = GF.val(form, 'status');
    return AS.svcClosed(st) && (st === 'rejected' || AS.endStatus(st, GF.val(form, 'afterStatus')) === 'rusak');
  }
  function toggleDone(changed) {
    var st = GF.val(form, 'status'), on = AS.svcClosed(st), rejected = st === 'rejected';
    GF.show(form, 'completedAt', on); GF.show(form, 'afterStatus', on);
    var t = form.querySelector('[data-q="completedAt"] .gf-q__title'); if (t) t.firstChild.nodeValue = rejected ? 'Tanggal Ditolak' : 'Tanggal Selesai';
    if (changed && on) { var r = form.querySelector('[name="afterStatus"][value="' + (rejected ? 'rusak' : 'aktif') + '"]'); if (r) r.checked = true; }
    var badge = form.querySelector('[data-q="docs"] .gf-q__badge'); if (badge) badge.textContent = needsReport() ? 'Wajib' : 'Opsional';
  }
  /* Tenggat Vendor: how long the vendor / technician has for the job. An email reminder goes out one day before. */
  function dueQ(min) {
    return GF.q({ name: 'dueDate', title: 'Tenggat Vendor', badge: 'Opsional',
      help: 'Batas waktu pengerjaan yang diberikan ke vendor / teknisi. Email pengingat dikirim H-1 sebelum tenggat.',
      body: GF.text('dueDate', { type: 'date', value: '', min: min }) });
  }
  function docsQ() {
    return GF.q({ name: 'docs', title: 'Berita Acara / Dokumen Servis', badge: 'Opsional',
      help: 'Foto atau PDF berita acara, nota servis. Wajib bila perangkat dinyatakan Rusak atau servis Ditolak.',
      body: AU.fileField('svcdocs', { max: 10 }) });
  }

  function historyHtml(d) {
    var list = AS.servicesOf(d.id);
    return '<section class="gf-card"><div class="gf-q__head"><h3 class="gf-q__title">Riwayat Servis Perangkat</h3><span class="gf-q__badge">' + list.length + ' laporan</span></div>' +
      (list.length ? '<ol class="as-tl" style="margin-top:6px">' + list.map(function (t) {
        return '<li><span class="as-tl__dot' + (AS.svcClosed(t.status) ? ' as-tl__dot--muted' : '') + '"></span><div class="as-tl__head"><span><b>' + esc(t.type) + '</b> · <a class="gf-link" href="progres-servis?id=' + encodeURIComponent(t.id) + '">' + esc(t.id) + '</a></span>' + AU.svcPill(t.status) + '</div>' +
          '<div class="as-tl__body">' + esc(t.problem) + (t.notes ? '<br><i>Catatan:</i> ' + esc(t.notes) : '') + '</div>' +
          (t.docs && t.docs.length ? AU.docsHtml(t.docs) : '') +
          '<div class="as-tl__by">' + esc(t.technician) + ' · dilaporkan ' + esc(AU.fmtDate(t.reportDate)) + (t.completedAt ? (t.status === 'rejected' ? ' · ditolak ' : ' · selesai ') + esc(AU.fmtDate(t.completedAt)) : '') + '</div></li>';
      }).join('') + '</ol>' : '<p class="gf-q__help">Belum ada riwayat servis untuk perangkat ini.</p>') + '</section>';
  }

  function pick(d) {
    DEV = d; OPEN = AS.openService(d.id);
    history.replaceState(null, '', 'servis?barcode=' + encodeURIComponent(d.id));
    var today = AU.today();
    var html = GF.section(1, 'Data Perangkat', 'Terisi otomatis dari barcode.') +
      GF.q({ name: 'devname', title: 'Nama Perangkat', badge: 'Otomatis', body: GF.ro(esc(d.name), AU.code(d.id) + ' · ' + esc(AU.catName(d)) + (d.brand ? ' · ' + esc(d.brand + ' ' + d.model) : '')) }) +
      GF.q({ name: 'devloc', title: 'Di Departemen', badge: 'Otomatis', body: GF.ro(esc(AS.deptText(AS.whereIs(d)))) });
    if (OPEN) {
      // Already in service: no second report; its status is changed on Progres Servis.
      html += '<div class="gf-card as-notice as-notice--warn">' + ic('activity') + '<div><b>Perangkat ini masih dalam servis</b> (' + esc(OPEN.id) + ' · ' + esc(AS.SVC_STATUS[OPEN.status].label) + '). ' +
        'Laporan baru bisa dibuat setelah servis ini selesai. Untuk mengubah status dan melihat progresnya, buka Progres Servis.' +
        '<div style="margin-top:10px"><a class="gf-btn gf-btn--primary" href="progres-servis?id=' + encodeURIComponent(OPEN.id) + '">' + ic('activity') + 'Buka Progres Servis</a></div></div></div>' +
        historyHtml(d) +
        '<div class="gf-actions"><a class="gf-btn gf-btn--ghost" href="../aset">' + ic('arrow-left') + 'Kembali ke Menu Aset IT</a><span class="gf-actions__spacer"></span><button type="button" class="gf-btn gf-btn--text" data-reset>Pilih perangkat lain</button></div>';
    } else {
      html += GF.section(2, 'Laporan Servis', 'Detail kerusakan atau pekerjaan pemeliharaan.', 'Laporan baru') +
        GF.q({ name: 'type', title: 'Jenis Servis', req: true, body: GF.radio('type', AS.SVC_TYPES, '') }) +
        GF.q({ name: 'problem', title: 'Deskripsi Masalah', req: true, body: GF.area('problem', { rows: 3 }) }) +
        GF.q({ name: 'reportDate', title: 'Tanggal Laporan', req: true, body: GF.text('reportDate', { type: 'date', value: today, max: today }) }) +
        GF.q({ name: 'technician', title: 'Teknisi / Vendor', req: true, body: GF.text('technician', { list: 'dl-tech' }) + GF.datalist('dl-tech', AS.TECHNICIANS.concat(AS.VENDORS)) }) +
        dueQ(today) +
        GF.q({ name: 'status', title: 'Status Servis', req: true, body: GF.radio('status', STATUS_OPTS, 'progress') }) +
        GF.q({ name: 'notes', title: 'Catatan Servis', badge: 'Opsional', body: GF.area('notes', {}) }) +
        docsQ() +
        GF.q({ name: 'completedAt', title: 'Tanggal Selesai', req: true, hidden: true, body: GF.text('completedAt', { type: 'date', value: today, max: today }) }) +
        GF.q({ name: 'afterStatus', title: 'Status Perangkat Setelah Servis', req: true, hidden: true, body: GF.radio('afterStatus', AFTER_OPTS, 'aktif') }) +
        GF.section(3, 'Riwayat', '') + historyHtml(d) +
        GF.actions('Kirim Laporan');
    }
    var box = form.querySelector('[data-body]');
    box.innerHTML = html; AU.render(box);
    picker = OPEN ? null : AU.filePicker(box, 'svcdocs', { max: 10, onChange: function () { GF.err(form, 'docs', ''); } });
    box.querySelector('[data-reset]').addEventListener('click', function () {
      if (OPEN) { history.replaceState(null, '', 'servis'); return render(); }
      AU.confirm({ title: 'Kosongkan formulir?', text: 'Jawaban yang sudah diisi akan dihapus.', ok: 'Kosongkan' }).then(function (ok) { if (ok) { kept.clear(); history.replaceState(null, '', 'servis'); render(); } });
    });
    if (!OPEN) toggleDone();
    setTimeout(function () { form.querySelector('.gf-section').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60);
  }

  function submit() {
    GF.clear(form);
    if (!DEV) { GF.err(form, 'barcode', 'Scan atau pilih perangkat terlebih dahulu.'); GF.firstErr(form); return; }
    if (OPEN) { location.href = 'progres-servis?id=' + encodeURIComponent(OPEN.id); return; }
    var st = GF.val(form, 'status'), closed = AS.svcClosed(st), rejected = st === 'rejected';
    var ok = GF.need(form, ['type', 'problem', 'reportDate', 'technician', 'status', 'completedAt', 'afterStatus']);
    var notes = GF.val(form, 'notes');
    if (needsReport() && !picker.count()) {
      GF.err(form, 'docs', rejected ? 'Unggah berita acara penolakan servis.' : 'Perangkat dinyatakan rusak: unggah berita acara kerusakan (foto atau PDF).'); ok = false;
    }
    var rd = GF.val(form, 'reportDate');
    if (rd > AU.today()) { GF.err(form, 'reportDate', 'Tanggal laporan tidak boleh di masa depan.'); ok = false; }
    var due = GF.val(form, 'dueDate');
    if (due && due < rd) { GF.err(form, 'dueDate', 'Tenggat vendor tidak boleh sebelum tanggal laporan (' + AU.fmtDate(rd) + ').'); ok = false; }
    if (closed && GF.val(form, 'completedAt') && GF.val(form, 'completedAt') < rd) { GF.err(form, 'completedAt', 'Tanggal ' + (rejected ? 'ditolak' : 'selesai') + ' sebelum tanggal laporan (' + AU.fmtDate(rd) + ').'); ok = false; }
    if (!ok) { GF.firstErr(form); return; }
    var d = DEV, btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    // Documents go to the portal first; the service then lists them (a refused save keeps them for the retry).
    picker.upload('berita_acara').then(function (docs) {
      var t;
      try {
        t = AS.addService({ deviceId: d.id, type: GF.val(form, 'type'), problem: GF.val(form, 'problem'), reportDate: rd, dueDate: due, technician: GF.val(form, 'technician'),
          status: st, notes: notes, completedAt: closed ? GF.val(form, 'completedAt') : '', afterStatus: GF.val(form, 'afterStatus'), docs: docs });
      } catch (err) { btn.disabled = false; return AU.fail(err); }
      return AS.save().then(function () { done(t, d); });
    }).catch(function (err) { btn.disabled = false; AU.fail(err); });
  }

  function done(t, d) {
    history.replaceState(null, '', 'servis');
    var dev = AS.get(d.id);
    t = AS.state().services.filter(function (x) { return x.id === t.id; })[0] || t; // the saved ticket, with its documents
    var end = t.status === 'done' ? 'Servis selesai; status perangkat kini ' + AS.STATUS[dev.status].label + '.'
      : t.status === 'rejected' ? 'Servis ditolak; berita acara tersimpan. Status perangkat kini ' + AS.STATUS[dev.status].label + '.'
      : 'Status servis: ' + AS.SVC_STATUS[t.status].label + '. Perangkat tercatat Dalam Servis. Progresnya diikuti di Progres Servis.';
    GF.done(app, {
      title: 'Laporan servis terkirim',
      text: t.id + ' — ' + d.name + '. ' + end,
      html: GF.kv([['No. Tiket', AU.code(t.id), true], ['Perangkat', d.name + ' (' + d.id + ')'], ['Departemen', AS.deptText(AS.svcLoc(t))], ['Jenis Servis', t.type], ['Masalah', t.problem],
        ['Teknisi / Vendor', t.technician], ['Tenggat Vendor', t.dueDate ? AU.fmtDate(t.dueDate) : ''], ['Status', AU.svcPill(t.status), true], [t.status === 'rejected' ? 'Tanggal Ditolak' : 'Tanggal Selesai', t.completedAt ? AU.fmtDate(t.completedAt) : ''],
        ['Dokumen', AU.docsHtml(t.docs), true]]) +
        (AS.svcClosed(t.status) && dev.status === 'rusak' ? '<div class="as-notice as-notice--warn" style="margin-top:16px">' + ic('triangle-alert') + '<div>Perangkat dinyatakan rusak. <a class="gf-link" href="disposal?barcode=' + encodeURIComponent(d.id) + '">Disposal perangkat</a></div></div>' : ''),
      links: [{ href: 'progres-servis?id=' + encodeURIComponent(t.id), label: 'Lihat progres servis', icon: 'activity' }, { id: 'again', label: 'Kirim laporan lain', icon: 'rotate-ccw', fn: render },
        { href: AU.trackUrl(d.id), label: 'Lacak perangkat', icon: 'map-pin' }]
    });
  }
})();
