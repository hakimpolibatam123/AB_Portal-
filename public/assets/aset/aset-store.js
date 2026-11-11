/*
 * IT Asset Management — data layer.
 * Devices, location moves, service tickets, disposal requests and an activity log.
 * Storage: the portal server (GET aset/data, POST aset/perintah). Every operation runs here first on the page's copy
 * and is queued; save() sends the queue, the server replays it with the same rules (App\Services\AssetLedger) as the
 * signed-in account, and the page continues with the server's state. Nothing is ever hard-deleted.
 */
(function () {
  'use strict';

  /* Extra Lucide icons (v0.460, ISC) used by the module; added before site.js renders icons */
  var EXTRA_ICONS = {
    'laptop': '<path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45L4 16" />',
    'barcode': '<path d="M3 5v14" /> <path d="M8 5v14" /> <path d="M12 5v14" /> <path d="M17 5v14" /> <path d="M21 5v14" />',
    'scan-line': '<path d="M3 7V5a2 2 0 0 1 2-2h2" /> <path d="M17 3h2a2 2 0 0 1 2 2v2" /> <path d="M21 17v2a2 2 0 0 1-2 2h-2" /> <path d="M7 21H5a2 2 0 0 1-2-2v-2" /> <path d="M7 12h10" />',
    'arrow-right-left': '<path d="m16 3 4 4-4 4" /> <path d="M20 7H4" /> <path d="m8 21-4-4 4-4" /> <path d="M4 17h16" />',
    'arrow-right': '<path d="M5 12h14" /> <path d="m12 5 7 7-7 7" />',
    'tag': '<path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z" /> <circle cx="7.5" cy="7.5" r=".5" fill="currentColor" />',
    'keyboard': '<path d="M10 8h.01" /> <path d="M12 12h.01" /> <path d="M14 8h.01" /> <path d="M16 12h.01" /> <path d="M18 8h.01" /> <path d="M6 8h.01" /> <path d="M7 16h10" /> <path d="M8 12h.01" /> <rect width="20" height="16" x="2" y="4" rx="2" />',
    'ban': '<circle cx="12" cy="12" r="10" /> <path d="m4.9 4.9 14.2 14.2" />',
    'filter': '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />'
  };
  window.RS_ICONS = window.RS_ICONS || {};
  Object.keys(EXTRA_ICONS).forEach(function (k) { if (!window.RS_ICONS[k]) window.RS_ICONS[k] = EXTRA_ICONS[k]; });

  /* ---------------- master data ---------------- */
  var NET_SPEC = [
    { key: 'hostname', label: 'Hostname', ph: 'BTN-ADM-01' }
  ];
  var PC_SPEC = [
    { key: 'hostname', label: 'Hostname', ph: 'BTN-ADM-01' },
    { key: 'os', label: 'Sistem Operasi', type: 'select', options: ['Windows 11 Pro', 'Windows 10 Pro', 'Ubuntu 22.04 LTS', 'macOS', 'Lainnya'] },
    { key: 'cpu', label: 'Prosesor', ph: 'Intel Core i5-12500' },
    { key: 'ram', label: 'RAM', type: 'select', options: ['4 GB', '8 GB', '16 GB', '32 GB', '64 GB'] },
    { key: 'storage', label: 'Penyimpanan', ph: 'SSD 512 GB' }
  ];
  /* net: shows IP + MAC; dhcp: IP may be dynamic */
  var CATEGORIES = [
    { key: 'pc', label: 'PC Desktop', short: 'PC', icon: 'monitor', prefix: 'PC', net: true, spec: PC_SPEC },
    { key: 'minipc', label: 'Mini PC', short: 'Mini PC', icon: 'cpu', prefix: 'MPC', net: true, spec: PC_SPEC },
    { key: 'laptop', label: 'Laptop', short: 'Laptop', icon: 'laptop', prefix: 'LTP', net: true, dhcp: true, spec: PC_SPEC },
    { key: 'monitor', label: 'Monitor', short: 'Monitor', icon: 'app-window', prefix: 'MON', net: false, spec: [
      // Typed (any size), the common ones offered as suggestions; a bare number gets " inch" (aset-register.js).
      { key: 'size', label: 'Ukuran Layar', type: 'text', unit: 'inch', inputmode: 'decimal', ph: 'Ketik ukuran, mis. 23.8', help: 'Ketik ukuran layar dalam inch, atau pilih dari saran.',
        options: ['19 inch', '21.5 inch', '24 inch', '27 inch', '32 inch', '43 inch', '55 inch'] },
      { key: 'pairedWith', label: 'Terhubung ke Perangkat', ph: 'Barcode PC, mis. AB-BTN-PC-0001', mono: true, help: 'Opsional. Isi barcode PC/Mini PC yang memakai monitor ini.' }
    ] },
    { key: 'printer', label: 'Printer', short: 'Printer', icon: 'printer', prefix: 'PRN', net: 'optional', spec: [
      { key: 'printerType', label: 'Jenis Printer', type: 'select', options: ['Laser', 'Inkjet', 'Thermal (Struk/Label)', 'Dot Matrix', 'Multifungsi'] },
      { key: 'connection', label: 'Koneksi', type: 'select', options: ['USB', 'Jaringan (LAN)', 'Wi-Fi'], netSwitch: true },
      { key: 'consumable', label: 'Tinta / Toner / Pita', ph: 'HP 76A (CF276A)' },
      { key: 'paper', label: 'Ukuran Kertas', type: 'select', options: ['A4', 'F4 / Folio', 'Struk 80 mm', 'Label / Gelang', 'Continuous Form'] },
      { key: 'counter', label: 'Page Counter Terakhir', ph: '12500', inputmode: 'numeric' }
    ] },
    { key: 'scanner', label: 'Scanner Dokumen', short: 'Scanner', icon: 'scan', prefix: 'SCN', net: false, spec: [
      { key: 'connection', label: 'Koneksi', type: 'select', options: ['USB', 'Jaringan (LAN)'] }
    ] },
    { key: 'cctv', label: 'CCTV', short: 'CCTV', icon: 'video', prefix: 'CCTV', net: true, spec: [
      { key: 'camType', label: 'Tipe Kamera', type: 'select', options: ['Dome', 'Bullet', 'PTZ', 'Fisheye'] },
      { key: 'resolution', label: 'Resolusi', type: 'select', options: ['2 MP (1080p)', '4 MP', '5 MP', '8 MP (4K)'] },
      { key: 'nvr', label: 'NVR / Channel', ph: 'NVR-01 / CH 05' }
    ] },
    { key: 'network', label: 'Perangkat Jaringan', short: 'Jaringan', icon: 'router', prefix: 'NET', net: true, spec: [
      { key: 'netType', label: 'Jenis Perangkat', type: 'select', options: ['Switch', 'Router', 'Access Point', 'Firewall', 'Modem / ONT'] },
      { key: 'ports', label: 'Jumlah Port', ph: '24', inputmode: 'numeric' },
      { key: 'firmware', label: 'Versi Firmware', ph: 'RouterOS 7.14' }
    ] },
    { key: 'server', label: 'Server', short: 'Server', icon: 'server', prefix: 'SRV', net: true, spec: [
      { key: 'hostname', label: 'Hostname', ph: 'BTN-HIS-APP01' },
      { key: 'os', label: 'Sistem Operasi', ph: 'Ubuntu Server 22.04 / Windows Server 2022' },
      { key: 'rack', label: 'Posisi Rak', ph: 'Rak Server 1 / U12' },
      { key: 'cpu', label: 'Prosesor', ph: '2x Intel Xeon Silver 4310' },
      { key: 'ram', label: 'RAM', ph: '128 GB' },
      { key: 'storage', label: 'Penyimpanan', ph: '4x SSD 960 GB RAID 10' }
    ] },
    { key: 'ups', label: 'UPS', short: 'UPS', icon: 'battery-charging', prefix: 'UPS', net: 'optional', spec: [
      { key: 'capacity', label: 'Kapasitas', ph: '3000 VA' },
      { key: 'batteryDate', label: 'Tanggal Ganti Baterai Terakhir', type: 'date' }
    ] },
    { key: 'other', label: 'Lainnya', short: 'Lainnya', icon: 'box', prefix: 'OTH', net: 'optional', spec: [] }
  ];
  var CAT = {}; CATEGORIES.forEach(function (c) { CAT[c.key] = c; });

  var BUILDINGS = ['Gedung Utama', 'Gedung Penunjang', 'Gedung Parkir'];
  var FLOORS = ['Basement', 'Lantai 1', 'Lantai 2', 'Lantai 3', 'Lantai 4', 'Lantai 5', 'Lantai 6'];
  var DEPTS = ['Admisi & Pendaftaran', 'UGD', 'Poliklinik', 'Rawat Inap', 'ICU', 'Kamar Operasi', 'Farmasi', 'Laboratorium',
    'Radiologi', 'Rekam Medis', 'Kasir & Keuangan', 'Gizi', 'Manajemen', 'Keamanan', 'Instalasi IT', 'Gudang IT'];

  /* device statuses; "servis", "disposal" and "disposed" are set by their workflows */
  var STATUS = {
    aktif: { label: 'Aktif', tone: 'ok' },
    cadangan: { label: 'Cadangan', tone: 'muted' },
    rusak: { label: 'Rusak', tone: 'err' },
    servis: { label: 'Dalam Servis', tone: 'info' },
    disposal: { label: 'Proses Disposal', tone: 'warn' },
    disposed: { label: 'Disposed', tone: 'dark' }
  };
  var SVC_STATUS = {
    reported: { label: 'Dilaporkan', tone: 'muted' },
    progress: { label: 'Dalam Proses', tone: 'info' },
    parts: { label: 'Menunggu Suku Cadang', tone: 'warn' },
    done: { label: 'Selesai', tone: 'ok' },
    rejected: { label: 'Ditolak', tone: 'err' }
  };
  // A service ends as done or rejected (e.g. warranty claim refused, with its berita acara); both free the device.
  var SVC_CLOSED = ['done', 'rejected'];
  function svcClosed(s) { return SVC_CLOSED.indexOf(s) !== -1; }
  // Uploaded documents as an operation lists them: [{id, name, type, size}] (the server keeps only known files).
  function files(list) { return (list || []).filter(function (f) { return f && f.id; }).map(function (f) { return { id: f.id, name: f.name, type: f.type, size: f.size }; }); }
  var SVC_TYPES = ['Perbaikan', 'Pemeliharaan Rutin', 'Penggantian Komponen', 'Instal Ulang / Konfigurasi', 'Klaim Garansi', 'Kalibrasi / Pengecekan'];
  var DSP_STATUS = {
    pending: { label: 'Menunggu Persetujuan', tone: 'warn' },
    approved: { label: 'Disetujui', tone: 'info' },
    disposed: { label: 'Dihapuskan', tone: 'dark' },
    rejected: { label: 'Dibatalkan', tone: 'err' }
  };
  var DSP_REASONS = ['Rusak berat / tidak ekonomis diperbaiki', 'Usang / tidak didukung lagi', 'Diganti unit baru (upgrade)', 'Hilang', 'Lainnya'];
  var DSP_CONDITIONS = ['Rusak total, tidak berfungsi', 'Rusak sebagian', 'Berfungsi, namun usang', 'Hilang / tidak ditemukan'];
  var DSP_METHODS = ['Pemusnahan melalui vendor e-waste', 'Lelang', 'Hibah', 'Dikembalikan ke vendor / tukar tambah'];
  var MOVE_REASONS = ['Relokasi unit kerja', 'Penggantian unit', 'Peminjaman sementara', 'Dikembalikan ke gudang', 'Dibawa untuk perbaikan', 'Instalasi baru', 'Lainnya'];
  var TECHNICIANS = ['Andi Pratama (IT Support)', 'Dimas Saputra (IT Support)', 'Fajar Nugroho (Network Engineer)', 'IT botania'];
  var VENDORS = ['Service Center HP', 'Service Center Epson', 'Vendor CCTV (Kontrak Pemeliharaan)', 'Distributor APC', 'Service Center Lenovo'];

  /* ---------------- helpers ---------------- */
  function clone(o) { return o == null ? o : JSON.parse(JSON.stringify(o)); }
  function nowIso() { return new Date().toISOString(); }
  // A label QR holds a link to the hospital's inventory (https://…/asset-inventaris/history/ITEM-00049895), and a
  // scanner may drop its start (ASSET-INVENTARIS/HISTORY/ITEM-00049895): the asset code is the last path segment.
  // Same rules as AssetLedger::normId() / codeProblem() on the server.
  function normId(s) {
    var v = String(s || '').replace(/[\u0000-\u001F]/g, '').trim().replace(/[?#].*$/, '');
    if (v.indexOf('/') !== -1) {
      var last = v.split('/').filter(Boolean).pop() || '';
      try { v = decodeURIComponent(last); } catch (e) { v = last; }
    }
    return v.trim().toUpperCase();
  }
  // Accepted asset codes: the hospital label (ITEM- + 8 digits) and the portal's own IDs (AB-BTN-<kategori>-0001).
  var CODE_FORMATS = [/^ITEM-\d{8}$/, /^AB-BTN-(PC|MPC|LTP|MON|PRN|SCN|CCTV|NET|SRV|UPS|OTH)-\d{4}$/];
  function codeProblem(s) {
    var c = normId(s);
    if (!c) return 'Scan atau ketik barcode terlebih dahulu.';
    if (CODE_FORMATS.some(function (re) { return re.test(c); })) return null;
    if (/^ITEM-?\d*$/.test(c) || /^AB-BTN-/.test(c)) return 'Barcode "' + c + '" tidak lengkap. Scan ulang label perangkat.';
    return 'Format barcode "' + c + '" tidak dikenali. Gunakan label aset (contoh ITEM-00049895) atau ID AB-BTN-PC-0001.';
  }
  function pad(n, w) { return String(n).padStart(w || 4, '0'); }
  function me() { return (window.RS && window.RS.currentUser) ? window.RS.currentUser().name : 'IT botania'; }
  // A location is its department / unit (Ruangan, Gedung and Lantai are no longer asked; older records may still have them).
  function locKey(l) { return l ? String(l.dept || '') : ''; }
  function sameLoc(a, b) { return locKey(a) === locKey(b); }
  /* MAC is free text; only a plain 12-hex-digit MAC (any separators) is written as 3C:D9:2B:1A:2F:4E. */
  function normMac(s) {
    var raw = String(s || '').trim(), hex = raw.replace(/[^0-9a-f]/gi, '').toUpperCase();
    if (hex.length !== 12 || !/^[0-9a-f:.\-\s]+$/i.test(raw)) return raw.toUpperCase();
    return hex.match(/.{2}/g).join(':');
  }


  /* ---------------- persistence: the portal server ---------------- */
  // <body data-aset-api> is the module's address on the portal (…/aset); writes carry the session's CSRF token.
  var API = (document.body && document.body.getAttribute('data-aset-api')) || '.';
  var queue = [];
  function csrf() { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; }
  function request(path, body) {
    var headers = { Accept: 'application/json' };
    if (body) { headers['Content-Type'] = 'application/json'; headers['X-CSRF-Token'] = csrf(); }
    return fetch(API + '/' + path, { method: body ? 'POST' : 'GET', credentials: 'same-origin', headers: headers, body: body ? JSON.stringify(body) : undefined })
      .then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (data) {
          if (r.status === 401 || r.status === 419) throw new Error(data.message || 'Sesi berakhir. Muat ulang halaman lalu masuk kembali.');
          if (!r.ok || !data.state) throw new Error(data.message || 'Data aset tidak dapat diproses. Coba lagi.');
          return data.state;
        });
      }, function () { throw new Error('Tidak dapat terhubung ke server. Periksa koneksi, lalu coba lagi.'); });
  }
  /*
   * Documents (berita acara servis, invoice pembelian, dokumen disposal): each File goes to POST aset/berkas one
   * after another and comes back as {id, name, type, size}; an operation then lists them (docs / invoices).
   */
  function upload(fileList, kind) {
    var list = Array.prototype.slice.call(fileList || []), out = [];
    return list.reduce(function (p, f) {
      return p.then(function () {
        var body = new FormData(); body.append('file', f); body.append('jenis', kind);
        return fetch(API + '/berkas', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-Token': csrf() }, body: body })
          .then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) {
              if (!r.ok || !data.file) throw new Error((data.message || 'Berkas tidak dapat diunggah.') + ' (' + f.name + ')');
              out.push(data.file);
            });
          }, function () { throw new Error('Tidak dapat terhubung ke server saat mengunggah ' + f.name + '.'); });
      });
    }, Promise.resolve()).then(function () { return out; });
  }
  /* Import Perangkat: a filled template checked by the server; with save, its valid rows are stored. */
  function importFile(file, save) {
    var body = new FormData(); body.append('file', file); if (save) body.append('simpan', '1');
    return fetch(API + '/registrasi/impor', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-Token': csrf() }, body: body })
      .then(function (r) {
        return r.json().catch(function () { return {}; }).then(function (data) {
          if (!r.ok) throw new Error(data.message || 'File tidak dapat diproses. Coba lagi.');
          return data;
        });
      }, function () { throw new Error('Tidak dapat terhubung ke server. Periksa koneksi, lalu coba lagi.'); });
  }
  /* Where an uploaded document opens (/berkas/{id}, signed-in only). */
  function fileUrl(f) { return f && f.id ? API.replace(/\/aset$/, '') + '/berkas/' + encodeURIComponent(f.id) : ''; }

  var S = null; // state
  function empty() { return { v: 1, seq: { srv: 0, dsp: 0, mv: 0, act: 0, bast: 0 }, devices: {}, moves: [], services: [], disposals: [], handovers: [], activity: [] }; }

  function log(deviceId, type, text, at, by) {
    S.seq.act++;
    S.activity.push({ id: 'A' + S.seq.act, deviceId: deviceId, type: type, text: text, at: at || nowIso(), by: by || me() });
  }

  /* ---------------- operations (all synchronous on S; callers then save()) ---------------- */
  function register(input, opt) {
    opt = opt || {};
    if (!CAT[input.category]) throw new Error('Pilih kategori perangkat.');
    // No barcode label: the next free AB-BTN-<kategori>-NNNN (the server picks it again when saving).
    var id = input.autoId ? nextId(input.category) : normId(input.id);
    if (!id) throw new Error('Barcode / ID aset wajib diisi.');
    if (codeProblem(id)) throw new Error(codeProblem(id));
    if (S.devices[id]) throw new Error('Barcode ' + id + ' sudah terdaftar.');
    var at = opt.at || nowIso(), by = opt.by || me();
    var d = {
      id: id, name: input.name, department: input.department || '', category: input.category, brand: input.brand || '', model: input.model || '', serial: input.serial || '',
      ip: input.ip || '', mac: input.mac ? normMac(input.mac) : '', location: input.location ? clone(input.location) : { building: '', floor: '', dept: '', room: '' }, pic: input.pic || '',
      status: input.status || 'aktif', purchaseDate: input.purchaseDate || '', warrantyEnd: input.warrantyEnd || '',
      notes: input.notes || '', spec: clone(input.spec || {}), invoices: files(input.invoices), createdAt: at, createdBy: by, updatedAt: at, updatedBy: by
    };
    S.devices[id] = d;
    log(id, 'register', placed(d) ? 'Perangkat diregistrasi di ' + locText(d.location) : 'Perangkat diregistrasi', at, by);
    return d;
  }
  function placed(d) { var l = d && d.location; return !!(l && (l.dept || l.room)); }
  /* set / change a device's placement (location + person in charge) */
  function place(id, input) {
    var d = S.devices[id]; if (!d) throw new Error('Perangkat tidak ditemukan.');
    if (d.status === 'disposed') throw new Error('Perangkat sudah dihapuskan.');
    var at = input.at || nowIso(), by = input.by || me();
    if (!placed(d)) {
      d.location = clone(input.location); if (input.pic) d.pic = input.pic;
      d.updatedAt = at; d.updatedBy = by;
      log(id, 'move', 'Ditempatkan di ' + locText(d.location), at, by);
      return null;
    }
    if (!sameLoc(d.location, input.location)) return move(id, { to: input.location, reason: 'Penempatan', pic: input.pic, at: at, by: by });
    if ((input.pic || '') !== (d.pic || '')) {
      log(id, 'edit', 'Penanggung jawab diubah: ' + (d.pic || '—') + ' → ' + (input.pic || '—'), at, by);
      d.pic = input.pic || ''; d.updatedAt = at; d.updatedBy = by;
    }
    return null;
  }
  function update(id, patch, opt) {
    opt = opt || {};
    var d = S.devices[id]; if (!d) throw new Error('Perangkat tidak ditemukan.');
    if (d.status === 'disposed') throw new Error('Perangkat sudah dihapuskan dan tidak dapat diubah.');
    var changed = [];
    ['name', 'department', 'category', 'brand', 'model', 'serial', 'ip', 'mac', 'pic', 'purchaseDate', 'warrantyEnd', 'notes'].forEach(function (k) {
      if (k in patch && (patch[k] || '') !== (d[k] || '')) { changed.push(FIELD_LABEL[k] || k); d[k] = k === 'mac' && patch[k] ? normMac(patch[k]) : patch[k]; }
    });
    if (patch.spec && JSON.stringify(patch.spec) !== JSON.stringify(d.spec || {})) { d.spec = clone(patch.spec); changed.push('Spesifikasi'); }
    if (patch.invoices) {
      var inv = files(patch.invoices), ids = function (l) { return (l || []).map(function (f) { return f.id; }).join(','); };
      if (ids(inv) !== ids(d.invoices)) { d.invoices = inv; changed.push('Invoice Pembelian'); }
    }
    if (patch.status && patch.status !== d.status) {
      if (['servis', 'disposal', 'disposed'].indexOf(d.status) !== -1) throw new Error('Status sedang dikunci oleh proses ' + STATUS[d.status].label + '.');
      log(id, 'status', 'Status diubah: ' + STATUS[d.status].label + ' → ' + STATUS[patch.status].label, opt.at, opt.by);
      d.status = patch.status;
    }
    d.updatedAt = opt.at || nowIso(); d.updatedBy = opt.by || me();
    if (changed.length) log(id, 'edit', 'Data diperbarui: ' + changed.join(', '), opt.at, opt.by);
    return d;
  }
  var FIELD_LABEL = { name: 'Nama', department: 'Departemen', category: 'Kategori', brand: 'Merek', model: 'Model', serial: 'Serial Number', ip: 'IP Address', mac: 'MAC Address', pic: 'Penanggung Jawab', purchaseDate: 'Tanggal Pembelian', warrantyEnd: 'Garansi', notes: 'Catatan' };

  function move(id, input, opt) {
    opt = opt || {};
    var d = S.devices[id]; if (!d) throw new Error('Perangkat tidak ditemukan.');
    if (d.status === 'disposed') throw new Error('Perangkat sudah dihapuskan; lokasi tidak dapat diubah.');
    if (sameLoc(d.location, input.to)) throw new Error('Lokasi baru sama dengan lokasi saat ini.');
    var at = input.at || opt.at || nowIso(), by = input.by || opt.by || me();
    S.seq.mv++;
    var m = { id: 'MV' + pad(S.seq.mv, 5), deviceId: id, from: clone(d.location), to: clone(input.to), at: at, by: by,
      reason: input.reason, note: input.note || '', picFrom: d.pic || '', picTo: input.pic != null && input.pic !== '' ? input.pic : (d.pic || '') };
    S.moves.push(m);
    d.location = clone(input.to);
    d.pic = m.picTo;
    d.updatedAt = at; d.updatedBy = by;
    log(id, 'move', 'Dipindahkan ke ' + locText(m.to) + ' — ' + m.reason, at, by);
    return m;
  }

  function addService(input, opt) {
    opt = opt || {};
    var d = S.devices[normId(input.deviceId)]; if (!d) throw new Error('Perangkat tidak ditemukan.');
    if (d.status === 'disposed') throw new Error('Perangkat sudah dihapuskan.');
    if (d.status === 'disposal') throw new Error('Perangkat sedang dalam proses disposal.');
    if (openService(d.id)) throw new Error('Perangkat ini masih memiliki laporan servis yang belum selesai.');
    var at = opt.at || nowIso(), by = opt.by || me();
    var year = new Date(input.reportDate || at).getFullYear();
    var due = dueDate(input.dueDate, input.reportDate);
    S.seq.srv++;
    var t = {
      id: 'SRV-' + year + '-' + pad(S.seq.srv), deviceId: d.id, deviceName: d.name, location: clone(d.location),
      type: input.type, problem: input.problem, reportDate: input.reportDate, dueDate: due, technician: input.technician,
      status: input.status || 'reported', notes: input.notes || '', completedAt: input.completedAt || '',
      prevStatus: d.status, createdAt: at, createdBy: by, docs: files(input.docs),
      log: [{ at: at, by: by, status: input.status || 'reported', note: 'Laporan dibuat' + (input.notes ? ': ' + input.notes : '') }]
    };
    needReport(t.docs, t.status, input.afterStatus || t.prevStatus);
    if (svcClosed(t.status) && !t.completedAt) t.completedAt = at.slice(0, 10);
    S.services.push(t);
    log(d.id, 'service', 'Laporan servis ' + t.id + ' dibuat (' + t.type + ')', at, by);
    if (svcClosed(t.status)) { finishService(t, input.afterStatus || t.prevStatus, at, by); }
    else d.status = 'servis';
    return t;
  }
  /* The device status after a closed service: the one chosen, or aktif when done and rusak when rejected. */
  function endStatus(status, afterStatus) {
    return ['aktif', 'cadangan', 'rusak'].indexOf(afterStatus) !== -1 ? afterStatus : (status === 'rejected' ? 'rusak' : 'aktif');
  }
  /* Closing as rejected, or with the device found broken (Rusak), needs a berita acara (photo or PDF). Same as the server. */
  function needReport(docs, status, afterStatus) {
    if (!svcClosed(status) || (docs && docs.length)) return;
    if (status === 'rejected') throw new Error('Unggah berita acara penolakan servis.');
    if (endStatus(status, afterStatus) === 'rusak') throw new Error('Perangkat dinyatakan rusak: unggah berita acara kerusakan (foto atau PDF).');
  }
  /* A closed service frees the device with the end status chosen (default: aktif when done, rusak when rejected). */
  function finishService(t, afterStatus, at, by) {
    var d = S.devices[t.deviceId];
    var next = endStatus(t.status, afterStatus);
    t.afterStatus = next;
    if (d && d.status !== 'disposed' && d.status !== 'disposal') {
      d.status = next;
      log(d.id, 'service', 'Servis ' + t.id + (t.status === 'rejected' ? ' ditolak' : ' selesai') + ' — status perangkat: ' + STATUS[next].label, at, by);
    }
  }
  function updateService(id, input, opt) {
    opt = opt || {};
    var t = S.services.filter(function (x) { return x.id === id; })[0];
    if (!t) throw new Error('Laporan servis tidak ditemukan.');
    if (svcClosed(t.status)) throw new Error('Laporan servis sudah ' + (t.status === 'done' ? 'selesai' : 'ditolak') + '.');
    var at = opt.at || nowIso(), by = opt.by || me();
    var added = files(input.docs);
    t.docs = (t.docs || []).concat(added).slice(0, 20);
    needReport(t.docs, input.status || t.status, input.afterStatus);
    if (input.technician) t.technician = input.technician;
    if (input.notes != null) t.notes = input.notes;
    // Vendor deadline: set, moved or cleared ('') while open; undefined/null leaves it (same as the server).
    var dueChange = '';
    if (input.dueDate != null) {
      var due = dueDate(input.dueDate, t.reportDate);
      if (due !== (t.dueDate || '')) { dueChange = due ? 'Tenggat vendor: ' + dueText(due) : 'Tenggat vendor dihapus'; t.dueDate = due; }
    }
    var prev = t.status;
    t.status = input.status || t.status;
    t.log.push({ at: at, by: by, status: t.status, note: (input.note || dueChange || (prev !== t.status ? 'Status: ' + SVC_STATUS[prev].label + ' → ' + SVC_STATUS[t.status].label : added.length ? 'Dokumen ditambahkan' : 'Catatan ditambahkan')) +
      (input.note && dueChange ? ' · ' + dueChange : '') + (added.length ? ' (lampiran: ' + added.map(function (f) { return f.name; }).join(', ') + ')' : '') });
    if (svcClosed(t.status)) {
      t.completedAt = input.completedAt || at.slice(0, 10);
      finishService(t, input.afterStatus, at, by);
    } else if (prev !== t.status) {
      log(t.deviceId, 'service', 'Servis ' + t.id + ': ' + SVC_STATUS[t.status].label, at, by);
    }
    return t;
  }
  /* Tenggat Vendor: an optional date, not before the report date (AssetLedger::dueDate). */
  function dueDate(v, reportDate) {
    v = String(v || '').trim(); if (!v) return '';
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) throw new Error('Tanggal tenggat vendor tidak valid.');
    if (reportDate && v < reportDate) throw new Error('Tenggat vendor tidak boleh sebelum tanggal laporan.');
    return v;
  }
  function dueText(ymd) { var m = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']; return parseInt(ymd.slice(8), 10) + ' ' + m[parseInt(ymd.slice(5, 7), 10) - 1] + ' ' + ymd.slice(0, 4); }
  /* Days from today to an open service's vendor deadline (negative = late), or null. */
  function dueIn(t) {
    if (!t || !t.dueDate || svcClosed(t.status)) return null;
    var now = new Date(), today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var p = t.dueDate.split('-'), due = new Date(+p[0], +p[1] - 1, +p[2]);
    return Math.round((due - today) / 86400000);
  }
  function openService(deviceId) {
    return S.services.filter(function (x) { return x.deviceId === deviceId && !svcClosed(x.status); })[0] || null;
  }

  /* Disposal in one step (no request / approval): the device is archived at once. An older request still open is
     finished by this same record. */
  function dispose(input, opt) {
    opt = opt || {};
    var d = S.devices[normId(input.deviceId)]; if (!d) throw new Error('Perangkat tidak ditemukan.');
    if (d.status === 'disposed') throw new Error('Perangkat sudah dihapuskan.');
    if (openService(d.id)) throw new Error('Selesaikan dulu laporan servis ' + openService(d.id).id + ' sebelum disposal.');
    var at = opt.at || nowIso(), by = opt.by || me();
    var r = openDisposal(d.id);
    if (!r) {
      S.seq.dsp++;
      r = { id: 'DSP-' + new Date(at).getFullYear() + '-' + pad(S.seq.dsp), deviceId: d.id, deviceName: d.name, category: d.category, lastLocation: clone(d.location),
        disposalDate: '', docs: [], requestedBy: by, requestedAt: at, prevStatus: d.status, approvedBy: '', approverRole: '', approvedAt: '', decisionNote: '' };
      S.disposals.push(r);
    }
    r.reason = input.reason; r.reasonDetail = input.reasonDetail || ''; r.condition = input.condition;
    r.status = 'disposed'; r.disposedAt = input.date || at.slice(0, 10); r.method = input.method || ''; r.reference = input.reference || '';
    if (input.docs && input.docs.length) r.docs = (r.docs || []).concat(files(input.docs));
    d.status = 'disposed'; d.disposedAt = r.disposedAt; d.disposalId = r.id; d.updatedAt = at; d.updatedBy = by;
    log(d.id, 'disposal', 'Perangkat dihapuskan (' + r.method + ') — ' + r.reason + (r.reference ? ' — BA ' + r.reference : '') + '. Riwayat diarsipkan.', at, by);
    return r;
  }
  /* Serah Terima: one berita acara for the devices; the receiver becomes their person in charge (AssetLedger::handover). */
  function handover(input, opt) {
    opt = opt || {};
    var at = opt.at || nowIso(), by = opt.by || me();
    var list = (input.deviceIds || []).map(function (id) { var d = S.devices[normId(id)]; if (!d) throw new Error('Perangkat tidak ditemukan.'); return d; });
    if (!list.length) throw new Error('Pilih minimal satu perangkat.');
    list.forEach(function (d) { if (['servis', 'disposal', 'disposed'].indexOf(d.status) !== -1) throw new Error(d.name + ' berstatus ' + STATUS[d.status].label + ', tidak dapat diserahterimakan.'); });
    S.seq.bast = (S.seq.bast || 0) + 1;
    var r = { id: 'BAST-' + at.slice(0, 4) + '-' + pad(S.seq.bast), date: input.date || at.slice(0, 10), fromName: input.fromName, fromRole: input.fromRole || '', toName: input.toName, toRole: input.toRole || '', toDept: input.toDept,
      condition: input.condition, note: input.note || '', devices: list.map(function (d) { return { id: d.id, name: d.name, brand: d.brand || '', model: d.model || '', serial: d.serial || '' }; }),
      signFrom: files(input.signFrom)[0] || null, signTo: files(input.signTo)[0] || null, createdAt: at, createdBy: by };
    S.handovers.push(r);
    list.forEach(function (d) {
      var loc = { building: '', floor: '', dept: input.toDept, room: '' };
      if (placed(d) && !sameLoc(d.location, loc)) move(d.id, { to: loc, reason: 'Serah terima', pic: input.toName, at: at, by: by });
      else place(d.id, { location: placed(d) ? d.location : loc, pic: input.toName, at: at, by: by });
      d.department = input.toDept; d.updatedAt = at; d.updatedBy = by;
      log(d.id, 'handover', 'Serah terima ' + r.id + ': ' + input.fromName + ' → ' + input.toName + ' (' + input.toDept + ')', at, by);
    });
    return r;
  }
  function getHandover(id) { return (S.handovers || []).filter(function (x) { return x.id === id; })[0] || null; }
  function signHandover(id, input, opt) {
    opt = opt || {};
    var r = getHandover(id); if (!r) throw new Error('Serah terima tidak ditemukan.');
    var field = input.role === 'from' ? 'signFrom' : 'signTo';
    if (r[field]) throw new Error('Tanda tangan pihak ini sudah ada.');
    r[field] = files(input.file)[0] || null;
    r.devices.forEach(function (d) { log(d.id, 'handover', 'Tanda tangan ' + (input.role === 'from' ? 'yang menyerahkan' : 'yang menerima') + ' ditambahkan pada ' + id, opt.at || nowIso(), opt.by || me()); });
    return r;
  }
  function getDisposal(id) { return S.disposals.filter(function (x) { return x.id === id; })[0] || null; }
  function openDisposal(deviceId) {
    return S.disposals.filter(function (x) { return x.deviceId === deviceId && (x.status === 'pending' || x.status === 'approved'); })[0] || null;
  }
  /* Cancels an older request still open (the only kind left: new disposals are done in one step). */
  function rejectDisposal(id, input, opt) {
    opt = opt || {};
    var r = getDisposal(id); if (!r) throw new Error('Pengajuan tidak ditemukan.');
    if (r.status !== 'pending' && r.status !== 'approved') throw new Error('Pengajuan ini sudah selesai.');
    var at = opt.at || nowIso();
    r.status = 'rejected'; r.approvedBy = input.approvedBy; r.approverRole = input.role || ''; r.approvedAt = at; r.decisionNote = input.note || '';
    var d = S.devices[r.deviceId];
    if (d && d.status === 'disposal') d.status = r.prevStatus && r.prevStatus !== 'disposal' ? r.prevStatus : 'aktif';
    log(r.deviceId, 'disposal', 'Pengajuan disposal ' + r.id + ' dibatalkan oleh ' + r.approvedBy + (r.decisionNote ? ' — ' + r.decisionNote : ''), at, opt.by);
    return r;
  }

  /* ---------------- queries ---------------- */
  function devices(opts) {
    opts = opts || {};
    var list = Object.keys(S.devices).map(function (k) { return S.devices[k]; });
    if (!opts.includeDisposed) list = list.filter(function (d) { return d.status !== 'disposed'; });
    return list;
  }
  function get(id) { return S.devices[normId(id)] || null; }
  function movesOf(id) { return S.moves.filter(function (m) { return m.deviceId === id; }).sort(byAtDesc); }
  function servicesOf(id) { return S.services.filter(function (m) { return m.deviceId === id; }).sort(function (a, b) { return String(b.createdAt).localeCompare(a.createdAt); }); }
  function disposalsOf(id) { return S.disposals.filter(function (m) { return m.deviceId === id; }).sort(function (a, b) { return String(b.requestedAt).localeCompare(a.requestedAt); }); }
  function activityOf(id) { return S.activity.filter(function (m) { return m.deviceId === id; }).sort(byAtDesc); }
  function byAtDesc(a, b) { return String(b.at).localeCompare(String(a.at)); }
  function previousLocation(id) { var m = movesOf(id)[0]; return m ? m.from : null; }
  /* Units / departments: the built-in list plus every name already typed (device department or location unit). */
  function deptNames() {
    var s = {}; DEPTS.forEach(function (x) { s[x] = 1; });
    Object.keys(S.devices).forEach(function (k) { var d = S.devices[k]; if (d.department) s[d.department] = 1; if (d.location && d.location.dept) s[d.location.dept] = 1; });
    return Object.keys(s).sort(function (a, b) { return a.localeCompare(b, 'id'); });
  }
  /* "Lainnya" devices: their own type (Remote Laser, Proyektor…), and the types already used. */
  function kindOf(d) { return d && d.category === 'other' && d.spec && d.spec.kind ? d.spec.kind : ''; }
  function kinds() {
    var s = {}; Object.keys(S.devices).forEach(function (k) { var v = kindOf(S.devices[k]); if (v) s[v] = 1; });
    return Object.keys(s).sort(function (a, b) { return a.localeCompare(b, 'id'); });
  }
  function search(q, opts) {
    q = String(q || '').trim().toLowerCase(); if (!q) return [];
    return devices(opts).filter(function (d) {
      return [d.id, d.name, d.department, kindOf(d), d.serial, d.ip, d.mac, d.brand, d.model, d.location && d.location.room, d.location && d.location.dept].join(' ').toLowerCase().indexOf(q) !== -1;
    });
  }
  function conflicts(field, value, exceptId) {
    var v = String(value || '').trim().toUpperCase(); if (!v) return null;
    if (field === 'mac') v = normMac(v);
    return devices().filter(function (d) { return d.id !== exceptId && String(d[field] || '').toUpperCase() === v; })[0] || null;
  }
  function nextId(catKey) {
    var c = CAT[catKey] || CAT.other, pre = 'AB-BTN-' + c.prefix + '-', max = 0;
    Object.keys(S.devices).forEach(function (k) { if (k.indexOf(pre) === 0) max = Math.max(max, parseInt(k.slice(pre.length), 10) || 0); });
    return pre + pad(max + 1);
  }
  function locText(l) {
    if (!l || !(l.dept || l.room)) return '—';
    var where = [l.building, l.floor].filter(Boolean).join(', ');
    return [l.dept, l.room].filter(Boolean).join(' · ') + (where ? ' (' + where + ')' : '');
  }
  /* A location as the pages show it: its department (an older room-only record shows its room). */
  function deptText(l) { return (l && (l.dept || l.room)) || '—'; }
  /* Where a device is: its placement, or its department from Registrasi when it was never placed. */
  function whereIs(d) { return placed(d) ? d.location : { building: '', floor: '', dept: (d && d.department) || '', room: '' }; }
  /* A service's location is recorded when it is reported; a report made before placement shows the device's department. */
  function svcLoc(t) { var l = t.location; return l && (l.dept || l.room) ? l : whereIs(S.devices[t.deviceId]); }


  /* ---------------- public ---------------- */
  // Each operation answers at once from the page's copy and is queued (without the design's {at, by} options:
  // the server sets the time and the account); save() sends the queue and takes the server's state. A refused
  // save reloads the server's state, so the page never keeps a change that was not stored.
  var ARITY = { register: 1, update: 2, place: 2, move: 2, addService: 1, updateService: 2, dispose: 1, rejectDisposal: 2, handover: 1, signHandover: 2 };
  function queued(op, fn) {
    return function () {
      var args = clone(Array.prototype.slice.call(arguments, 0, ARITY[op]));
      var out = fn.apply(null, Array.prototype.slice.call(arguments, 0, ARITY[op]));
      queue.push({ op: op, args: args });
      return out;
    };
  }
  var readyP = null;
  function ready() {
    if (readyP) return readyP;
    readyP = request('data').then(function (state) { S = state; return S; }, function (e) {
      S = empty();
      if (window.siteToast) window.siteToast('Data aset tidak dapat dimuat', e.message, 'error');
      return S;
    });
    return readyP;
  }
  function save() {
    if (!queue.length) return Promise.resolve(S);
    var sent = queue;
    queue = [];
    return request('perintah', { commands: sent }).then(function (state) { S = state; return S; }, function (e) {
      return request('data').then(function (state) { S = state; }, function () {}).then(function () { throw e; });
    });
  }

  window.AS = {
    CATEGORIES: CATEGORIES, CAT: CAT, BUILDINGS: BUILDINGS, FLOORS: FLOORS, DEPTS: DEPTS,
    STATUS: STATUS, SVC_STATUS: SVC_STATUS, svcClosed: svcClosed, endStatus: endStatus, dueIn: dueIn, deptNames: deptNames, kindOf: kindOf, kinds: kinds, upload: upload, fileUrl: fileUrl, SVC_TYPES: SVC_TYPES, DSP_STATUS: DSP_STATUS, DSP_REASONS: DSP_REASONS,
    DSP_CONDITIONS: DSP_CONDITIONS, DSP_METHODS: DSP_METHODS, MOVE_REASONS: MOVE_REASONS, TECHNICIANS: TECHNICIANS, VENDORS: VENDORS,
    ready: ready, save: save, importFile: importFile, state: function () { return S; },
    register: queued('register', register), update: queued('update', update), move: queued('move', move),
    addService: queued('addService', addService), updateService: queued('updateService', updateService), openService: openService,
    dispose: queued('dispose', dispose), rejectDisposal: queued('rejectDisposal', rejectDisposal),
    handover: queued('handover', handover), signHandover: queued('signHandover', signHandover), getHandover: getHandover,
    getDisposal: getDisposal, openDisposal: openDisposal,
    devices: devices, get: get, movesOf: movesOf, servicesOf: servicesOf, disposalsOf: disposalsOf, activityOf: activityOf,
    previousLocation: previousLocation, search: search, conflicts: conflicts, nextId: nextId, placed: placed, place: queued('place', place),
    normId: normId, codeProblem: codeProblem, normMac: normMac, locText: locText, deptText: deptText, whereIs: whereIs, svcLoc: svcLoc, sameLoc: sameLoc, me: me
  };
})();
