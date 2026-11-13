/* IT Asset Management — Progres Servis (AssetController::progresServis, GET /aset/progres-servis).
 * One screen in two panels, not a long form: the services on the left (Berjalan / Selesai, status filter, search) and
 * the chosen one on the right — its status change (status, technician, vendor deadline, note, berita acara) beside its
 * report details and progress history. On a wide screen the page fits the window; each panel scrolls on its own.
 * ?id=<SRV-…> or ?barcode=<device> opens one. New reports are made on the Servis & Pemeliharaan form. */
(function () {
  'use strict';
  var AS = window.AS, AU = window.AU, GF = window.GF, esc = AU.esc, ic = AU.ic;
  var app = document.getElementById('as-app');
  var OPEN_KEYS = ['reported', 'progress', 'parts'];
  var AFTER_OPTS = [{ value: 'aktif', label: 'Aktif' }, { value: 'cadangan', label: 'Cadangan' }, { value: 'rusak', label: 'Rusak' }];
  var FLOW = ['reported', 'progress', 'parts', 'done'];
  var V = { tab: 'open', filter: 'all', q: '', sel: null, hist: false };
  var picker = null;

  AS.ready().then(function () {
    AU.consumeFlash();
    var id = AU.qs('id'), b = AU.qs('barcode'), t = id ? find(id) : null;
    if (!t && b) { var d = AS.get(b); t = d ? (AS.openService(d.id) || AS.servicesOf(d.id)[0] || null) : null; }
    if (t) { V.sel = t.id; V.tab = AS.svcClosed(t.status) ? 'done' : 'open'; }
    else if (window.innerWidth >= 1000) { var first = list()[0]; V.sel = first ? first.id : null; }
    shell(); drawList(); drawDetail(); fit();
    window.addEventListener('resize', fit);
  }).catch(AU.fail);

  /* ---------------- data ---------------- */
  function find(id) { return AS.state().services.filter(function (x) { return x.id === id; })[0] || null; }
  function daysOpen(date) {
    var d = date ? new Date(String(date).slice(0, 10) + 'T00:00:00') : null; if (!d || isNaN(d)) return null;
    return Math.max(0, Math.round((new Date(AU.today() + 'T00:00:00') - d) / 86400000));
  }
  function ageText(n) { return n === null ? '' : n === 0 ? 'Hari ini' : n === 1 ? 'Kemarin' : n + ' hari'; }
  // Berjalan: late or near vendor deadlines (≤ 3 days) first, then the longest-waiting. Selesai: newest first.
  function list(tab) {
    tab = tab || V.tab;
    var all = AS.state().services.filter(function (t) { return tab === 'open' ? !AS.svcClosed(t.status) : AS.svcClosed(t.status); });
    if (tab === 'open') {
      var urgent = function (t) { var n = AS.dueIn(t); return n !== null && n <= 3 ? n : Infinity; };
      return all.sort(function (a, b) { return (urgent(a) - urgent(b)) || String(a.reportDate || '').localeCompare(String(b.reportDate || '')) || String(a.id).localeCompare(String(b.id)); });
    }
    return all.sort(function (a, b) { return String(b.completedAt || b.reportDate || '').localeCompare(String(a.completedAt || a.reportDate || '')) || String(b.id).localeCompare(String(a.id)); });
  }
  function shown() {
    var q = V.q.toLowerCase();
    return list().filter(function (t) {
      return (V.tab !== 'open' || V.filter === 'all' || t.status === V.filter) &&
        (!q || [t.id, t.deviceId, t.deviceName, t.type, t.problem, t.technician].join(' ').toLowerCase().indexOf(q) !== -1);
    });
  }

  /* ---------------- layout ---------------- */
  function shell() {
    app.innerHTML = '<div class="sp">' +
      // No head card: the page title is for screen readers; Lihat Rekap and Lapor Servis Baru sit in the row of the
      // back button (AssetController: breadcrumbAside).
      '<h1 class="as-sr">Progres Servis</h1>' +
      '<div class="sp-body" data-body>' +
        '<aside class="sp-list" aria-label="Daftar servis"><div class="sp-list__head">' +
          '<div class="sp-seg" role="tablist" aria-label="Servis">' +
            '<button type="button" role="tab" data-tab="open"></button><button type="button" role="tab" data-tab="done"></button></div>' +
          '<label class="as-search sp-search">' + ic('search') + '<input class="pm-input" type="search" placeholder="Cari tiket, perangkat, teknisi…" data-q aria-label="Cari servis"></label>' +
          '<div class="sp-chips" data-chips role="group" aria-label="Saring status"></div></div>' +
          '<div class="sp-list__items" data-items></div></aside>' +
        '<section class="sp-detail" data-detail aria-live="polite"></section>' +
      '</div></div>';
    AU.render(app);
    app.querySelector('[data-q]').addEventListener('input', function (e) { V.q = e.target.value.trim(); drawList(); });
    app.addEventListener('click', function (e) {
      var b;
      if ((b = e.target.closest('[data-tab]'))) { V.tab = b.getAttribute('data-tab'); V.filter = 'all'; return drawList(); }
      if ((b = e.target.closest('[data-chip]'))) { V.filter = b.getAttribute('data-chip'); return drawList(); }
      if ((b = e.target.closest('[data-sel]')) && !e.target.closest('a')) { select(b.getAttribute('data-sel')); }
    });
    app.addEventListener('keydown', function (e) { if (e.key === 'Enter' && e.target.matches && e.target.matches('tr[data-sel]')) select(e.target.getAttribute('data-sel')); });
  }
  /* Wide screens: the two panels take the rest of the window (no page scroll); narrow screens stack and scroll. */
  function fit() {
    var body = app.querySelector('[data-body]'); if (!body) return;
    if (window.innerWidth < 1000) { body.style.height = ''; return; }
    var h = window.innerHeight - (body.getBoundingClientRect().top + window.scrollY) - 20;
    body.style.height = Math.max(400, h) + 'px';
    // What still sits below the panels (the page footer) comes off too, so the page itself does not scroll.
    var extra = document.documentElement.scrollHeight - window.innerHeight;
    if (extra > 0) body.style.height = Math.max(400, h - extra) + 'px';
  }
  function select(id) {
    V.sel = id;
    var t = find(id); if (t) V.tab = AS.svcClosed(t.status) ? 'done' : 'open'; // the list shows the tab it is in
    if (history.replaceState) history.replaceState(null, '', 'progres-servis?id=' + encodeURIComponent(id));
    drawList(); drawDetail();
    if (window.innerWidth < 1000) app.querySelector('[data-detail]').scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /* ---------------- left: the list ---------------- */
  function drawList() {
    var open = list('open'), done = list('done');
    app.querySelector('[data-tab="open"]').innerHTML = 'Berjalan <span>' + open.length + '</span>';
    app.querySelector('[data-tab="done"]').innerHTML = 'Selesai <span>' + done.length + '</span>';
    app.querySelectorAll('[data-tab]').forEach(function (b) { b.setAttribute('aria-selected', String(b.getAttribute('data-tab') === V.tab)); });
    var chips = app.querySelector('[data-chips]');
    chips.hidden = V.tab !== 'open';
    chips.innerHTML = [['all', 'Semua', open.length]].concat(OPEN_KEYS.map(function (k) { return [k, AS.SVC_STATUS[k].label, open.filter(function (t) { return t.status === k; }).length]; }))
      .map(function (c) { return '<button type="button" class="sp-chip' + (c[0] !== 'all' ? ' sp-chip--' + AS.SVC_STATUS[c[0]].tone : '') + '" data-chip="' + c[0] + '" aria-pressed="' + (V.filter === c[0]) + '">' + esc(c[1]) + ' <b>' + c[2] + '</b></button>'; }).join('');
    var rows = shown(), box = app.querySelector('[data-items]');
    box.innerHTML = rows.length ? rows.map(function (t) {
      var def = AS.SVC_STATUS[t.status], n = daysOpen(t.reportDate), due = AU.dueBadge(t);
      return '<button type="button" class="sp-item sp-item--' + def.tone + (t.id === V.sel ? ' is-on' : '') + '" data-sel="' + esc(t.id) + '" aria-current="' + (t.id === V.sel) + '">' +
        '<span class="sp-item__row"><b class="sp-item__name">' + esc(t.deviceName) + '</b>' + AU.svcPill(t.status) + '</span>' +
        '<span class="sp-item__ids">' + esc(t.id) + ' · ' + esc(t.deviceId) + '</span>' +
        '<span class="sp-item__row sp-item__foot"><span class="sp-item__prob">' + esc(t.type + (t.problem && t.problem !== '-' ? ' — ' + t.problem : '')) + '</span>' +
          (V.tab === 'open' ? (due || '<span class="sp-item__age' + (n !== null && n >= 7 ? ' is-late' : '') + '">' + esc(ageText(n)) + '</span>')
            : '<span class="sp-item__age">' + esc(t.completedAt ? AU.fmtDate(t.completedAt) : '') + '</span>') + '</span></button>';
    }).join('') : '<div class="sp-empty">' + ic(V.tab === 'open' ? 'circle-check' : 'inbox') + '<b>' + (V.q ? 'Tidak ada yang cocok' : V.tab === 'open' ? 'Tidak ada servis yang berjalan' : 'Belum ada servis yang selesai') + '</b>' +
      (V.q ? '<span>Ubah kata kunci pencarian.</span>' : V.tab === 'open' ? '<span>Laporan baru dibuat di formulir Servis & Pemeliharaan.</span>' : '') + '</div>';
    AU.render(app.querySelector('.sp-list'));
  }

  /* ---------------- right: one service ---------------- */
  function drawDetail() {
    var box = app.querySelector('[data-detail]'), t = V.sel ? find(V.sel) : null;
    picker = null;
    if (!t) {
      box.innerHTML = '<div class="sp-empty sp-empty--big">' + ic('activity') + '<b>Pilih servis di daftar</b><span>Progres, ubah status, dan riwayatnya tampil di sini.</span></div>';
      return AU.render(box);
    }
    var d = AS.get(t.deviceId), c = d ? AU.catOf(d) : null, closed = AS.svcClosed(t.status);
    var flow = (t.status === 'rejected' ? ['reported', 'progress', 'rejected'] : FLOW).map(function (k) {
      var at = FLOW.indexOf(t.status), i = FLOW.indexOf(k);
      var cls = k === t.status ? 'is-now' : (t.status === 'rejected' ? k !== 'rejected' : i > -1 && i < at) ? 'is-past' : '';
      return '<span class="sp-step ' + cls + '"><i></i>' + esc(AS.SVC_STATUS[k].label) + '</span>';
    }).join('');
    box.innerHTML =
      '<div class="sp-d__head"><span class="as-tile">' + ic(c ? c.icon : 'wrench') + '</span>' +
        '<div class="sp-d__title"><span class="sp-d__ids">' + AU.code(t.id) + '<span>' + esc(t.type) + '</span></span><h2>' + esc(t.deviceName) + '</h2>' +
        '<p>' + esc([t.deviceId, d ? AU.catName(d) : '', AS.deptText(AS.svcLoc(t))].filter(Boolean).join(' · ')) + '</p></div>' +
        '<div class="sp-d__status">' + AU.svcPill(t.status) + AU.dueBadge(t) + '</div></div>' +
      '<div class="sp-flow">' + flow + '</div>' +
      // Two wide rows, one under the other: Ubah Status, then every service of this device.
      '<div class="sp-rows"><section class="sp-row">' + (closed ? closedHtml(t, d) : formHtml(t)) + '</section>' +
        '<section class="sp-row sp-row--info">' + deviceHistoryHtml(t) + '</section></div>';
    AU.render(box);
    if (!closed) wireForm(box, t);
    var hb = box.querySelector('[data-hist]'); if (hb) hb.addEventListener('toggle', function () { V.hist = hb.open; });
  }

  /* Riwayat Servis Perangkat: one closed line ("Lihat riwayat servis · N kali") that opens, on a click, every service
     of this device in one line each (newest first); a row opens that service here. Open or closed is kept while
     moving between services. */
  function deviceHistoryHtml(t) {
    var all = AS.servicesOf(t.deviceId);
    return '<details class="sp-histbox" data-hist' + (V.hist ? ' open' : '') + '><summary>' + ic('history') + '<span>Riwayat servis perangkat</span>' +
        '<b class="sp-histbox__n">' + all.length + ' kali diservis</b><span class="sp-histbox__toggle"></span></summary>' +
      '<div class="sp-histbox__body"><a class="sp-link" href="' + AU.trackUrl(t.deviceId) + '">' + ic('map-pin') + 'Lokasi & riwayat perangkat</a>' +
      '<div class="rs-table-wrap sp-shist"><table class="rs-table as-table"><thead><tr><th scope="col">No. Tiket</th><th scope="col">Jenis & Masalah</th><th scope="col">Dilaporkan</th>' +
        '<th scope="col">Teknisi / Vendor</th><th scope="col">Selesai / Ditolak</th><th scope="col">Status</th></tr></thead><tbody>' +
      all.map(function (x) {
        return '<tr data-sel="' + esc(x.id) + '" tabindex="0"' + (x.id === t.id ? ' class="is-on" aria-current="true"' : '') + ' title="Buka ' + esc(x.id) + '">' +
          '<td>' + AU.code(x.id) + (x.id === t.id ? ' <small class="sp-shist__now">ini</small>' : '') + '</td>' +
          '<td><span class="as-name"><b style="font-weight:500">' + esc(x.type) + '</b><small class="as-clip">' + esc(x.problem || '') + '</small></span></td>' +
          '<td class="rs-nowrap">' + esc(AU.fmtDate(x.reportDate)) + '</td><td>' + esc(x.technician || '—') + '</td>' +
          '<td class="rs-nowrap">' + (x.completedAt ? esc(AU.fmtDate(x.completedAt)) : '<span class="rs-muted">—</span>') + '</td><td>' + AU.svcPill(x.status) + '</td></tr>';
      }).join('') + '</tbody></table></div></div></details>';
  }
  function closedHtml(t, d) {
    var rejected = t.status === 'rejected';
    return '<div class="sp-closed-note ' + (rejected ? 'is-err' : 'is-ok') + '">' + ic(rejected ? 'triangle-alert' : 'circle-check') +
      '<div><b>Servis ' + (rejected ? 'ditolak' : 'selesai') + (t.completedAt ? ' pada ' + esc(AU.fmtDate(t.completedAt)) : '') + '</b>' +
      '<span>Status tidak bisa diubah lagi.</span></div></div>' +
      (d && d.status !== 'disposed' && d.status !== 'disposal' && !AS.openService(d.id)
        ? '<a class="rs-btn rs-btn--ghost sp-again" href="servis?barcode=' + encodeURIComponent(d.id) + '">' + ic('circle-plus') + 'Lapor servis baru untuk perangkat ini</a>' : '');
  }
  /* The status change of an open service. Saved here with the same checks as before on the Servis form. */
  function formHtml(t) {
    var today = AU.today();
    // The status is one dropdown, the current one chosen and marked "(saat ini)".
    // Dilaporkan is not offered (a service is followed from Dalam Proses on); only an older ticket still on it shows it.
    var options = Object.keys(AS.SVC_STATUS).filter(function (k) { return k !== 'reported' || t.status === 'reported'; }).map(function (k) {
      return '<option value="' + k + '"' + (k === t.status ? ' selected' : '') + '>' + esc(AS.SVC_STATUS[k].label) + (k === t.status ? ' (saat ini)' : '') + '</option>';
    }).join('');
    // The status on its own highlighted line (current → new), then technician, deadline and note side by side,
    // then berita acara and Simpan.
    return '<form class="sp-form" novalidate><h3 class="sp-h">Ubah Status</h3>' +
      '<div class="sp-stbox" data-stbox><div class="sp-stbox__now"><span>Status saat ini</span>' + AU.svcPill(t.status) + '</div>' +
        '<span class="sp-stbox__arrow" aria-hidden="true">' + ic('chevron-right') + '</span>' +
        '<label class="sp-stbox__new"><span>Ubah ke <i class="req">*</i></span><select class="pm-input" name="st">' + options + '</select></label>' +
        '<span class="sp-stbox__hint" data-hint></span></div>' +
      '<div class="sp-fields"><label class="sp-field"><span>Teknisi / Vendor <i class="req">*</i></span><input class="pm-input" name="tech" list="sp-tech" value="' + esc(t.technician || '') + '" autocomplete="off">' +
        '<datalist id="sp-tech">' + AS.TECHNICIANS.concat(AS.VENDORS).map(function (x) { return '<option value="' + esc(x) + '">'; }).join('') + '</datalist></label>' +
        '<label class="sp-field"><span>Tenggat Vendor</span><input class="pm-input" type="date" name="due" value="' + esc(t.dueDate || '') + '" min="' + esc(t.reportDate) + '"></label>' +
        '<label class="sp-field sp-field--note"><span>Catatan <small class="as-stdlg__opt" data-noteopt>Opsional</small></span><input class="pm-input" name="note" maxlength="1000" placeholder="Contoh: menunggu SSD pengganti dari vendor" autocomplete="off"></label></div>' +
      '<div class="sp-fields sp-closedq" data-closed hidden><label class="sp-field"><span><span data-cdl>Tanggal Selesai</span> <i class="req">*</i></span><input class="pm-input" type="date" name="cdate" value="' + today + '" min="' + esc(t.reportDate) + '" max="' + today + '"></label>' +
        '<div class="sp-field sp-field--wide"><span>Status Perangkat Setelahnya <i class="req">*</i></span><div class="sp-after">' +
        AFTER_OPTS.map(function (o) { return '<label><input type="radio" name="after" value="' + o.value + '"' + (o.value === 'aktif' ? ' checked' : '') + '>' + esc(o.label) + '</label>'; }).join('') + '</div></div></div>' +
      '<div class="sp-form__foot"><div class="sp-docs"><span class="sp-docs__label">Berita Acara <small class="as-stdlg__opt" data-docopt>Opsional</small></span>' + AU.fileField('spdocs', { max: 10 }) + '</div>' +
        '<p class="rs-error" data-err hidden></p><button type="submit" class="rs-btn rs-btn--primary">' + ic('save') + 'Simpan Perubahan</button></div></form>';
  }
  function wireForm(box, t) {
    var f = box.querySelector('.sp-form'), err = f.querySelector('[data-err]');
    picker = AU.filePicker(f, 'spdocs', { max: 10, onChange: function () { paint(); } });
    function val(n) { var x = f.querySelector('[name="' + n + '"]:checked') || f.querySelector('[name="' + n + '"]:not([type=radio])'); return x ? String(x.value).trim() : ''; }
    function report(st) { return AS.svcClosed(st) && (st === 'rejected' || AS.endStatus(st, val('after')) === 'rusak'); }
    function paint() {
      var st = val('st'), closed = AS.svcClosed(st), hasDocs = (t.docs && t.docs.length) || (picker && picker.count());
      f.querySelector('[data-closed]').hidden = !closed;
      f.querySelector('[data-cdl]').textContent = st === 'rejected' ? 'Tanggal Ditolak' : 'Tanggal Selesai';
      f.querySelector('[data-docopt]').textContent = report(st) ? 'Wajib' : 'Opsional';
      f.querySelector('[data-docopt]').classList.toggle('is-req', report(st) && !hasDocs);
      f.querySelector('[data-noteopt]').textContent = st === t.status ? 'Wajib bila status tetap' : 'Opsional';
      f.querySelector('[data-hint]').textContent = (st === t.status ? 'Status tetap: catat progresnya di bawah.' : 'Status akan berubah: ' + AS.SVC_STATUS[t.status].label + ' → ' + AS.SVC_STATUS[st].label) +
        (st === 'parts' && t.status !== 'parts' ? ' · Akan masuk Perlu Perhatian.' : '');
      f.querySelector('[data-stbox]').classList.toggle('is-changed', st !== t.status);
    }
    f.addEventListener('change', function (e) {
      if (e.target.name === 'st' && AS.svcClosed(e.target.value)) { var a = f.querySelector('[name="after"][value="' + (e.target.value === 'rejected' ? 'rusak' : 'aktif') + '"]'); if (a) a.checked = true; }
      err.hidden = true; paint();
    });
    f.addEventListener('input', function () { err.hidden = true; });
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var st = val('st') || t.status, closed = AS.svcClosed(st), note = val('note'), tech = val('tech'), due = val('due'), cdate = val('cdate'), after = val('after');
      var today = AU.today(), changed = st !== t.status || tech !== (t.technician || '') || due !== (t.dueDate || '') || picker.count();
      var msg = !tech ? 'Isi teknisi / vendor.'
        : !changed && !note ? 'Ubah status, tenggat, atau teknisi, atau tulis catatan progres.'
        : due && due < t.reportDate ? 'Tenggat vendor tidak boleh sebelum tanggal laporan (' + AU.fmtDate(t.reportDate) + ').'
        : closed && (!cdate || cdate < t.reportDate || cdate > today) ? 'Tanggal ' + (st === 'rejected' ? 'ditolak' : 'selesai') + ' harus antara ' + AU.fmtDate(t.reportDate) + ' dan hari ini.'
        : report(st) && !picker.count() && !(t.docs && t.docs.length) ? (st === 'rejected' ? 'Unggah berita acara penolakan servis.' : 'Perangkat dinyatakan rusak: unggah berita acara kerusakan.') : '';
      if (msg) { err.textContent = msg; err.hidden = false; return; }
      // The status before saving (the store changes the service object itself).
      var btn = f.querySelector('button[type="submit"]'), was = t.status; btn.disabled = true;
      // Documents go to the portal first; the service then lists them (a refused save keeps them for the retry).
      picker.upload('berita_acara').then(function (docs) {
        AS.updateService(t.id, { status: st, note: note, technician: tech, notes: closed ? note : undefined, dueDate: due,
          completedAt: closed ? cdate : '', afterStatus: closed ? after : '', docs: docs });
        return AS.save();
      }).then(function () {
        var now = find(t.id), is = now ? now.status : st;
        // Menunggu Suku Cadang puts the service in "Perlu perhatian" (header bell and page) while it waits; leaving it
        // takes it out. The bell is drawn by the server, so the page reloads on this one and says so.
        if ((is === 'parts') !== (was === 'parts')) {
          AU.flash(is === 'parts' ? 'Masuk Perlu Perhatian' : 'Servis diperbarui',
            is === 'parts' ? t.deviceName + ' (' + t.id + ') menunggu suku cadang dan tampil di notifikasi Perlu Perhatian sampai statusnya berubah.'
              : t.id + ' · ' + AS.SVC_STATUS[is].label + '. Tidak lagi di Perlu Perhatian.');
          location.reload();
          return;
        }
        AU.toast('Servis diperbarui', t.id + ' · ' + AS.SVC_STATUS[is].label, 'success');
        if (now && AS.svcClosed(now.status)) V.tab = 'done';
        drawList(); drawDetail();
      }).catch(function (x) { btn.disabled = false; err.textContent = (x && x.message) || 'Perubahan belum tersimpan. Coba lagi.'; err.hidden = false; });
    });
    paint();
  }
})();
