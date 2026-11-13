/*
 * IT Asset Management — Lihat Rekap: Rekap & Daftar Perangkat in three tabs (Daftar Perangkat, Ringkasan, Riwayat;
 * the page opens on Daftar Perangkat; #ringkasan and #servis / #disposal / #perpindahan open the others), or one history on its own page
 * (<body data-rekap-view>: servis / disposal / perpindahan).
 */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var F = { q: '', cat: '', dept: '', building: '', status: '', archive: false, recent: false, sort: 'updated', page: 1 };
  var PAGE_SIZE = 12;
  // Daftar Perangkat is the first tab and the one the page opens on (#ringkasan opens Ringkasan).
  var showAllLoc = false, TAB = 'perangkat';
  var VIEW = document.body.getAttribute('data-rekap-view') || '';
  var OPS_VIEWS = ['servis', 'disposal', 'perpindahan', 'serah-terima'];

  AS.ready().then(function () { draw(); if (!VIEW) { jump(); window.addEventListener('hashchange', jump); } }).catch(AU.fail);
  var OPS = OPS_VIEWS.indexOf(VIEW) !== -1 ? VIEW : 'servis';
  function jump() {
    var h = location.hash.slice(1);
    if (OPS_VIEWS.indexOf(h) !== -1) { OPS = h; drawOps(); showTab('riwayat', true); }
    else showTab(h === 'ringkasan' ? 'ringkasan' : 'perangkat', true);
  }
  /* Shows one tab; the address keeps it, so coming back from a device opens the same tab. */
  function showTab(t, keepUrl) {
    TAB = t;
    app.querySelectorAll('[data-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== t; });
    app.querySelectorAll('.as-tabs [data-tab]').forEach(function (b) { b.setAttribute('aria-selected', String(b.getAttribute('data-tab') === t)); });
    if (!keepUrl) history.replaceState(null, '', t === 'perangkat' ? location.pathname : '#' + (t === 'riwayat' ? OPS : t));
  }

  function within(iso, days) { return iso && (Date.now() - new Date(iso).getTime()) <= days * 86400000; }
  /* Where a device belongs: its placement, else the department from Registrasi. */
  function deptOf(d) { return (d.location && d.location.dept) || d.department || 'Tanpa departemen'; }

  function draw() {
    var S = AS.state();
    var active = AS.devices();
    var inService = active.filter(function (d) { return d.status === 'servis'; }).length;
    var waitParts = S.services.filter(function (t) { return t.status === 'parts'; }).length;
    var inDisposal = active.filter(function (d) { return d.status === 'disposal'; }).length;
    var recentAdded = active.filter(function (d) { return within(d.createdAt, 30); }).length;
    var recentMoved = S.moves.filter(function (m) { return within(m.at, 30); }).length;
    var disposed = S.disposals.filter(function (r) { return r.status === 'disposed'; }).length;
    var depts = {}; active.forEach(function (d) { depts[deptOf(d)] = 1; });
    var tab = function (key, icon, label, n) { return '<button type="button" role="tab" data-tab="' + key + '" aria-selected="false">' + ic(icon) + label + (n === null ? '' : ' <span class="as-n">' + n + '</span>') + '</button>'; };
    var ops = '<section class="rs-card" id="as-ops"><div class="rs-card__head"><div><h2>' + ic('history') + 'Riwayat Operasional</h2><p>Laporan servis, disposal, dan perpindahan perangkat.</p></div>' +
      '<div class="rs-actions"><button type="button" class="rs-btn rs-btn--ghost rs-btn--sm2" data-opsexport>' + ic('download') + 'Export CSV</button></div></div>' +
      '<div class="as-subtabs" role="tablist" aria-label="Riwayat operasional">' +
      '<button type="button" role="tab" data-ops="servis">' + ic('wrench') + 'Laporan Servis <span class="as-n">' + S.services.length + '</span></button>' +
      '<button type="button" role="tab" data-ops="disposal">' + ic('archive') + 'Disposal <span class="as-n">' + S.disposals.length + '</span></button>' +
      '<button type="button" role="tab" data-ops="perpindahan">' + ic('arrow-right-left') + 'Perpindahan <span class="as-n">' + S.moves.length + '</span></button>' +
      '<button type="button" role="tab" data-ops="serah-terima">' + ic('file-text') + 'Serah Terima <span class="as-n">' + S.handovers.length + '</span></button></div>' +
      '<div data-ops-body role="tabpanel"></div></section>';

    app.innerHTML = '<div class="as-stack">' + (VIEW ? ops :
      '<section class="as-kpis" aria-label="Ringkasan aset">' +
      kpi('all', 'hard-drive', '', 'Total Perangkat Aktif', active.length, Object.keys(depts).length + ' departemen') +
      kpi('servis', 'wrench', 'info', 'Sedang Diservis', inService, waitParts ? waitParts + ' menunggu suku cadang' : 'Tidak ada yang menunggu suku cadang') +
      kpi('disposed', 'archive', 'warn', 'Sudah Dihapuskan', disposed, inDisposal ? inDisposal + ' pengajuan lama belum selesai' : 'Tersimpan sebagai arsip') +
      kpi('recent', 'circle-plus', 'muted', 'Baru Ditambahkan', recentAdded, '30 hari terakhir · ' + recentMoved + ' perpindahan') +
      '</section>' +
      '<div class="as-tabs" role="tablist" aria-label="Isi rekap">' + tab('perangkat', 'list', 'Daftar Perangkat', active.length) + tab('ringkasan', 'layout-grid', 'Ringkasan', null) + tab('riwayat', 'history', 'Riwayat', S.services.length + S.disposals.length + S.moves.length + S.handovers.length) + '</div>' +
      '<div class="as-stack" data-panel="ringkasan" role="tabpanel" hidden>' +
      '<div class="as-grid-2">' +
      '<section class="rs-card"><div class="rs-card__head"><div><h2>' + ic('chart-column') + 'Perangkat per Kategori</h2><p>Klik kategori untuk melihat perangkatnya.</p></div><button type="button" class="as-linkbtn" data-tab="perangkat">Lihat semua</button></div><div class="rs-card__body">' + catBars(active) + '</div></section>' +
      '<section class="rs-card"><div class="rs-card__head"><div><h2>' + ic('map-pin') + 'Perangkat per Departemen</h2><p>Klik departemen untuk melihat perangkatnya.</p></div></div>' +
      '<div class="rs-card__body" data-locbars>' + locBars(active) + '</div></section>' +
      '</div>' +
      '<div class="as-grid-2">' +
      '<section class="rs-card"><div class="rs-card__head"><div><h2>' + ic('circle-plus') + 'Baru Ditambahkan</h2></div><a class="as-linkbtn" href="registrasi">Registrasi perangkat</a></div>' + recentAddedList(active) + '</section>' +
      '<section class="rs-card"><div class="rs-card__head"><div><h2>' + ic('arrow-right-left') + 'Baru Dipindahkan</h2></div><a class="as-linkbtn" href="lacak">Lacak perangkat</a></div>' + recentMovedList(S) + '</section>' +
      '</div></div>' +
      '<div data-panel="perangkat" role="tabpanel"><section class="rs-card" id="as-devices"><div class="rs-card__head"><div><h2>' + ic('list') + 'Daftar Perangkat</h2><p data-count class="rs-count"></p></div>' +
      '<div class="rs-actions"><button type="button" class="rs-btn rs-btn--primary rs-btn--sm2" data-scancheck>' + ic('scan-line') + 'Scan Barcode</button><button type="button" class="rs-btn rs-btn--ghost rs-btn--sm2" data-export>' + ic('download') + 'Export CSV</button></div></div>' +
      toolbar() + '<div class="as-chipbar" data-chips hidden></div><div data-table></div></section></div>' +
      '<div data-panel="riwayat" role="tabpanel" hidden>' + ops + '</div>') +
      '</div>';
    if (VIEW) keepView();
    AU.render(app);
    wire();
    drawTable(); drawOps();
  }

  /* A recap of one history on its own page: no tabs of the other histories, and its own title. */
  function keepView() {
    var ops = app.querySelector('#as-ops');
    ops.querySelector('.as-subtabs').remove();
    var titles = { servis: ['wrench', 'Riwayat Servis', 'Semua laporan servis dan pemeliharaan, terbaru dulu.'],
      disposal: ['archive', 'Riwayat Disposal', 'Semua perangkat yang sudah dihapuskan, beserta alasan dan metodenya.'],
      perpindahan: ['arrow-right-left', 'Riwayat Penempatan & Perpindahan', 'Semua perpindahan lokasi perangkat, terbaru dulu.'],
      'serah-terima': ['file-text', 'Riwayat Serah Terima', 'Semua berita acara serah terima, terbaru dulu.'] }[VIEW];
    ops.querySelector('.rs-card__head > div').innerHTML = '<h2>' + ic(titles[0]) + esc(titles[1]) + '</h2><p>' + esc(titles[2]) + '</p>';
  }

  function opsRows() {
    var S = AS.state(), by = function (k) { return function (a, b) { return String(b[k]).localeCompare(String(a[k])); }; };
    // A history only shows what happened (status changes are made on the Servis form or in Lacak). Each row also
    // carries its date, status and search text for the filters above the table.
    // Servis: the three dates share one column and documents show as small links, so the table fits the page width.
    if (OPS === 'servis') return { head: ['No. Tiket', 'Perangkat', 'Status', 'Lokasi', 'Jenis & Masalah', 'Teknisi / Vendor', 'Tanggal', 'Dokumen'],
      csvHead: ['No. Tiket', 'ID Aset', 'Nama Perangkat', 'Lokasi', 'Jenis Servis', 'Masalah', 'Dilaporkan', 'Teknisi / Vendor', 'Tenggat Vendor', 'Status', 'Selesai / Ditolak', 'Dokumen'],
      rows: S.services.slice().sort(by('createdAt')).map(function (t) {
        var docs = t.docs || [], loc = AS.svcLoc(t);
        // A click on the row opens the device's detail window (viewDevice), like Daftar Perangkat.
        return { href: 'progres-servis?id=' + encodeURIComponent(t.id), dev: t.deviceId, date: String(t.reportDate || '').slice(0, 10), st: t.status,
          search: [t.id, t.deviceId, t.deviceName, t.type, t.problem, t.technician, AS.locText(loc)].join(' '),
          csv: [t.id, t.deviceId, t.deviceName, AS.locText(loc), t.type, t.problem, t.reportDate, t.technician, t.dueDate || '', AS.SVC_STATUS[t.status].label, t.completedAt, docs.map(function (f) { return f.name; }).join('; ')],
          html: ['<span class="as-code">' + esc(t.id) + '</span>', '<span class="as-name"><b>' + esc(t.deviceName) + '</b><small>' + esc(t.deviceId) + '</small></span>', AU.svcPill(t.status),
            AU.locHtml(loc), '<span class="as-name"><b style="font-weight:500">' + esc(t.type) + '</b><small class="as-clip">' + esc(t.problem) + '</small></span>', esc(t.technician),
            '<span class="as-dates"><span><small>Lapor</small>' + esc(AU.fmtDate(t.reportDate)) + '</span>' +
              (t.dueDate ? '<span><small>Tenggat</small>' + esc(AU.fmtDate(t.dueDate)) + (AS.svcClosed(t.status) ? '' : ' ' + AU.dueBadge(t)) + '</span>' : '') +
              (t.completedAt ? '<span><small>' + (t.status === 'rejected' ? 'Ditolak' : 'Selesai') + '</small>' + esc(AU.fmtDate(t.completedAt)) + '</span>' : '') + '</span>',
            docs.length ? '<span class="as-doclinks">' + docs.map(function (f) { return '<a href="' + esc(AS.fileUrl(f)) + '" target="_blank" rel="noopener" title="' + esc(f.name) + '" aria-label="Buka ' + esc(f.name) + '">' + ic('paperclip') + '</a>'; }).join('') + '</span>' : '<span class="rs-muted">—</span>'] };
      }) };
    if (OPS === 'serah-terima') return { head: ['No. Dokumen', 'Tanggal', 'Perangkat', 'Yang Menyerahkan', 'Yang Menerima', 'Departemen', 'Tanda Tangan', 'PDF'],
      csvHead: ['No. Dokumen', 'Tanggal', 'ID Aset', 'Perangkat', 'Yang Menyerahkan', 'Yang Menerima', 'Departemen', 'Kondisi', 'Tanda Tangan'],
      rows: S.handovers.slice().sort(by('createdAt')).map(function (h) {
        var names = h.devices.map(function (d) { return d.name; }).join(', '), signed = (h.signFrom ? 1 : 0) + (h.signTo ? 1 : 0);
        return { href: 'serah-terima?id=' + encodeURIComponent(h.id), bast: h.id, date: h.date, st: '',
          search: [h.id, h.fromName, h.toName, h.toDept, names, h.devices.map(function (d) { return d.id; }).join(' ')].join(' '),
          csv: [h.id, h.date, h.devices.map(function (d) { return d.id; }).join('; '), names, h.fromName, h.toName, h.toDept, h.condition, signed + ' dari 2'],
          html: ['<span class="as-code">' + esc(h.id) + '</span>', esc(AU.fmtDate(h.date)), '<span class="as-name"><b>' + esc(h.devices[0] ? h.devices[0].name : '') + '</b>' + (h.devices.length > 1 ? '<small>dan ' + (h.devices.length - 1) + ' perangkat lain</small>' : '') + '</span>',
            esc(h.fromName), esc(h.toName), esc(h.toDept), signed === 2 ? AU.pill({ tone: 'ok', label: 'Lengkap' }) : AU.pill({ tone: 'warn', label: signed + ' dari 2' }),
            '<a class="as-iconbtn" href="serah-terima/pdf?id=' + encodeURIComponent(h.id) + '" aria-label="Unduh PDF ' + esc(h.id) + '" title="Unduh PDF">' + ic('download') + '</a>'] };
      }) };
    if (OPS === 'disposal') return { head: ['No. Disposal', 'Perangkat', 'Lokasi Terakhir', 'Alasan & Kondisi', 'Dicatat', 'Oleh', 'Status', 'Tgl. Disposal'],
      rows: S.disposals.slice().sort(by('requestedAt')).map(function (r) {
        return { href: 'disposal?barcode=' + encodeURIComponent(r.deviceId), dsp: r.id, date: localDate(r.requestedAt), st: r.status,
          search: [r.id, r.deviceId, r.deviceName, r.reason, r.condition, r.requestedBy, r.approvedBy, AS.locText(r.lastLocation)].join(' '), csv: [r.id, r.deviceId, r.deviceName, AS.locText(r.lastLocation), r.reason, r.condition, AU.fmtDate(r.requestedAt), r.approvedBy || r.requestedBy, AS.DSP_STATUS[r.status].label, r.disposedAt || r.disposalDate],
          html: ['<span class="as-code">' + esc(r.id) + '</span>', '<span class="as-name"><b>' + esc(r.deviceName) + '</b><small>' + esc(r.deviceId) + '</small></span>', AU.locHtml(r.lastLocation),
            '<span class="as-name"><b style="font-weight:500">' + esc(r.reason) + '</b><small>' + esc(r.condition) + '</small></span>', esc(AU.fmtDate(r.requestedAt)),
            esc((r.status === 'rejected' ? 'Dibatalkan: ' : '') + (r.approvedBy || r.requestedBy)), AU.dspPill(r.status),
            r.disposedAt ? esc(AU.fmtDate(r.disposedAt)) : r.disposalDate ? esc(AU.fmtDate(r.disposalDate)) + ' <small class="rs-muted">(rencana)</small>' : '<span class="rs-muted">—</span>'] };
      }) };
    return { head: ['Perangkat', 'Lokasi Sebelumnya', 'Lokasi Baru', 'Tanggal & Waktu', 'Diperbarui Oleh', 'Alasan'],
      rows: S.moves.slice().sort(by('at')).map(function (m) {
        var d = AS.get(m.deviceId) || { name: m.deviceId };
        // A click on the row opens the device's detail window (viewDevice), like Daftar Perangkat and Rekap Servis.
        return { href: AU.trackUrl(m.deviceId), dev: m.deviceId, date: localDate(m.at), st: '',
          search: [m.deviceId, d.name, m.by, m.reason, m.note, AS.locText(m.from), AS.locText(m.to)].join(' '), csv: [m.deviceId, d.name, AS.locText(m.from), AS.locText(m.to), AU.fmtDateTime(m.at), m.by, m.reason, m.note],
          html: ['<span class="as-name"><b>' + esc(d.name) + '</b><small>' + esc(m.deviceId) + '</small></span>', AU.locHtml(m.from), AU.locHtml(m.to), '<span class="rs-nowrap">' + esc(AU.fmtDateTime(m.at)) + '</span>', esc(m.by),
            '<span class="as-name" style="min-width:150px"><b style="font-weight:500">' + esc(m.reason) + '</b>' + (m.note ? '<small>' + esc(m.note) + '</small>' : '') + '</span>'] };
      }) };
  }
  /* A stored moment (ISO, UTC) as the local calendar day, YYYY-MM-DD. */
  function localDate(v) {
    var d = v ? new Date(v) : null; if (!d || isNaN(d)) return String(v || '').slice(0, 10);
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }

  /* History filters (same as a form's recap): search, month, date range, status, rows per page. */
  var O = { q: '', month: '', from: '', to: '', status: '', per: 25, page: 1 };
  function opsStatuses() {
    var def = OPS === 'servis' ? AS.SVC_STATUS : OPS === 'disposal' ? AS.DSP_STATUS : null;
    return def ? Object.keys(def).map(function (k) { return { value: k, label: def[k].label }; }) : [];
  }
  function opsFiltered() {
    var q = O.q.toLowerCase();
    return opsRows().rows.filter(function (r) {
      if (q && r.search.toLowerCase().indexOf(q) === -1) return false;
      if (O.month && r.date.slice(0, 7) !== O.month) return false;
      if (O.from && r.date < O.from) return false;
      if (O.to && r.date > O.to) return false;
      if (O.status && r.st !== O.status) return false;
      return true;
    });
  }
  function opsFilterBar() {
    var st = opsStatuses();
    return '<form class="fr-filters fr-filters--recap as-opsfilters" role="search" data-opsfilters onsubmit="return false">' +
      '<div class="fr-search">' + ic('search') + '<input class="dash-input" type="search" data-o="q" maxlength="100" placeholder="Cari" aria-label="Cari" value="' + esc(O.q) + '"></div>' +
      '<label class="fr-field"><span>Bulan</span><input class="dash-input" type="month" data-o="month" value="' + esc(O.month) + '"></label>' +
      '<label class="fr-field"><span>Dari tanggal</span><input class="dash-input" type="date" data-o="from" value="' + esc(O.from) + '"></label>' +
      '<label class="fr-field"><span>Sampai tanggal</span><input class="dash-input" type="date" data-o="to" value="' + esc(O.to) + '"></label>' +
      (st.length ? '<label class="fr-field"><span>Status</span><select class="dash-input" data-o="status"><option value="">Semua laporan</option>' +
        st.map(function (s) { return '<option value="' + s.value + '"' + (O.status === s.value ? ' selected' : '') + '>' + esc(s.label) + '</option>'; }).join('') + '</select></label>' : '') +
      '<label class="fr-field fr-field--small"><span>Per halaman</span><select class="dash-input" data-o="per">' +
        [25, 50, 100].map(function (n) { return '<option value="' + n + '"' + (O.per === n ? ' selected' : '') + '>' + n + '</option>'; }).join('') + '</select></label>' +
      '<button type="button" class="section-textbtn fr-reset" data-oreset' + (O.q || O.month || O.from || O.to || O.status ? '' : ' hidden') + '>' + ic('refresh-ccw') + 'Reset filter</button>' +
      '</form>';
  }
  function drawOps() {
    var box = app.querySelector('[data-ops-body]'); if (!box) return;
    app.querySelectorAll('[data-ops]').forEach(function (b) { b.setAttribute('aria-selected', String(b.getAttribute('data-ops') === OPS)); });
    // The filter bar is drawn once per history, so typing in it keeps the focus; only the table below is redrawn.
    if (box.getAttribute('data-for') !== OPS) {
      box.setAttribute('data-for', OPS);
      O.status = ''; O.page = 1;
      box.innerHTML = opsFilterBar() + '<div data-ops-table></div>';
      AU.render(box);
    }
    drawOpsTable();
  }
  function drawOpsTable() {
    var box = app.querySelector('[data-ops-table]'); if (!box) return;
    var t = opsRows(), list = opsFiltered(), total = t.rows.length;
    var pg = AU.pager(list.length, O.page, O.per); O.page = pg.page;
    var rows = list.slice(pg.from ? pg.from - 1 : 0, pg.to);
    var reset = app.querySelector('[data-oreset]'); if (reset) reset.hidden = !(O.q || O.month || O.from || O.to || O.status);
    box.innerHTML = rows.length ? '<div class="rs-table-wrap"><table class="rs-table as-table as-opstable--' + OPS + '"><thead><tr>' + t.head.map(function (h) { return '<th scope="col">' + esc(h) + '</th>'; }).join('') + '</tr></thead><tbody>' +
      // A disposal row (data-dsp) and a service row (data-dev) open a detail window; the other histories open their page.
      rows.map(function (r) { return '<tr data-href="' + esc(r.href) + '"' + (r.dsp ? ' data-dsp="' + esc(r.dsp) + '" title="Lihat detail disposal"' : '') + (r.dev && AS.get(r.dev) ? ' data-dev="' + esc(r.dev) + '" title="Lihat detail perangkat"' : '') + (r.bast ? ' data-bast="' + esc(r.bast) + '" title="Lihat detail serah terima"' : '') + ' tabindex="0">' + r.html.map(function (c) { return '<td>' + c + '</td>'; }).join('') + '</tr>'; }).join('') + '</tbody></table></div>' +
      pg.html.replace(/data-pg=/g, 'data-opspg=')
      : '<div class="rs-empty">' + ic('inbox') + '<b>' + (total ? 'Tidak ada data yang cocok dengan filter' : 'Belum ada data') + '</b></div>';
    AU.render(box);
  }

  function kpiOn(key) { return key === 'recent' ? F.recent : key !== 'all' && F.status === key; }
  function kpi(key, icon, tone, label, value, sub) {
    var on = kpiOn(key);
    return '<button type="button" class="as-kpi" data-kpi="' + key + '" aria-pressed="' + on + '" title="Klik untuk melihat daftarnya"><span class="as-tile' + (tone ? ' as-tile--' + tone : '') + '">' + ic(icon) + '</span>' +
      '<span class="as-kpi__body"><span class="as-kpi__label">' + esc(label) + '</span><span class="as-kpi__value">' + value + '</span><span class="as-kpi__sub">' + esc(sub) + '</span></span></button>';
  }
  function bars(rows, key) {
    if (!rows.length) return '<p class="as-empty-inline">Belum ada data.</p>';
    var max = Math.max.apply(null, rows.map(function (r) { return r.n; }));
    return '<div class="as-bars">' + rows.map(function (r) {
      return '<button type="button" class="as-bar" data-' + key + '="' + esc(r.key) + '" title="Filter: ' + esc(r.label) + '"><span class="as-bar__label">' + (r.icon ? ic(r.icon) : '') + '<span>' + esc(r.label) + '</span></span>' +
        '<span class="as-bar__track"><span class="as-bar__fill" style="width:' + (r.n / max * 100).toFixed(1) + '%"></span></span><span class="as-bar__val">' + r.n + '</span></button>';
    }).join('') + '</div>';
  }
  function catBars(list) {
    return bars(AS.CATEGORIES.map(function (c) { return { key: c.key, label: c.label, icon: c.icon, n: list.filter(function (d) { return d.category === c.key; }).length }; })
      .filter(function (r) { return r.n; }).sort(function (a, b) { return b.n - a.n; }), 'fcat');
  }
  function locBars(list) {
    var cnt = {}; list.forEach(function (d) { var k = deptOf(d); cnt[k] = (cnt[k] || 0) + 1; });
    var rows = Object.keys(cnt).map(function (k) { return { key: k, label: k, n: cnt[k] }; }).sort(function (a, b) { return b.n - a.n; });
    var top = showAllLoc ? rows : rows.slice(0, 8);
    if (!showAllLoc && rows.length > 8) top.push({ key: '', label: 'Lainnya (' + (rows.length - 8) + ' unit)', n: rows.slice(8).reduce(function (s, r) { return s + r.n; }, 0) });
    return bars(top, 'fdept');
  }
  function recentAddedList(list) {
    var rows = list.slice().sort(function (a, b) { return String(b.createdAt).localeCompare(a.createdAt); }).slice(0, 5);
    if (!rows.length) return '<p class="as-empty-inline">Belum ada perangkat.</p>';
    return '<ul class="as-list">' + rows.map(function (d) {
      return '<li><a class="as-list__item" href="' + AU.trackUrl(d.id) + '"><span class="as-tile as-tile--sm">' + ic(AU.catOf(d).icon) + '</span>' +
        '<span class="as-list__text"><b>' + esc(d.name) + '</b><small>' + esc(d.id) + ' · ' + esc(deptOf(d)) + '</small></span>' +
        '<span class="as-list__meta">' + esc(AU.ago(d.createdAt)) + '</span></a></li>';
    }).join('') + '</ul>';
  }
  function recentMovedList(S) {
    var rows = S.moves.slice().sort(function (a, b) { return String(b.at).localeCompare(a.at); }).slice(0, 5);
    if (!rows.length) return '<p class="as-empty-inline">Belum ada perpindahan.</p>';
    return '<ul class="as-list">' + rows.map(function (m) {
      var d = AS.get(m.deviceId) || { name: m.deviceId, category: 'other' };
      return '<li><a class="as-list__item" href="' + AU.trackUrl(m.deviceId) + '"><span class="as-tile as-tile--sm">' + ic(AU.catOf(d).icon) + '</span>' +
        '<span class="as-list__text"><b>' + esc(d.name) + '</b><small class="as-move">' + esc(m.from.dept || m.from.room) + ' ' + ic('arrow-right') + ' ' + esc(m.to.dept || m.to.room) + '</small></span>' +
        '<span class="as-list__meta">' + esc(AU.ago(m.at)) + '<br>' + esc(m.by.split(' (')[0]) + '</span></a></li>';
    }).join('') + '</ul>';
  }

  /* Only the departments that have a device, so a filter never comes up empty. */
  function deptOptions() {
    var set = {}; AS.devices({ includeDisposed: true }).forEach(function (d) { set[deptOf(d)] = 1; });
    return Object.keys(set).sort(function (a, b) { return a.localeCompare(b, 'id'); });
  }
  function toolbar() {
    var opt = function (list, cur, all) { return '<option value="">' + all + '</option>' + list.map(function (o) { var v = o.value || o, l = o.label || o; return '<option value="' + esc(v) + '"' + (v === cur ? ' selected' : '') + '>' + esc(l) + '</option>'; }).join(''); };
    return '<div class="as-toolbar">' +
      '<div class="as-search">' + ic('search') + '<input class="pm-input" type="search" data-f="q" placeholder="Cari nama, barcode, serial, IP…" aria-label="Cari perangkat, juga menurut departemen dan penanggung jawab" value="' + esc(F.q) + '"></div>' +
      '<select class="pm-input pm-input--sm" data-f="cat" aria-label="Kategori">' + opt(AS.CATEGORIES.map(function (c) { return { value: c.key, label: c.label }; }), F.cat, 'Semua kategori') + '</select>' +
      '<select class="pm-input pm-input--sm" data-f="dept" aria-label="Departemen">' + opt(deptOptions(), F.dept, 'Semua departemen') + '</select>' +
      '<select class="pm-input pm-input--sm" data-f="status" aria-label="Status">' + opt(Object.keys(AS.STATUS).map(function (k) { return { value: k, label: AS.STATUS[k].label }; }), F.status, 'Semua status') + '</select>' +
      '<select class="pm-input pm-input--sm" data-f="sort" aria-label="Urutkan"><option value="updated"' + (F.sort === 'updated' ? ' selected' : '') + '>Terakhir diperbarui</option><option value="name"' + (F.sort === 'name' ? ' selected' : '') + '>Nama A–Z</option><option value="id"' + (F.sort === 'id' ? ' selected' : '') + '>Barcode</option><option value="created"' + (F.sort === 'created' ? ' selected' : '') + '>Terbaru didaftarkan</option></select>' +
      '<label class="as-check"><input type="checkbox" data-f="archive"' + (F.archive ? ' checked' : '') + '>Sertakan arsip (disposed)</label>' +
      '</div>';
  }

  function filtered() {
    var q = F.q.toLowerCase();
    var list = AS.devices({ includeDisposed: F.archive || F.status === 'disposed' }).filter(function (d) {
      if (F.cat && d.category !== F.cat) return false;
      if (F.dept && deptOf(d) !== F.dept) return false;
      if (F.building && d.location.building !== F.building) return false;
      if (F.status && d.status !== F.status) return false;
      if (F.recent && !within(d.createdAt, 30)) return false;
      if (q && [d.id, d.name, d.serial, d.ip, d.mac, d.brand, d.model, d.pic, d.location.room, deptOf(d)].join(' ').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
    var cmp = {
      updated: function (a, b) { return String(b.updatedAt).localeCompare(a.updatedAt); },
      created: function (a, b) { return String(b.createdAt).localeCompare(a.createdAt); },
      name: function (a, b) { return a.name.localeCompare(b.name, 'id'); },
      id: function (a, b) { return a.id.localeCompare(b.id); }
    }[F.sort];
    return list.sort(cmp);
  }

  function drawTable() {
    var list = filtered();
    var pg = AU.pager(list.length, F.page, PAGE_SIZE); F.page = pg.page;
    var rows = list.slice(pg.from ? pg.from - 1 : 0, pg.to);
    var box = app.querySelector('[data-table]');
    if (!box) return; // a history-only recap page has no device list
    app.querySelector('[data-count]').textContent = list.length + ' perangkat' + (F.archive ? ' (termasuk arsip)' : '');
    box.innerHTML = rows.length ? '<div class="rs-table-wrap"><table class="rs-table as-table"><thead><tr>' +
      '<th scope="col">Barcode / ID</th><th scope="col">Nama Perangkat</th><th scope="col">Kategori</th><th scope="col">Departemen</th><th scope="col">Status</th><th scope="col">Diperbarui</th><th scope="col" class="as-td-tight"><span class="as-sr">Aksi</span></th></tr></thead><tbody>' +
      rows.map(function (d) {
        // A click on the row shows the device in a window (viewDevice); the pencil edits it, the bin opens Disposal
        // (devices are never hard-deleted: their service and move history stays). Neither for an archived device or
        // one already in disposal.
        var dead = d.status === 'disposed', blank = '<span class="as-iconbtn as-iconbtn--blank" aria-hidden="true"></span>';
        return '<tr data-dev="' + esc(d.id) + '" tabindex="0" title="Lihat detail">' +
          '<td>' + AU.code(d.id) + '</td>' +
          '<td><span class="as-name"><b>' + esc(d.name) + '</b><small>' + esc([[d.brand, d.model].filter(Boolean).join(' '), d.ip].filter(Boolean).join(' · ')) + '</small></span></td>' +
          '<td>' + AU.catChip(d) + '</td>' +
          '<td><span class="as-name"><b style="font-weight:500">' + esc(deptOf(d)) + '</b>' + (d.pic ? '<small>' + esc(d.pic) + '</small>' : '') + '</span></td>' +
          '<td>' + AU.statusPill(d.status) + '</td>' +
          '<td class="rs-nowrap"><span class="as-name" style="min-width:0"><span>' + esc(AU.fmtDate(d.updatedAt)) + '</span><small>' + esc((d.updatedBy || '').split(' (')[0]) + '</small></span></td>' +
          '<td class="as-td-tight"><span class="as-rowacts cl-acts">' +
          (dead || d.status === 'disposal' ? blank : '<a class="as-iconbtn cl-acts__del" href="disposal?barcode=' + encodeURIComponent(d.id) + '" aria-label="Hapus (disposal) ' + esc(d.name) + '" title="Hapus (disposal)">' + ic('trash-2') + '</a>') +
          (dead ? blank : '<a class="as-iconbtn" href="registrasi?edit=' + encodeURIComponent(d.id) + '" aria-label="Edit ' + esc(d.name) + '" title="Edit">' + ic('pencil') + '</a>') +
          '</span></td></tr>';
      }).join('') + '</tbody></table></div>' + pg.html
      : '<div class="rs-empty">' + ic('search') + '<b>Tidak ada perangkat yang cocok</b><span>Ubah kata kunci atau filter.</span><button type="button" class="rs-btn rs-btn--ghost rs-btn--sm2" data-clear>Hapus semua filter</button></div>';
    AU.render(box);
    drawChips();
  }

  function drawChips() {
    var chips = [];
    var lab = { cat: function (v) { return 'Kategori: ' + AS.CAT[v].label; }, dept: function (v) { return 'Departemen: ' + v; }, building: function (v) { return v; }, status: function (v) { return 'Status: ' + AS.STATUS[v].label; } };
    ['cat', 'dept', 'building', 'status'].forEach(function (k) { if (F[k]) chips.push({ k: k, t: lab[k](F[k]) }); });
    if (F.recent) chips.push({ k: 'recent', t: 'Didaftarkan 30 hari terakhir' });
    if (F.q) chips.push({ k: 'q', t: 'Kata kunci: "' + F.q + '"' });
    var bar = app.querySelector('[data-chips]');
    bar.hidden = !chips.length;
    bar.innerHTML = chips.map(function (c) { return '<button type="button" class="as-fchip" data-unset="' + c.k + '" aria-label="Hapus filter ' + esc(c.t) + '">' + esc(c.t) + ic('x') + '</button>'; }).join('') +
      (chips.length > 1 ? '<button type="button" class="as-textbtn" data-clear>Hapus semua</button>' : '');
    AU.render(bar);
  }

  function syncControls() {
    app.querySelectorAll('[data-f]').forEach(function (el) { var k = el.getAttribute('data-f'); if (el.type === 'checkbox') el.checked = !!F[k]; else el.value = F[k]; });
    app.querySelectorAll('[data-kpi]').forEach(function (b) {
      var k = b.getAttribute('data-kpi');
      b.setAttribute('aria-pressed', String(kpiOn(k)));
    });
  }
  function apply(scroll) {
    F.page = 1; syncControls(); drawTable();
    if (scroll) { showTab('perangkat'); app.querySelector('.as-tabs').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  }
  function clearAll() { F.q = ''; F.cat = ''; F.dept = ''; F.building = ''; F.status = ''; F.recent = false; apply(); }

  var wired = false;
  function wire() {
    if (wired) return; wired = true;
    var t;
    app.addEventListener('input', function (e) {
      var oq = e.target.closest('[data-o="q"]');
      if (oq) { clearTimeout(t); t = setTimeout(function () { O.q = oq.value.trim(); O.page = 1; drawOpsTable(); }, 150); return; }
      var el = e.target.closest('[data-f="q"]'); if (!el) return;
      clearTimeout(t); t = setTimeout(function () { F.q = el.value.trim(); F.page = 1; drawTable(); }, 150);
    });
    app.addEventListener('change', function (e) {
      var o = e.target.closest('[data-o]');
      if (o && o.getAttribute('data-o') !== 'q') {
        var ok = o.getAttribute('data-o');
        O[ok] = ok === 'per' ? +o.value : o.value;
        // A month and a date range are two ways to pick a period: choosing one clears the other.
        if (ok === 'month' && O.month) { O.from = ''; O.to = ''; app.querySelector('[data-o="from"]').value = ''; app.querySelector('[data-o="to"]').value = ''; }
        if ((ok === 'from' || ok === 'to') && O[ok]) { O.month = ''; app.querySelector('[data-o="month"]').value = ''; }
        O.page = 1; return drawOpsTable();
      }
      var el = e.target.closest('[data-f]'); if (!el || el.getAttribute('data-f') === 'q') return;
      var k = el.getAttribute('data-f'); F[k] = el.type === 'checkbox' ? el.checked : el.value; apply();
    });
    app.addEventListener('click', function (e) {
      var b;
      if ((b = e.target.closest('[data-tab]'))) return showTab(b.getAttribute('data-tab'));
      if ((b = e.target.closest('[data-kpi]'))) {
        var k = b.getAttribute('data-kpi');
        if (k === 'all') { clearAll(); return apply(true); }
        if (k === 'recent') { F.recent = !F.recent; return apply(true); }
        F.status = F.status === k ? '' : k; return apply(true);
      }
      if ((b = e.target.closest('[data-fcat]'))) { F.cat = b.getAttribute('data-fcat'); return apply(true); }
      if ((b = e.target.closest('[data-fdept], [data-fbuilding]'))) {
        var v = b.getAttribute('data-fdept') || b.getAttribute('data-fbuilding');
        if (v) { F[b.hasAttribute('data-fdept') ? 'dept' : 'building'] = v; return apply(true); }
        showAllLoc = true; var lb0 = app.querySelector('[data-locbars]'); lb0.innerHTML = locBars(AS.devices()); AU.render(lb0); return;
      }
      if ((b = e.target.closest('[data-unset]'))) { var u = b.getAttribute('data-unset'); F[u] = u === 'recent' ? false : ''; return apply(); }
      if (e.target.closest('[data-clear]')) return clearAll();
      if ((b = e.target.closest('[data-pg]'))) { F.page = +b.getAttribute('data-pg'); drawTable(); document.getElementById('as-devices').scrollIntoView({ block: 'start' }); return; }
      if ((b = e.target.closest('[data-view]'))) return viewDevice(b.getAttribute('data-view'));
      if (e.target.closest('[data-scancheck]')) return scanCheck();
      if (e.target.closest('[data-export]')) return exportCsv();
      if ((b = e.target.closest('[data-ops]'))) { OPS = b.getAttribute('data-ops'); history.replaceState(null, '', '#' + OPS); return drawOps(); }
      if ((b = e.target.closest('[data-opspg]'))) { O.page = +b.getAttribute('data-opspg'); drawOpsTable(); document.getElementById('as-ops').scrollIntoView({ block: 'start' }); return; }
      if (e.target.closest('[data-oreset]')) {
        O.q = ''; O.month = ''; O.from = ''; O.to = ''; O.status = ''; O.page = 1;
        app.querySelectorAll('[data-o]').forEach(function (x) { if (x.getAttribute('data-o') !== 'per') x.value = ''; });
        return drawOpsTable();
      }
      // The CSV follows the filters shown.
      if (e.target.closest('[data-opsexport]')) { var t = opsRows(), list = opsFiltered(); AU.downloadCsv(OPS + '-aset-it-' + AU.today() + '.csv', [t.csvHead || t.head].concat(list.map(function (r) { return r.csv; }))); return AU.toast('Export berhasil', list.length + ' baris diekspor.', 'success'); }
      var dr = e.target.closest('tr[data-dev]');
      if (dr && !e.target.closest('a, button')) return viewDevice(dr.getAttribute('data-dev'));
      var ds = e.target.closest('tr[data-dsp]');
      if (ds && !e.target.closest('a, button')) return viewDisposal(ds.getAttribute('data-dsp'));
      var bt = e.target.closest('tr[data-bast]');
      if (bt && !e.target.closest('a, button')) return viewHandover(bt.getAttribute('data-bast'));
      var tr = e.target.closest('tr[data-href]');
      if (tr && !e.target.closest('a, button')) location.href = tr.getAttribute('data-href');
    });
    app.addEventListener('keydown', function (e) {
      if (!(e.key === 'Enter' || e.key === ' ') || !e.target.closest) return;
      var dr = e.target.closest('tr[data-dev]');
      if (dr && e.target === dr) { e.preventDefault(); return viewDevice(dr.getAttribute('data-dev')); }
      var ds = e.target.closest('tr[data-dsp]');
      if (ds && e.target === ds) { e.preventDefault(); return viewDisposal(ds.getAttribute('data-dsp')); }
      var bt = e.target.closest('tr[data-bast]');
      if (bt && e.target === bt) { e.preventDefault(); return viewHandover(bt.getAttribute('data-bast')); }
      var tr = e.target.closest('tr[data-href]');
      if (tr && e.target === tr) { e.preventDefault(); location.href = tr.getAttribute('data-href'); }
    });
  }

  /*
   * A click on a Rekap Disposal row: the disposal in a read-only window — device, reason and condition, who asked and
   * who decided, dates, notes and the berita acara files. Records stay as they are (no edit or delete): an open one
   * is processed or cancelled on the Disposal form, which the window links to.
   */
  function viewDisposal(id) {
    var r = AS.state().disposals.filter(function (x) { return x.id === id; })[0]; if (!r) return;
    var d = AS.get(r.deviceId), open = r.status === 'pending' || r.status === 'approved', cancelled = r.status === 'rejected';
    var group = function (title, rows) { var html = GF.kv(rows); return html.indexOf('<dt>') === -1 ? '' : '<h3 class="as-sec-title as-view__title">' + esc(title) + '</h3>' + html; };
    var c = d ? AU.catOf(d) : null;
    AU.modal({ title: 'Detail Disposal', subtitle: esc(r.id), size: 'md',
      body: '<div class="as-view"><div class="as-devhead"><span class="as-tile">' + ic(c ? c.icon : 'archive') + '</span><div class="as-devhead__text"><b>' + esc(r.deviceName || r.deviceId) + '</b>' +
        '<div class="as-devhead__meta">' + AU.code(r.deviceId) + (d ? '<span>' + esc(AU.catName(d)) + '</span>' : '') + AU.dspPill(r.status) + '</div></div></div>' +
        group('Perangkat', [['Merek / Model', d ? [d.brand, d.model].filter(Boolean).join(' ') : ''], ['Serial Number', d ? d.serial : ''], ['Lokasi Terakhir', AS.locText(r.lastLocation).replace(/^—$/, '')],
          ['Status Perangkat Kini', d ? AU.statusPill(d.status) : '', true]]) +
        group('Alasan & Kondisi', [['Alasan', r.reason], ['Penjelasan', r.reasonDetail], ['Kondisi', r.condition]]) +
        group('Pengajuan & Persetujuan', [['Diajukan', AU.fmtDateTime(r.requestedAt) + (r.requestedBy ? ' · ' + r.requestedBy : '')],
          [cancelled ? 'Dibatalkan' : 'Disetujui', r.approvedAt ? AU.fmtDate(r.approvedAt) + (r.approvedBy ? ' · ' + r.approvedBy + (r.approverRole ? ' (' + r.approverRole + ')' : '') : '') : ''],
          ['Catatan', r.decisionNote]]) +
        group('Penghapusan', [['Tanggal Dihapuskan', r.disposedAt ? AU.fmtDate(r.disposedAt) : ''], ['Rencana Tanggal', !r.disposedAt && r.disposalDate ? AU.fmtDate(r.disposalDate) : ''],
          ['Metode', r.method], ['No. Berita Acara', r.reference]]) +
        '<h3 class="as-sec-title as-view__title">Berita Acara / Lampiran</h3>' + (r.docs && r.docs.length ? AU.docsHtml(r.docs) : '<p class="gf-q__help" style="margin:0">Belum ada lampiran.</p>') + '</div>',
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Tutup</button>' +
        (d ? '<a class="rs-btn rs-btn--ghost" href="' + AU.trackUrl(d.id) + '">' + ic('map-pin') + 'Lokasi & Riwayat</a>' : '') +
        (open ? '<a class="rs-btn rs-btn--primary" href="disposal?barcode=' + encodeURIComponent(r.deviceId) + '">' + ic('clipboard-check') + 'Proses / Batalkan</a>' : '') });
  }

  /*
   * A click on a Rekap Serah Terima row: the berita acara in a read-only window — the two parties, department,
   * condition, the devices handed over and the two signatures. A missing signature is added on its own page; the PDF
   * downloads from here.
   */
  function viewHandover(id) {
    var h = AS.getHandover(id); if (!h) return;
    var group = function (title, rows) { var html = GF.kv(rows); return html.indexOf('<dt>') === -1 ? '' : '<h3 class="as-sec-title as-view__title">' + esc(title) + '</h3>' + html; };
    var sign = function (label, file, name) {
      return '<div class="as-hsig"><span class="as-hsig__label">' + esc(label) + '</span>' +
        (file ? '<img src="' + esc(AS.fileUrl(file)) + '" alt="Tanda tangan ' + esc(name) + '">' : '<span class="as-hsig__none">Belum ditandatangani</span>') +
        '<b>' + esc(name || '—') + '</b></div>';
    };
    var page = 'serah-terima?id=' + encodeURIComponent(h.id), missing = !h.signFrom || !h.signTo;
    AU.modal({ title: 'Detail Serah Terima', subtitle: esc(h.id + ' · ' + AU.fmtDate(h.date)), size: 'md',
      body: '<div class="as-view">' +
        group('Pihak Serah Terima', [['Yang Menyerahkan', h.fromName + (h.fromRole ? ' · ' + h.fromRole : '')], ['Yang Menerima', h.toName + (h.toRole ? ' · ' + h.toRole : '')],
          ['Departemen', h.toDept], ['Kondisi', h.condition], ['Keterangan', h.note], ['Dicatat', AU.fmtDateTime(h.createdAt) + (h.createdBy ? ' · ' + h.createdBy : '')]]) +
        '<h3 class="as-sec-title as-view__title">Perangkat <span class="as-hcount">' + h.devices.length + '</span></h3><ul class="as-hdevs">' + h.devices.map(function (d) {
          return '<li><span class="as-name"><b>' + esc(d.name) + '</b><small>' + esc([d.id, [d.brand, d.model].filter(Boolean).join(' '), d.serial].filter(Boolean).join(' · ')) + '</small></span>' +
            '<a class="sp-link" href="' + AU.trackUrl(d.id) + '">' + ic('map-pin') + 'Lacak</a></li>';
        }).join('') + '</ul>' +
        '<h3 class="as-sec-title as-view__title">Tanda Tangan</h3><div class="as-hsigs">' + sign('Yang Menyerahkan', h.signFrom, h.fromName) + sign('Yang Menerima', h.signTo, h.toName) + '</div></div>',
      note: missing ? '<a class="rs-btn rs-btn--ghost" href="' + page + '">' + ic('pencil') + 'Lengkapi Tanda Tangan</a>' : '',
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Tutup</button>' +
        '<a class="rs-btn rs-btn--primary" href="serah-terima/pdf?id=' + encodeURIComponent(h.id) + '">' + ic('download') + 'Unduh PDF</a>' });
  }

  /* A click on a device row: every detail of one device in a read-only popup, grouped like the Registrasi form. */
  function viewDevice(id) {
    var d = AS.get(id); if (!d) return;
    var c = AU.catOf(d), svc = AS.openService(d.id), dead = d.status === 'disposed';
    var spec = (c.spec || []).filter(function (s) { return d.spec && d.spec[s.key]; }).map(function (s) { return [s.label, s.type === 'date' ? AU.fmtDate(d.spec[s.key]) : d.spec[s.key]]; });
    var group = function (title, rows) { var html = GF.kv(rows); return html.indexOf('<dt>') === -1 ? '' : '<h3 class="as-sec-title as-view__title">' + esc(title) + '</h3>' + html; };
    AU.modal({ title: 'Detail Perangkat', size: 'md',
      body: '<div class="as-view"><div class="as-devhead"><span class="as-tile">' + ic(c.icon) + '</span><div class="as-devhead__text"><b>' + esc(d.name) + '</b>' +
        '<div class="as-devhead__meta">' + AU.code(d.id) + '<span>' + esc(AU.catName(d)) + '</span>' + AU.statusPill(d.status) + '</div></div></div>' +
        group('Identitas', [['Departemen', deptOf(d)], ['Penanggung Jawab', d.pic], ['Merek / Model', [d.brand, d.model].filter(Boolean).join(' ')], ['Serial Number', d.serial]]) +
        group('Jaringan', [['IP Address', d.ip], ['MAC Address', d.mac]]) +
        group('Detail ' + c.label, spec) +
        group('Status & Pengadaan', [['Servis Berjalan', svc ? svc.id + ' · ' + AS.SVC_STATUS[svc.status].label : ''], ['Dihapuskan', dead ? AU.fmtDate(d.disposedAt) : ''],
          ['Tanggal Pembelian', d.purchaseDate ? AU.fmtDate(d.purchaseDate) : ''], d.invoices && d.invoices.length ? ['Invoice Pembelian', AU.docsHtml(d.invoices), true] : null,
          ['Terdaftar', AU.fmtDateTime(d.createdAt) + (d.createdBy ? ' · ' + d.createdBy : '')], ['Diperbarui', AU.fmtDateTime(d.updatedAt) + (d.updatedBy ? ' · ' + d.updatedBy.split(' (')[0] : '')], ['Catatan', d.notes]]) + '</div>',
      note: dead || d.status === 'disposal' ? '' : '<a class="rs-btn rs-btn--danger" href="disposal?barcode=' + encodeURIComponent(d.id) + '">' + ic('trash-2') + 'Hapus (Disposal)</a>',
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Tutup</button>' +
        (dead ? '' : '<a class="rs-btn rs-btn--ghost" href="registrasi?edit=' + encodeURIComponent(d.id) + '">' + ic('pencil') + 'Edit Data</a>') +
        '<a class="rs-btn rs-btn--primary" href="' + AU.trackUrl(d.id) + '">' + ic('map-pin') + 'Lokasi & Riwayat</a>' });
  }

  /* Scan Barcode: checks one device by its label and shows the answer, ready for the next scan. */
  function scanCheck() {
    AU.scan({ title: 'Cek Perangkat dari Barcode', camera: true, onResult: function (code) {
      setTimeout(function () { showScanned(code); }, 0); // after the scanner window has closed
      return true;
    } });
  }
  function showScanned(code) {
    var d = AS.get(code), again = '<button type="button" class="rs-btn rs-btn--ghost" data-again>' + ic('scan-line') + 'Scan Lagi</button>';
    var wire = function (api) { api.$('[data-again]').addEventListener('click', function () { api.close(); scanCheck(); }); };
    if (!d) {
      AU.modal({ title: 'Barcode belum terdaftar', size: 'sm', onMount: wire,
        body: '<div class="as-notice as-notice--warn">' + ic('circle-alert') + '<div>Barcode <code class="as-code">' + esc(code) + '</code> belum terdaftar di Aset IT.</div></div>',
        foot: again + '<a class="rs-btn rs-btn--primary" href="registrasi?code=' + encodeURIComponent(code) + '">' + ic('circle-plus') + 'Daftarkan Perangkat</a>' });
      return;
    }
    var svc = AS.openService(d.id);
    AU.modal({ title: 'Perangkat ditemukan', size: 'sm', onMount: wire,
      body: '<div class="as-devcard"><span class="as-tile">' + ic(AU.catOf(d).icon) + '</span><div class="as-devcard__text"><b>' + esc(d.name) + '</b><small>' + AU.code(d.id) + ' · ' + esc(AU.catName(d)) + '</small></div>' + AU.statusPill(d.status) + '</div>' +
        GF.kv([['Departemen', deptOf(d)], ['Penanggung Jawab', d.pic || '—'], ['Merek / Model', [d.brand, d.model].filter(Boolean).join(' ')], ['Serial Number', d.serial], ['IP Address', d.ip], ['MAC Address', d.mac],
          ['Servis Berjalan', svc ? svc.id + ' · ' + AS.SVC_STATUS[svc.status].label : ''], ['Diperbarui', AU.fmtDate(d.updatedAt)]]),
      foot: again + '<a class="rs-btn rs-btn--primary" href="' + AU.trackUrl(d.id) + '">' + ic('search') + 'Lihat Detail</a>' });
  }

  function exportCsv() {
    var list = filtered();
    var rows = [['Barcode/ID Aset', 'Nama Perangkat', 'Departemen', 'Kategori', 'Merek', 'Model', 'Serial Number', 'IP Address', 'MAC Address', 'Unit Kerja', 'Ruangan', 'Penanggung Jawab', 'Status', 'Tanggal Pembelian', 'Didaftarkan', 'Diperbarui']];
    list.forEach(function (d) {
      rows.push([d.id, d.name, d.department || '', AU.catName(d), d.brand, d.model, d.serial, d.ip, d.mac, d.location.dept, d.location.room, d.pic, AS.STATUS[d.status].label, d.purchaseDate, AU.fmtDateTime(d.createdAt), AU.fmtDateTime(d.updatedAt)]);
    });
    AU.downloadCsv('aset-it-' + AU.today() + '.csv', rows);
    AU.toast('Export berhasil', list.length + ' perangkat diekspor ke CSV.', 'success');
  }
})();
