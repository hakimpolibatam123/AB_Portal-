/* IT Asset Management — Servis & Pemeliharaan (Google Forms style).
 * Scan a device: no active ticket → new report; active ticket → status update. ?barcode=<ID> preselects. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var DEV = null, T = null, finder = null, form = null, picker = null;
  var STATUS_OPTS = Object.keys(AS.SVC_STATUS).map(function (k) { return { value: k, label: AS.SVC_STATUS[k].label }; });
  var AFTER_OPTS = [{ value: 'aktif', label: 'Aktif' }, { value: 'cadangan', label: 'Cadangan' }, { value: 'rusak', label: 'Rusak' }];

  AS.ready().then(function () {
    AU.consumeFlash();
    render();
    var b = AU.qs('barcode') || AU.qs('new');
    var tid = AU.qs('id'); if (!b && tid) { var t = AS.state().services.filter(function (x) { return x.id === tid; })[0]; if (t) b = t.deviceId; }
    if (b) { var d = AS.get(b); if (d) finder.set(d); else finder.find(b); }
  }).catch(AU.fail);

  /*
   * Open services, kept the same small size however many devices are in service: three status tiles with counts
   * (click = filter), then only the SHOW longest-waiting tickets as one-line rows. "Lihat semua" opens the rest in a
   * short scrolling list. Services open 7+ days are marked red.
   */
  var OPEN_KEYS = ['reported', 'progress', 'parts'], SHOW = 3, LATE = 7, openFilter = 'all', openAll = false;
  var OPEN_ICON = { reported: 'inbox', progress: 'wrench', parts: 'clock' };
  function daysOpen(date) {
    var d = date ? new Date(String(date).slice(0, 10) + 'T00:00:00') : null; if (!d || isNaN(d)) return null;
    return Math.max(0, Math.round((new Date(AU.today() + 'T00:00:00') - d) / 86400000));
  }
  function ageText(n) { return n === null ? '' : n === 0 ? 'Hari ini' : n === 1 ? 'Kemarin' : n + ' hari'; }
  function activeChips() {
    var open = AS.state().services.filter(function (t) { return !AS.svcClosed(t.status); });
    if (!open.length) return '';
    // Late or near vendor deadlines (≤ 3 days) first, nearest first; then the longest-waiting.
    var urgent = function (t) { var n = AS.dueIn(t); return n !== null && n <= 3 ? n : Infinity; };
    open.sort(function (a, b) {
      return (urgent(a) - urgent(b)) || String(a.reportDate || '').localeCompare(String(b.reportDate || '')) || String(a.id).localeCompare(String(b.id));
    });
    openFilter = 'all'; openAll = false;
    var isLate = function (t) { var n = daysOpen(t.reportDate); return n !== null && n >= LATE; };
    var tile = function (k) {
      var def = AS.SVC_STATUS[k], l = open.filter(function (t) { return t.status === k; }), late = l.filter(isLate).length;
      return '<button type="button" class="as-svct as-svct--' + def.tone + '" data-svcf="' + k + '" aria-pressed="false"' + (l.length ? '' : ' disabled') + '>' +
        '<span class="as-svct__ic">' + ic(OPEN_ICON[k]) + '</span>' +
        '<span class="as-svct__txt"><b>' + l.length + '</b><span>' + esc(def.label) + '</span></span>' +
        (late ? '<span class="as-svct__late" title="' + late + ' servis berjalan ' + LATE + ' hari atau lebih">' + late + ' lama</span>' : '') + '</button>';
    };
    var rows = open.map(function (t) {
      var def = AS.SVC_STATUS[t.status], n = daysOpen(t.reportDate);
      return '<button type="button" class="as-svcrow as-svcrow--' + def.tone + '" data-pick="' + esc(t.deviceId) + '" data-st="' + esc(t.status) + '"' +
        ' title="' + esc(t.id + ' · ' + def.label + (t.problem ? ' — ' + t.problem : '') + (t.technician ? ' · ' + t.technician : '')) + '">' +
        '<i class="as-svcrow__dot" aria-label="' + esc(def.label) + '"></i>' +
        '<span class="as-svcrow__name">' + esc(t.deviceName) + '</span><span class="as-svcrow__id">' + esc(t.deviceId) + '</span>' +
        (urgent(t) !== Infinity ? AU.dueBadge(t) : '<span class="as-svcrow__age' + (n !== null && n >= LATE ? ' is-late' : '') + '">' + esc(ageText(n)) + '</span>') +
        ic('chevron-right') + '</button>';
    }).join('');
    return GF.q({ name: 'open', title: 'Servis yang sedang berjalan', badge: open.length + ' aktif',
      body: '<div class="as-svct-row" role="toolbar" aria-label="Saring status servis">' + OPEN_KEYS.map(tile).join('') + '</div>' +
        '<div class="as-svclist__head"><span data-svch></span><span class="as-svclist__hint">Klik untuk membuka</span></div>' +
        '<div class="as-svclist">' + rows + '</div>' +
        '<button type="button" class="as-svclist__more" data-svcmore hidden></button>' });
  }
  /* Show the chosen status (or all), near vendor deadlines then longest-waiting first, SHOW rows unless "Lihat semua" is on. */
  function filterOpen(k) {
    if (k != null) { openFilter = openFilter === k ? 'all' : k; openAll = false; }
    form.querySelectorAll('[data-svcf]').forEach(function (b) { var on = b.getAttribute('data-svcf') === openFilter; b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on); });
    var match = 0;
    form.querySelectorAll('.as-svcrow').forEach(function (r) {
      var ok = openFilter === 'all' || r.getAttribute('data-st') === openFilter;
      r.hidden = !ok || (!openAll && match >= SHOW); if (ok) match++;
    });
    var head = form.querySelector('[data-svch]'), more = form.querySelector('[data-svcmore]'), list = form.querySelector('.as-svclist');
    if (!head) return;
    head.textContent = openFilter === 'all' ? 'Perlu didahulukan' : AS.SVC_STATUS[openFilter].label;
    list.classList.toggle('is-all', openAll);
    more.hidden = match <= SHOW;
    more.textContent = openAll ? 'Tampilkan lebih sedikit' : 'Lihat semua (' + match + ')';
  }

  function render() {
    DEV = null; T = null;
    app.innerHTML = '<form class="gf" id="gf-form" novalidate>' +
      GF.head({ title: 'Servis & Pemeliharaan',
        meta: [['Petugas', AS.me()], ['Tanggal', AU.fmtDate(AU.today())]], rekap: 'rekap-servis' }) +
      activeChips() +
      GF.q({ name: 'barcode', title: 'Barcode / ID Aset Perangkat', req: true, body: '<div data-finder></div>' }) +
      '<div data-body></div></form>';
    form = app.querySelector('#gf-form');
    finder = AU.lookup(app.querySelector('[data-finder]'), { gf: true, goLabel: 'Pilih', scanTitle: 'Scan Barcode Perangkat yang Diservis',
      filter: function (d) { return d.status === 'disposal' ? d.name + ' sedang dalam proses disposal — tidak dapat diservis.' : null; },
      onPick: function (d) { pick(d); }, onInput: function () { GF.err(form, 'barcode', ''); } });
    AU.render(app); GF.wire(form); filterOpen();
    form.addEventListener('click', function (e) { var f = e.target.closest('[data-svcf]'); if (f) { filterOpen(f.getAttribute('data-svcf')); return; }
      if (e.target.closest('[data-svcmore]')) { openAll = !openAll; filterOpen(); return; } var b = e.target.closest('[data-pick]'); if (b) finder.set(AS.get(b.getAttribute('data-pick'))); });
    form.addEventListener('change', function (e) { if (e.target.name === 'status' || e.target.name === 'afterStatus') toggleDone(e.target.name === 'status'); });
    form.addEventListener('submit', function (e) { e.preventDefault(); submit(); });
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
  function dueQ(value, min) {
    return GF.q({ name: 'dueDate', title: 'Tenggat Vendor', badge: 'Opsional',
      help: 'Batas waktu pengerjaan yang diberikan ke vendor / teknisi. Email pengingat dikirim H-1 sebelum tenggat.',
      body: GF.text('dueDate', { type: 'date', value: value || '', min: min }) + (value && T ? '<p class="gf-q__help">' + AU.dueBadge(T) + '</p>' : '') });
  }
  function docsQ(existing) {
    return GF.q({ name: 'docs', title: 'Berita Acara / Dokumen Servis', badge: 'Opsional',
      help: 'Foto atau PDF berita acara, nota servis. Wajib bila perangkat dinyatakan Rusak atau servis Ditolak.',
      body: (existing && existing.length ? '<p class="gf-q__help">Sudah terlampir:</p>' + AU.docsHtml(existing) + '<p class="gf-q__help">Tambah dokumen:</p>' : '') + AU.fileField('svcdocs', { max: 10 }) });
  }

  function historyHtml(d) {
    var list = AS.servicesOf(d.id);
    return '<section class="gf-card"><div class="gf-q__head"><h3 class="gf-q__title">Riwayat Servis Perangkat</h3><span class="gf-q__badge">' + list.length + ' laporan</span></div>' +
      (list.length ? '<ol class="as-tl" style="margin-top:6px">' + list.map(function (t) {
        return '<li><span class="as-tl__dot' + (AS.svcClosed(t.status) ? ' as-tl__dot--muted' : '') + '"></span><div class="as-tl__head"><span><b>' + esc(t.type) + '</b> · ' + esc(t.id) + '</span>' + AU.svcPill(t.status) + '</div>' +
          '<div class="as-tl__body">' + esc(t.problem) + (t.notes ? '<br><i>Catatan:</i> ' + esc(t.notes) : '') + '</div>' +
          (t.docs && t.docs.length ? AU.docsHtml(t.docs) : '') +
          '<div class="as-tl__by">' + esc(t.technician) + ' · dilaporkan ' + esc(AU.fmtDate(t.reportDate)) + (t.completedAt ? (t.status === 'rejected' ? ' · ditolak ' : ' · selesai ') + esc(AU.fmtDate(t.completedAt)) : '') + '</div></li>';
      }).join('') + '</ol>' : '<p class="gf-q__help">Belum ada riwayat servis untuk perangkat ini.</p>') + '</section>';
  }

  function pick(d) {
    DEV = d; T = AS.openService(d.id);
    history.replaceState(null, '', 'servis?barcode=' + encodeURIComponent(d.id));
    var c = AU.catOf(d), today = AU.today();
    var html = GF.section(1, 'Data Perangkat', 'Terisi otomatis dari barcode.') +
      GF.q({ name: 'devname', title: 'Nama Perangkat', badge: 'Otomatis', body: GF.ro(esc(d.name), AU.code(d.id) + ' · ' + esc(AU.catName(d)) + (d.brand ? ' · ' + esc(d.brand + ' ' + d.model) : '')) }) +
      GF.q({ name: 'devloc', title: 'Di Departemen', badge: 'Otomatis', body: GF.ro(esc(AS.deptText(AS.whereIs(d)))) });
    if (T) {
      var flow = ['reported', 'progress', 'parts', 'done'].map(function (k) { return k === T.status ? '<b class="is-now">' + esc(AS.SVC_STATUS[k].label) + '</b>' : '<span>' + esc(AS.SVC_STATUS[k].label) + '</span>'; }).join(ic('chevron-right'));
      html += GF.section(2, 'Perbarui Status Servis', 'Perangkat ini memiliki laporan servis yang belum selesai.', T.id) +
        '<section class="gf-card"><span class="as-mode">' + ic('refresh-cw') + 'Mode pembaruan</span>' +
        GF.kv([['No. Tiket', AU.code(T.id), true], ['Jenis Servis', T.type], ['Deskripsi Masalah', T.problem], ['Tanggal Laporan', AU.fmtDate(T.reportDate) + ' · ' + T.createdBy],
          T.dueDate ? ['Tenggat Vendor', esc(AU.fmtDate(T.dueDate)) + ' ' + AU.dueBadge(T), true] : null, ['Status saat ini', AU.svcPill(T.status), true]]) +
        '<div class="as-flow" style="margin-top:12px">' + flow + '</div>' +
        '<h3 class="as-sec-title" style="margin-top:16px">Progres</h3><ol class="as-tl">' + T.log.slice().reverse().map(function (l) {
          return '<li><span class="as-tl__dot"></span><div class="as-tl__head"><span>' + AU.svcPill(l.status) + '</span><span class="as-tl__time">' + esc(AU.fmtDateTime(l.at)) + '</span></div><div class="as-tl__body">' + esc(l.note) + '</div><div class="as-tl__by">' + esc(l.by) + '</div></li>';
        }).join('') + '</ol></section>' +
        GF.q({ name: 'status', title: 'Status Servis', req: true, body: GF.radio('status', STATUS_OPTS, T.status) }) +
        GF.q({ name: 'technician', title: 'Teknisi / Vendor', req: true, body: GF.text('technician', { value: T.technician, list: 'dl-tech' }) + GF.datalist('dl-tech', AS.TECHNICIANS.concat(AS.VENDORS)) }) +
        dueQ(T.dueDate, T.reportDate) +
        GF.q({ name: 'notes', title: 'Catatan Servis', req: true, body: GF.area('notes', {}) }) +
        docsQ(T.docs) +
        GF.q({ name: 'completedAt', title: 'Tanggal Selesai', req: true, hidden: true, body: GF.text('completedAt', { type: 'date', value: today, max: today }) }) +
        GF.q({ name: 'afterStatus', title: 'Status Perangkat Setelah Servis', req: true, hidden: true, body: GF.radio('afterStatus', AFTER_OPTS, 'aktif') }) +
        GF.section(3, 'Riwayat', '') + historyHtml(d) +
        GF.actions('Simpan Pembaruan', { icon: 'save', reset: 'Kosongkan formulir' });
    } else {
      html += GF.section(2, 'Laporan Servis', 'Detail kerusakan atau pekerjaan pemeliharaan.', 'Laporan baru') +
        GF.q({ name: 'type', title: 'Jenis Servis', req: true, body: GF.radio('type', AS.SVC_TYPES, '') }) +
        GF.q({ name: 'problem', title: 'Deskripsi Masalah', req: true, body: GF.area('problem', { rows: 3 }) }) +
        GF.q({ name: 'reportDate', title: 'Tanggal Laporan', req: true, body: GF.text('reportDate', { type: 'date', value: today, max: today }) }) +
        GF.q({ name: 'technician', title: 'Teknisi / Vendor', req: true, body: GF.text('technician', { list: 'dl-tech' }) + GF.datalist('dl-tech', AS.TECHNICIANS.concat(AS.VENDORS)) }) +
        dueQ('', today) +
        GF.q({ name: 'status', title: 'Status Servis', req: true, body: GF.radio('status', STATUS_OPTS, 'reported') }) +
        GF.q({ name: 'notes', title: 'Catatan Servis', badge: 'Opsional', body: GF.area('notes', {}) }) +
        docsQ([]) +
        GF.q({ name: 'completedAt', title: 'Tanggal Selesai', req: true, hidden: true, body: GF.text('completedAt', { type: 'date', value: today, max: today }) }) +
        GF.q({ name: 'afterStatus', title: 'Status Perangkat Setelah Servis', req: true, hidden: true, body: GF.radio('afterStatus', AFTER_OPTS, 'aktif') }) +
        GF.section(3, 'Riwayat', '') + historyHtml(d) +
        GF.actions('Kirim Laporan');
    }
    var box = form.querySelector('[data-body]');
    box.innerHTML = html; AU.render(box);
    picker = AU.filePicker(box, 'svcdocs', { max: 10, onChange: function () { GF.err(form, 'docs', ''); } });
    box.querySelector('[data-reset]').addEventListener('click', function () {
      AU.confirm({ title: 'Kosongkan formulir?', text: 'Jawaban yang sudah diisi akan dihapus.', ok: 'Kosongkan' }).then(function (ok) { if (ok) { history.replaceState(null, '', 'servis'); render(); } });
    });
    toggleDone();
    setTimeout(function () { form.querySelector('.gf-section').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 60);
  }

  function submit() {
    GF.clear(form);
    if (!DEV) { GF.err(form, 'barcode', 'Scan atau pilih perangkat terlebih dahulu.'); GF.firstErr(form); return; }
    var st = GF.val(form, 'status'), closed = AS.svcClosed(st), rejected = st === 'rejected';
    var ok = GF.need(form, T ? ['status', 'technician', 'completedAt', 'afterStatus'] : ['type', 'problem', 'reportDate', 'technician', 'status', 'completedAt', 'afterStatus']);
    var notes = GF.val(form, 'notes');
    if (T && !notes) { GF.err(form, 'notes', st === T.status ? 'Tambahkan catatan progres.' : 'Jelaskan pembaruan status ini.'); ok = false; }
    if (rejected && !notes) { GF.err(form, 'notes', 'Tuliskan alasan penolakan servis.'); ok = false; }
    if (needsReport() && !picker.count() && !(T && T.docs && T.docs.length)) {
      GF.err(form, 'docs', rejected ? 'Unggah berita acara penolakan servis.' : 'Perangkat dinyatakan rusak: unggah berita acara kerusakan (foto atau PDF).'); ok = false;
    }
    var rd = T ? T.reportDate : GF.val(form, 'reportDate');
    if (!T && rd > AU.today()) { GF.err(form, 'reportDate', 'Tanggal laporan tidak boleh di masa depan.'); ok = false; }
    var due = GF.val(form, 'dueDate');
    if (due && due < rd) { GF.err(form, 'dueDate', 'Tenggat vendor tidak boleh sebelum tanggal laporan (' + AU.fmtDate(rd) + ').'); ok = false; }
    if (closed && GF.val(form, 'completedAt') && GF.val(form, 'completedAt') < rd) { GF.err(form, 'completedAt', 'Tanggal ' + (rejected ? 'ditolak' : 'selesai') + ' sebelum tanggal laporan (' + AU.fmtDate(rd) + ').'); ok = false; }
    if (!ok) { GF.firstErr(form); return; }
    var d = DEV, btn = form.querySelector('button[type="submit"]'), wasUpdate = !!T;
    btn.disabled = true;
    // Documents go to the portal first; the service then lists them (a refused save keeps them for the retry).
    picker.upload('berita_acara').then(function (docs) {
      var t;
      try {
        if (T) t = AS.updateService(T.id, { status: st, note: notes, technician: GF.val(form, 'technician'), notes: closed ? notes : undefined, dueDate: due,
          completedAt: GF.val(form, 'completedAt'), afterStatus: GF.val(form, 'afterStatus'), docs: docs });
        else t = AS.addService({ deviceId: d.id, type: GF.val(form, 'type'), problem: GF.val(form, 'problem'), reportDate: rd, dueDate: due, technician: GF.val(form, 'technician'),
          status: st, notes: notes, completedAt: closed ? GF.val(form, 'completedAt') : '', afterStatus: GF.val(form, 'afterStatus'), docs: docs });
      } catch (err) { btn.disabled = false; return AU.fail(err); }
      return AS.save().then(function () { done(t, d, wasUpdate); });
    }).catch(function (err) { btn.disabled = false; AU.fail(err); });
  }

  function done(t, d, wasUpdate) {
    history.replaceState(null, '', 'servis');
    var dev = AS.get(d.id);
    t = AS.state().services.filter(function (x) { return x.id === t.id; })[0] || t; // the saved ticket, with its documents
    var end = t.status === 'done' ? 'Servis selesai; status perangkat kini ' + AS.STATUS[dev.status].label + '.'
      : t.status === 'rejected' ? 'Servis ditolak; berita acara tersimpan. Status perangkat kini ' + AS.STATUS[dev.status].label + '.'
      : 'Status servis: ' + AS.SVC_STATUS[t.status].label + '. Perangkat tercatat Dalam Servis.';
    GF.done(app, {
      title: wasUpdate ? 'Status servis diperbarui' : 'Laporan servis terkirim',
      text: t.id + ' — ' + d.name + '. ' + end,
      html: GF.kv([['No. Tiket', AU.code(t.id), true], ['Perangkat', d.name + ' (' + d.id + ')'], ['Departemen', AS.deptText(AS.svcLoc(t))], ['Jenis Servis', t.type], ['Masalah', t.problem],
        ['Teknisi / Vendor', t.technician], ['Tenggat Vendor', t.dueDate ? AU.fmtDate(t.dueDate) : ''], ['Status', AU.svcPill(t.status), true], [t.status === 'rejected' ? 'Tanggal Ditolak' : 'Tanggal Selesai', t.completedAt ? AU.fmtDate(t.completedAt) : ''],
        ['Dokumen', AU.docsHtml(t.docs), true]]) +
        (AS.svcClosed(t.status) && dev.status === 'rusak' ? '<div class="as-notice as-notice--warn" style="margin-top:16px">' + ic('triangle-alert') + '<div>Perangkat dinyatakan rusak. <a class="gf-link" href="disposal?barcode=' + encodeURIComponent(d.id) + '">Disposal perangkat</a></div></div>' : ''),
      links: [{ id: 'again', label: 'Kirim laporan lain', icon: 'rotate-ccw', fn: render }, { href: AU.trackUrl(d.id), label: 'Lacak perangkat', icon: 'map-pin' }, { href: 'rekap-servis', label: 'Lihat rekap servis', icon: 'chart-column' }]
    });
  }
})();
