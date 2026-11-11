/*
 * IT Asset Management — shared UI helpers.
 * Uses the portal's existing components (pm-* modals, rs-* cards/tables/pills, siteToast).
 */
(function () {
  'use strict';
  var AS = window.AS;

  /* ---------------- formatting ---------------- */
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  function p2(n) { return String(n).padStart(2, '0'); }
  function toDate(v) { if (!v) return null; if (/^\d{4}-\d{2}-\d{2}$/.test(v)) { var a = v.split('-'); return new Date(+a[0], +a[1] - 1, +a[2]); } var d = new Date(v); return isNaN(d) ? null : d; }
  function fmtDate(v) { var d = toDate(v); return d ? p2(d.getDate()) + ' ' + MON[d.getMonth()] + ' ' + d.getFullYear() : '—'; }
  function fmtDateTime(v) { var d = toDate(v); return d ? fmtDate(v) + ', ' + p2(d.getHours()) + ':' + p2(d.getMinutes()) : '—'; }
  function ago(v) {
    var d = toDate(v); if (!d) return '';
    var s = (Date.now() - d.getTime()) / 1000;
    if (s < 60) return 'baru saja'; if (s < 3600) return Math.floor(s / 60) + ' menit lalu';
    if (s < 86400) return Math.floor(s / 3600) + ' jam lalu'; if (s < 86400 * 2) return 'kemarin';
    if (s < 86400 * 31) return Math.floor(s / 86400) + ' hari lalu'; return fmtDate(v);
  }
  function today() { var d = new Date(); return d.getFullYear() + '-' + p2(d.getMonth() + 1) + '-' + p2(d.getDate()); }
  function nowLocal() { var d = new Date(); return today() + 'T' + p2(d.getHours()) + ':' + p2(d.getMinutes()); }
  function ic(name) { return '<span data-i="' + esc(name) + '"></span>'; }
  function pill(def, extra) { return def ? '<span class="rs-pill as-pill--' + def.tone + (extra ? ' ' + extra : '') + '"><i></i>' + esc(def.label) + '</span>' : ''; }
  function statusPill(k) { return pill(AS.STATUS[k]); }
  function svcPill(k) { return pill(AS.SVC_STATUS[k]); }
  /* An open service's vendor deadline as a small badge: late / today (red), tomorrow or within 3 days (amber), later. */
  function dueText(t) {
    var n = AS.dueIn(t); if (n === null) return '';
    return n < 0 ? 'Lewat tenggat ' + (-n) + ' hari' : n === 0 ? 'Tenggat hari ini' : n === 1 ? 'Tenggat besok' : n <= 3 ? 'Tenggat ' + n + ' hari lagi' : 'Tenggat ' + fmtDate(t.dueDate);
  }
  function dueBadge(t) {
    var n = AS.dueIn(t); if (n === null) return '';
    return '<span class="rs-pill as-pill--' + (n <= 0 ? 'err' : n <= 3 ? 'warn' : 'muted') + ' as-due" title="Tenggat vendor ' + esc(fmtDate(t.dueDate)) + '"><i></i>' + esc(dueText(t)) + '</span>';
  }
  function dspPill(k) { return pill(AS.DSP_STATUS[k]); }
  function catOf(d) { return AS.CAT[d.category] || AS.CAT.other; }
  /* Category name; a "Lainnya" device shows its own type (e.g. "Lainnya: Remote Laser"). */
  function catName(d) { var k = AS.kindOf(d); return k ? 'Lainnya: ' + k : catOf(d).label; }
  function catChip(d) { var c = catOf(d), k = AS.kindOf(d); return '<span class="as-cat">' + ic(c.icon) + esc(k || c.short) + '</span>'; }
  function code(id) { return '<code class="as-code">' + esc(id) + '</code>'; }
  /* A location is shown as its department; Ruangan, Gedung and Lantai of older records stay in the data and the CSV. */
  function locHtml(l) {
    if (!l || !(l.dept || l.room)) return '<span class="rs-muted">Belum ditempatkan</span>';
    return '<span class="as-loc-cell"><b>' + esc(AS.deptText(l)) + '</b></span>';
  }
  function toast(t, d, v) { if (window.siteToast) window.siteToast(t, d, v); }
  function render(root) { if (window.rsRenderIcons && root) window.rsRenderIcons(root); }
  function qs(name) { return new URLSearchParams(location.search).get(name); }
  function trackUrl(id) { return 'lacak?id=' + encodeURIComponent(id); }

  /* ---------------- modal (portal pm-* classes) ---------------- */
  var openModals = [];
  function modal(o) {
    var prevFocus = document.activeElement;
    var ov = document.createElement('div');
    ov.className = 'pm-overlay as-modal';
    ov.innerHTML = '<div class="pm pm--' + (o.size || 'md') + '" role="dialog" aria-modal="true" aria-labelledby="asm-title">' +
      '<div class="pm-head pm-head--simple"><div><h2 class="pm-title" id="asm-title">' + esc(o.title) + '</h2>' + (o.subtitle ? '<p class="pm-help">' + o.subtitle + '</p>' : '') + '</div>' +
      '<button type="button" class="pm-x" data-x aria-label="Tutup">' + ic('x') + '</button></div>' +
      '<form class="pm-form" novalidate><div class="pm-body"><div class="pm-pad as-mbody">' + (o.body || '') + '</div></div>' +
      (o.foot === false ? '' : '<div class="pm-foot"><span class="as-foot-note">' + (o.note || '') + '</span><div class="pm-actions">' + (o.foot || '<button type="button" class="rs-btn rs-btn--ghost" data-x>Tutup</button>') + '</div></div>') +
      '</form></div>';
    document.body.appendChild(ov);
    document.documentElement.classList.add('rsab-lock');
    render(ov);
    var api = {
      el: ov, form: ov.querySelector('form'),
      close: function () {
        if (!ov.parentNode) return;
        if (o.onClose) o.onClose();
        ov.remove(); openModals.splice(openModals.indexOf(api), 1);
        if (!openModals.length) document.documentElement.classList.remove('rsab-lock');
        if (prevFocus && prevFocus.focus) try { prevFocus.focus(); } catch (e) {}
      },
      $: function (s) { return ov.querySelector(s); }, $$: function (s) { return Array.prototype.slice.call(ov.querySelectorAll(s)); }
    };
    openModals.push(api);
    ov.addEventListener('mousedown', function (e) { if (e.target === ov) api.close(); });
    ov.querySelectorAll('[data-x]').forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); api.close(); }); });
    api.form.addEventListener('submit', function (e) { e.preventDefault(); if (o.onSubmit) o.onSubmit(api); });
    if (o.onMount) o.onMount(api);
    setTimeout(function () { var f = ov.querySelector('[autofocus]') || ov.querySelector('.as-mbody input:not([readonly]):not([type=hidden]), .as-mbody select, .as-mbody textarea, .as-mbody button'); if (f) f.focus(); }, 30);
    return api;
  }
  document.addEventListener('keydown', function (e) {
    if (!openModals.length) return;
    var top = openModals[openModals.length - 1];
    if (e.key === 'Escape') { e.preventDefault(); top.close(); return; }
    if (e.key === 'Tab') { // keep focus inside the top dialog
      var f = Array.prototype.filter.call(top.el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'), function (x) { return !x.disabled && x.offsetParent !== null; });
      if (!f.length) return;
      if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    }
  });
  function confirmBox(o) {
    return new Promise(function (resolve) {
      var done = false;
      var m = modal({ title: o.title, size: 'sm', body: '<p class="pm-text" style="margin-top:0">' + o.text + '</p>',
        foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Batal</button><button type="submit" class="rs-btn ' + (o.danger ? 'rs-btn--danger-fill' : 'rs-btn--primary') + '">' + esc(o.ok || 'Lanjutkan') + '</button>',
        onSubmit: function (api) { done = true; api.close(); resolve(true); }, onClose: function () { if (!done) resolve(false); } });
      return m;
    });
  }

  /* ---------------- form helpers ---------------- */
  function field(o) {
    var id = o.id || ('f-' + o.name);
    var req = o.req ? ' <i class="req">*</i>' : '';
    var attrs = ' id="' + id + '" name="' + esc(o.name) + '"' + (o.req ? ' required' : '') + (o.readonly ? ' readonly' : '') + (o.disabled ? ' disabled' : '') +
      (o.ph ? ' placeholder="' + esc(o.ph) + '"' : '') + (o.inputmode ? ' inputmode="' + o.inputmode + '"' : '') + (o.max ? ' max="' + o.max + '"' : '') + (o.autocomplete ? ' autocomplete="' + o.autocomplete + '"' : ' autocomplete="off"');
    var cls = 'pm-input' + (o.mono ? ' as-mono font-mono' : '');
    var ctl;
    if (o.type === 'select') {
      ctl = '<select class="' + cls + '"' + attrs + '>' + (o.placeholder !== false ? '<option value="">' + esc(o.placeholder || 'Pilih…') + '</option>' : '') +
        (o.options || []).map(function (op) { var v = typeof op === 'object' ? op.value : op, l = typeof op === 'object' ? op.label : op; return '<option value="' + esc(v) + '"' + (String(v) === String(o.value || '') ? ' selected' : '') + '>' + esc(l) + '</option>'; }).join('') + '</select>';
    } else if (o.type === 'textarea') {
      ctl = '<textarea class="' + cls + '" rows="' + (o.rows || 3) + '"' + attrs + '>' + esc(o.value || '') + '</textarea>';
    } else {
      ctl = '<input class="' + cls + '" type="' + (o.type || 'text') + '"' + attrs + ' value="' + esc(o.value || '') + '"' + (o.list ? ' list="' + o.list + '"' : '') + '>';
    }
    return '<div class="pm-field' + (o.span ? ' as-span2' : '') + '" data-field="' + esc(o.name) + '"><label class="pm-label" for="' + id + '">' + esc(o.label) + req + '</label>' + ctl +
      (o.help ? '<p class="pm-help">' + o.help + '</p>' : '') + '<p class="pm-err" hidden>' + ic('circle-alert') + '<span class="as-errt"></span></p></div>';
  }
  function setErr(root, name, msg) {
    var f = root.querySelector('[data-field="' + name + '"]'); if (!f) return;
    var e = f.querySelector('.pm-err'), c = f.querySelector('input, select, textarea');
    if (!e) return;
    if (msg) { e.hidden = false; e.querySelector('.as-errt').textContent = msg; if (c) c.setAttribute('aria-invalid', 'true'); }
    else { e.hidden = true; if (c) c.removeAttribute('aria-invalid'); }
  }
  function clearErrs(root) { root.querySelectorAll('.pm-err').forEach(function (e) { e.hidden = true; }); root.querySelectorAll('[aria-invalid]').forEach(function (e) { e.removeAttribute('aria-invalid'); }); }
  function val(root, name) { var el = root.querySelector('[name="' + name + '"]'); return el ? String(el.value || '').trim() : ''; }
  function focusFirstErr(root) { var e = root.querySelector('[aria-invalid="true"]'); if (e) { e.focus(); e.scrollIntoView({ block: 'center', behavior: 'smooth' }); } }

  /* location: department / room, with room suggestions from existing data (Gedung / Lantai are no longer asked) */
  function roomList() {
    var set = {}; AS.devices({ includeDisposed: true }).forEach(function (d) { if (d.location && d.location.room) set[d.location.room] = 1; });
    return Object.keys(set).sort();
  }
  function locFields(prefix, loc, opt) {
    opt = opt || {}; loc = loc || {};
    var dl = 'dl-' + prefix + '-rooms';
    return '<div class="as-form-grid">' +
      field({ name: prefix + 'dept', label: 'Departemen / Unit', value: loc.dept, req: true, ph: 'Ketik atau pilih unit kerja', list: dl + '-depts' }) +
      field({ name: prefix + 'room', label: 'Ruangan', value: loc.room, req: true, ph: 'mis. Nurse Station Lt. 3', list: dl }) +
      '</div><datalist id="' + dl + '">' + roomList().map(function (r) { return '<option value="' + esc(r) + '">'; }).join('') + '</datalist>' +
      '<datalist id="' + dl + '-depts">' + AS.deptNames().map(function (r) { return '<option value="' + esc(r) + '">'; }).join('') + '</datalist>';
  }
  function readLoc(root, prefix) { return { building: '', floor: '', dept: val(root, prefix + 'dept'), room: val(root, prefix + 'room') }; }
  function checkLoc(root, prefix) {
    var ok = true;
    ['dept', 'room'].forEach(function (k) { if (!val(root, prefix + k)) { setErr(root, prefix + k, 'Wajib diisi.'); ok = false; } else setErr(root, prefix + k, ''); });
    return ok;
  }

  /* ---------------- Code 128 (set B) barcode as SVG ---------------- */
  var C128 = ['212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313', '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111', '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412', '211214', '211232', '2331112'];
  function code128(text, o) {
    o = o || {};
    text = String(text).replace(/[^\x20-\x7E]/g, '');
    var codes = [104], sum = 104;
    for (var i = 0; i < text.length; i++) { var v = text.charCodeAt(i) - 32; codes.push(v); sum += v * (i + 1); }
    codes.push(sum % 103); codes.push(106);
    var mod = o.module || 2, h = o.height || 60, quiet = 10 * mod, x = quiet, rects = '';
    codes.forEach(function (c) {
      var p = C128[c];
      for (var j = 0; j < p.length; j++) { var w = +p[j] * mod; if (j % 2 === 0) rects += '<rect x="' + x + '" y="0" width="' + w + '" height="' + h + '"/>'; x += w; }
    });
    var W = x + quiet;
    return '<svg class="as-barcode" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + W + ' ' + h + '" width="' + (o.width || W) + '" height="' + (o.width ? Math.round(h * o.width / W) : h) + '" preserveAspectRatio="none" role="img" aria-label="Barcode ' + esc(text) + '"><rect width="' + W + '" height="' + h + '" fill="#fff"/><g fill="#000">' + rects + '</g></svg>';
  }
  function labelHtml(d) {
    return '<div class="as-label"><div class="as-label__top"><b>RS AWAL BROS BOTANIA</b><span>Aset IT</span></div>' + code128(d.id, { height: 56, module: 2 }) +
      '<div class="as-label__id font-mono">' + esc(d.id) + '</div><div class="as-label__name">' + esc(d.name || '') + '</div></div>';
  }
  /* label as a PNG (used where printing is unavailable, e.g. the hosted viewer) */
  function labelPng(d) {
    var text = String(d.id).replace(/[^\x20-\x7E]/g, ''), codes = [104], sum = 104;
    for (var i = 0; i < text.length; i++) { var v = text.charCodeAt(i) - 32; codes.push(v); sum += v * (i + 1); }
    codes.push(sum % 103); codes.push(106);
    var mod = 3, units = codes.reduce(function (n, c) { return n + C128[c].split('').reduce(function (a, b) { return a + +b; }, 0); }, 0) + 20;
    var W = Math.max(640, units * mod + 60), H = 300, c = document.createElement('canvas'); c.width = W; c.height = H;
    var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, W, H); g.strokeStyle = '#1f2a2c'; g.lineWidth = 3; g.strokeRect(2, 2, W - 4, H - 4);
    g.fillStyle = '#000'; g.font = '700 20px sans-serif'; g.fillText('RS AWAL BROS BOTANIA', 24, 40); g.textAlign = 'right'; g.font = '18px sans-serif'; g.fillText('ASET IT', W - 24, 40);
    var x = (W - units * mod) / 2 + 10 * mod;
    codes.forEach(function (cd) { var p = C128[cd]; for (var j = 0; j < p.length; j++) { var w = +p[j] * mod; if (j % 2 === 0) g.fillRect(x, 58, w, 150); x += w; } });
    g.textAlign = 'center'; g.font = '700 28px monospace'; g.fillText(text, W / 2, 246); g.font = '18px sans-serif'; g.fillStyle = '#333'; g.fillText(String(d.name || '').slice(0, 60), W / 2, 278);
    return new Promise(function (res) { c.toBlob(res, 'image/png'); });
  }
  function printLabel(d) {
    if (window.rsHosted && window.rsSaveFile) {
      toast('Label disiapkan sebagai PNG', 'Cetak langsung tidak tersedia di tampilan ini — simpan gambar label lalu cetak.', 'info');
      return labelPng(d).then(function (b) { window.rsSaveFile('Label-' + String(d.id).replace(/[^\w\-]+/g, '_') + '.png', b); });
    }
    var box = document.getElementById('as-print') || document.body.appendChild(Object.assign(document.createElement('div'), { id: 'as-print' }));
    box.innerHTML = labelHtml(d);
    document.body.classList.add('as-printing');
    var cleanup = function () { document.body.classList.remove('as-printing'); window.removeEventListener('afterprint', cleanup); };
    window.addEventListener('afterprint', cleanup);
    setTimeout(function () { window.print(); setTimeout(cleanup, 500); }, 50);
  }

  /* ---------------- barcode scanner (camera + USB scanner / manual) ---------------- */
  // The barcode readers are served by the portal itself (vendor/, next to this script), so phones without
  // BarcodeDetector (iPhone/iPad, Firefox) scan without internet and the site's CSP allows them:
  // zxing-wasm 2.2.4 (MIT; zxing-cpp, Apache-2.0) first, ZXing-JS 0.21.3 (MIT) where WebAssembly is not allowed.
  var VENDOR = (function () {
    var src = document.currentScript && document.currentScript.src;
    return (src ? src.replace(/[^/]*$/, '') : '') + 'vendor/';
  })();
  var scripts = {};
  function loadScript(file, global) {
    if (window[global]) return Promise.resolve(window[global]);
    if (scripts[file]) return scripts[file];
    scripts[file] = new Promise(function (res, rej) {
      var s = document.createElement('script'); s.src = VENDOR + file; s.async = true;
      s.onload = function () { window[global] ? res(window[global]) : rej(new Error(global)); };
      s.onerror = function () { scripts[file] = null; rej(new Error(global)); };
      document.head.appendChild(s);
    });
    return scripts[file];
  }
  function loadZxing() { return loadScript('zxing-library-0.21.3.min.js', 'ZXing'); }
  /* zxing-cpp as WebAssembly: resolves to ZXingWASM once its .wasm is compiled (fails where CSP or the browser forbid it). */
  function loadZxingWasm() {
    return loadScript('zxing-wasm-reader-2.2.4.js', 'ZXingWASM').then(function (W) {
      return W.prepareZXingModule({
        overrides: { locateFile: function (path, prefix) { return /\.wasm$/.test(path) ? VENDOR + 'zxing_reader-2.2.4.wasm' : prefix + path; } },
        fireImmediately: true
      }).then(function () { return W; });
    });
  }
  /* true when the browser alters canvas pixels (fingerprint protection, e.g. Brave Shields): every read would fail. */
  function canvasTampered() {
    var c = document.createElement('canvas'); c.width = c.height = 16;
    var g = c.getContext('2d', { willReadFrequently: true }), x, y;
    for (y = 0; y < 16; y++) for (x = 0; x < 16; x++) { g.fillStyle = (x + y) % 2 ? '#000' : '#fff'; g.fillRect(x, y, 1, 1); }
    var p = g.getImageData(0, 0, 16, 16).data;
    for (y = 0; y < 16; y++) for (x = 0; x < 16; x++) { var v = (x + y) % 2 ? 0 : 255, i = (y * 16 + x) * 4; if (p[i] !== v || p[i + 1] !== v || p[i + 2] !== v) return true; }
    return false;
  }
  function beep() {
    try { if (navigator.vibrate) navigator.vibrate(60);
      var C = window.AudioContext || window.webkitAudioContext; if (!C) return; var c = new C(), o = c.createOscillator(), g = c.createGain();
      o.frequency.value = 1200; g.gain.value = .06; o.connect(g); g.connect(c.destination); o.start(); setTimeout(function () { o.stop(); c.close(); }, 110);
    } catch (e) {}
  }
  /*
   * Barcode reader for the camera and for photos. Engines, best first: BarcodeDetector where the browser has it
   * with QR support (Chrome/Edge on Android, macOS); zxing-cpp (WebAssembly), far better than ZXing-JS on small,
   * soft phone pictures (iPhone/iPad, desktop); ZXing-JS where WebAssembly is not allowed. The two ZXing engines
   * read pictures copied to a canvas: the whole picture, then its middle (inside the on-screen frame) enlarged, then
   * the middle enlarged more and sharpened. Resolves to { engine, tampered, read(source, w, h) → Promise<text|null> };
   * tampered: the browser alters canvas pixels (fingerprint protection, e.g. Brave Shields), so reads would fail.
   */
  var FORMATS = ['qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'itf', 'data_matrix'];
  var WASM_FORMATS = ['QRCode', 'Code128', 'Code39', 'EAN-13', 'EAN-8', 'UPC-A', 'ITF', 'DataMatrix'];
  var decoderP = null;
  /* Canvas regions of a picture, in the order they are tried; decodeCanvas(canvas, ctx) → text|null|Promise. */
  function regionReader(decodeCanvas) {
    var canvas = document.createElement('canvas'), ctx = canvas.getContext('2d', { willReadFrequently: true });
    // Sharpening (3×3 on brightness) restores the module edges of an enlarged, slightly soft label.
    function sharpen() {
      var W = canvas.width, H = canvas.height, d = ctx.getImageData(0, 0, W, H), p = d.data, L = new Float32Array(W * H), i, x, y, v;
      for (i = 0; i < W * H; i++) L[i] = p[i * 4] * 0.299 + p[i * 4 + 1] * 0.587 + p[i * 4 + 2] * 0.114;
      for (y = 1; y < H - 1; y++) for (x = 1; x < W - 1; x++) {
        i = y * W + x; v = L[i] * 7 - 1.5 * (L[i - 1] + L[i + 1] + L[i - W] + L[i + W]);
        p[i * 4] = p[i * 4 + 1] = p[i * 4 + 2] = v < 0 ? 0 : v > 255 ? 255 : v;
      }
      ctx.putImageData(d, 0, 0);
    }
    function part(src, sx, sy, sw, sh, scale, sharp) {
      canvas.width = Math.max(1, Math.round(sw * scale)); canvas.height = Math.max(1, Math.round(sh * scale));
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(src, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
      if (sharp) sharpen();
      return Promise.resolve(decodeCanvas(canvas, ctx));
    }
    return function read(src, w, h) {
      if (!w || !h) return Promise.resolve(null);
      var side = Math.min(w, h) * 0.7, small = Math.min(w, h) * 0.4;
      // The middle is read at the camera's full resolution (never shrunk below 1×), enlarged when it is small.
      var tries = [
        function () { return part(src, 0, 0, w, h, Math.min(1, 1600 / Math.max(w, h))); },
        function () { return part(src, (w - side) / 2, (h - side) / 2, side, side, Math.max(1, Math.min(2.5, 1000 / side))); },
        function () { return part(src, (w - small) / 2, (h - small) / 2, small, small, Math.max(1, Math.min(3, 1000 / small)), true); }
      ];
      var next = function (i) { return i >= tries.length ? null : tries[i]().then(function (t) { return t || next(i + 1); }); };
      return next(0);
    };
  }
  function wasmEngine() {
    return loadZxingWasm().then(function (W) {
      var opts = { tryHarder: true, formats: WASM_FORMATS, maxNumberOfSymbols: 1 };
      return { engine: 'zxing-cpp', read: regionReader(function (canvas, ctx) {
        return W.readBarcodes(ctx.getImageData(0, 0, canvas.width, canvas.height), opts).then(function (r) {
          var hit = (r || []).filter(function (x) { return x.isValid && x.text; })[0];
          return hit ? hit.text : null;
        });
      }) };
    });
  }
  function jsEngine() {
    return loadZxing().then(function (Z) {
      var B = Z.BarcodeFormat, hints = new Map();
      hints.set(Z.DecodeHintType.POSSIBLE_FORMATS, [B.QR_CODE, B.CODE_128, B.CODE_39, B.EAN_13, B.EAN_8, B.UPC_A, B.ITF, B.DATA_MATRIX]);
      hints.set(Z.DecodeHintType.TRY_HARDER, true);
      var reader = new Z.MultiFormatReader(); reader.setHints(hints);
      return { engine: 'ZXing-JS', read: regionReader(function (canvas) {
        var lum = new Z.HTMLCanvasElementLuminanceSource(canvas), tries = [Z.HybridBinarizer, Z.GlobalHistogramBinarizer];
        for (var i = 0; i < tries.length; i++) {
          try { return reader.decodeWithState(new Z.BinaryBitmap(new tries[i](lum))).getText(); } catch (e) { /* not in this picture */ }
        }
        return null;
      }) };
    });
  }
  function makeDecoder() {
    if (decoderP) return decoderP;
    var native = window.BarcodeDetector && window.BarcodeDetector.getSupportedFormats
      ? window.BarcodeDetector.getSupportedFormats().then(function (have) {
        return have.indexOf('qr_code') === -1 ? null : new window.BarcodeDetector({ formats: FORMATS.filter(function (f) { return have.indexOf(f) !== -1; }) });
      }, function () { return null; })
      : Promise.resolve(null);
    decoderP = native.then(function (det) {
      if (det) return { engine: 'BarcodeDetector', tampered: false, read: function (src) { return det.detect(src).then(function (r) { return r && r.length ? r[0].rawValue : null; }); } };
      var tampered = canvasTampered();
      return wasmEngine().catch(function (err) {
        return jsEngine().then(function (e) { e.fallback = 'zxing-wasm: ' + ((err && err.message) || err); return e; });
      }).then(function (e) { e.tampered = tampered; return e; });
    });
    decoderP.catch(function () { decoderP = null; });
    return decoderP;
  }
  /* A photo (File) → the code in it, or null: the photo is shrunk to at most 1600 px for ZXing. */
  function readPhoto(file) {
    return makeDecoder().then(function (dec) {
      return new Promise(function (res, rej) {
        var url = URL.createObjectURL(file), img = new Image();
        img.onload = function () { res(img); };
        img.onerror = function () { URL.revokeObjectURL(url); rej(new Error('Foto tidak dapat dibuka.')); };
        img.src = url;
      }).then(function (img) {
        var k = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight));
        var c = document.createElement('canvas'); c.width = Math.round(img.naturalWidth * k); c.height = Math.round(img.naturalHeight * k);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(img.src);
        readPhoto.last = { canvas: c, engine: dec.engine, natural: img.naturalWidth + '×' + img.naturalHeight };
        return dec.read(c, c.width, c.height);
      });
    });
  }

  /*
   * Diagnosis for the IT team (POST <body data-aset-diag>, stored on the portal server only): what the scanner saw
   * when it read nothing — reader, camera size and settings, frames tried, errors — and the middle of the picture.
   */
  function sendDiag(info, src, w, h) {
    var url = document.body.getAttribute('data-aset-diag'), frame = '';
    if (!url) return;
    try {
      if (src && w && h) {
        var side = Math.min(w, h) * 0.6, k = Math.min(1, 1200 / side), c = document.createElement('canvas');
        c.width = c.height = Math.round(side * k);
        c.getContext('2d').drawImage(src, (w - side) / 2, (h - side) / 2, side, side, 0, 0, c.width, c.height);
        frame = c.toDataURL('image/jpeg', 0.85);
      }
    } catch (e) { info.frameError = String((e && e.message) || e); }
    info.page = location.pathname; info.secure = window.isSecureContext; info.dpr = window.devicePixelRatio;
    var meta = document.querySelector('meta[name="csrf-token"]');
    fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': meta ? meta.content : '' },
      body: JSON.stringify({ info: info, frame: frame }) }).catch(function () {});
  }

  /* scan({ title, hint, onResult(code) -> true to close / string to show as error }) */
  function scan(o) {
    o = o || {};
    var stream = null, timer = null, stopped = false, mode = 'manual';
    var m = modal({
      title: o.title || 'Scan Barcode', size: 'md', subtitle: o.subtitle || '',
      body:
        '<div class="pm-tabs as-scan-tabs" role="tablist">' +
        '<button type="button" role="tab" aria-selected="true" data-mode="manual">' + ic('keyboard') + ' Scanner USB / Ketik</button>' +
        '<button type="button" role="tab" aria-selected="false" data-mode="camera">' + ic('camera') + ' Kamera</button></div>' +
        '<div data-panel="manual" class="as-scan-manual">' +
        '<div class="as-scan-hint">' + ic('barcode') + '<div><b>Siap menerima scan</b><p class="pm-help">Arahkan scanner barcode USB/Bluetooth ke label — kode akan terisi otomatis. Bisa juga diketik manual lalu tekan Enter.</p></div></div>' +
        field({ name: 'code', label: 'Barcode / ID Aset', mono: true, ph: 'AB-BTN-PC-0001' }) + '</div>' +
        '<div data-panel="camera" hidden><div class="as-scan-view"><video playsinline muted></video><div class="as-scan-frame" aria-hidden="true"><i></i></div>' +
        '<div class="as-scan-msg" role="status">Menyiapkan kamera…</div></div>' +
        // Fallback: the phone's own camera app (sharp autofocus) takes a photo, which is read here.
        '<div class="as-scan-photo"><label class="rs-btn rs-btn--ghost">' + ic('image') + ' Foto label<input type="file" accept="image/*" capture="environment" data-photo hidden></label></div>' +
        '<p class="as-scan-info" data-info></p></div>' +
        (o.extra || ''),
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Batal</button><button type="submit" class="rs-btn rs-btn--primary" data-go>' + ic('search') + ' Gunakan Kode</button>',
      onClose: stop,
      onSubmit: function () { submit(val(m.el, 'code')); }
    });
    var input = m.$('[name="code"]'), msg = m.$('.as-scan-msg'), video = m.$('video'), info = m.$('[data-info]');
    input.focus();
    m.$('[data-photo]').addEventListener('change', function (e) {
      var file = e.target.files && e.target.files[0]; e.target.value = '';
      if (!file) return;
      msg.classList.remove('is-err'); msg.textContent = 'Membaca foto…';
      readPhoto(file).then(function (text) {
        if (!text) {
          var p = readPhoto.last || {};
          if (p.canvas) sendDiag({ kind: 'foto', engine: p.engine, photo: p.natural, read: p.canvas.width + '×' + p.canvas.height }, p.canvas, p.canvas.width, p.canvas.height);
          fail('Barcode tidak terbaca di foto. Foto lebih dekat, tegak lurus, dan tidak buram.'); return;
        }
        var bad = AS.codeProblem(text);
        if (bad) { fail(bad); return; }
        stopped = false; found(text);
      }, function (err) { fail(err.message || 'Foto tidak dapat dibaca.'); });
    });
    function submit(codeVal) {
      var bad = AS.codeProblem(codeVal);
      codeVal = AS.normId(codeVal);
      if (bad) { if (mode !== 'manual') setMode('manual'); input.value = codeVal; setErr(m.el, 'code', bad); input.select(); return; }
      var r = o.onResult ? o.onResult(codeVal) : true;
      if (r === true || r === undefined) { m.close(); }
      else if (typeof r === 'string') { setMode('manual'); input.value = codeVal; setErr(m.el, 'code', r); input.select(); }
    }
    function setMode(k) {
      mode = k;
      m.$$('[data-mode]').forEach(function (b) { b.setAttribute('aria-selected', b.getAttribute('data-mode') === k ? 'true' : 'false'); });
      m.$('[data-panel="manual"]').hidden = k !== 'manual'; m.$('[data-panel="camera"]').hidden = k !== 'camera';
      m.$('[data-go]').hidden = k !== 'manual';
      if (k === 'camera') start(); else { stop(); stopped = false; input.focus(); }
    }
    m.$$('[data-mode]').forEach(function (b) { b.addEventListener('click', function () { setMode(b.getAttribute('data-mode')); }); });
    input.addEventListener('input', function () { setErr(m.el, 'code', ''); });
    // A read that is not a whole asset code (cut-off or foreign barcode) is shown and scanning goes on.
    function found(codeVal) {
      if (stopped) return;
      var bad = AS.codeProblem(codeVal);
      if (bad) { msg.classList.add('is-err'); msg.textContent = bad; return; }
      beep(); stop(); msg.classList.remove('is-err'); msg.textContent = 'Terbaca: ' + AS.normId(codeVal); submit(codeVal);
    }
    function fail(t) { msg.textContent = t; msg.classList.add('is-err'); }
    function start() {
      stopped = false; msg.classList.remove('is-err'); msg.textContent = 'Menyiapkan kamera…';
      // Browsers open the camera only on https:// (or localhost): from a phone, http://<IP komputer> has no camera.
      if (!window.isSecureContext && location.protocol === 'http:') {
        var secure = 'https://' + location.host + location.pathname + location.search;
        msg.classList.add('is-err');
        msg.innerHTML = 'Kamera live hanya bisa lewat HTTPS. <a href="' + esc(secure) + '">Buka halaman ini lewat HTTPS</a>, atau pakai tombol Foto label di bawah.';
        return;
      }
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { fail('Kamera tidak tersedia di perangkat/browser ini. Gunakan scanner USB atau ketik kode.'); return; }
      // As many pixels as the camera gives (4K on recent phones): the label QR has 45×45 small modules.
      navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 3840 }, height: { ideal: 2160 } }, audio: false }).then(function (s) {
        if (stopped || mode !== 'camera') { s.getTracks().forEach(function (t) { t.stop(); }); return; }
        stream = s; video.srcObject = s; video.play().catch(function () {});
        // Keep refocusing where the camera allows it (most phones): labels are read close up.
        var track = s.getVideoTracks()[0], caps = track && track.getCapabilities ? track.getCapabilities() : {};
        if (caps.focusMode && caps.focusMode.indexOf('continuous') !== -1) track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }).catch(function () {});
        msg.textContent = 'Arahkan kamera ke barcode…';
        makeDecoder().then(function (dec) {
          if (dec.tampered) {
            fail('Browser ini mengubah gambar kamera (proteksi fingerprint, mis. Brave Shields), jadi barcode tidak bisa dibaca. Matikan Shields untuk situs ini atau pakai Chrome/Edge.');
            info.textContent = 'Pembaca: ' + dec.engine + ' · data gambar diubah browser';
            sendDiag({ kind: 'kamera', engine: dec.engine, tampered: true });
            return;
          }
          // Without the phone's own detector, zoom in 2× where the camera allows it: the label can stay at a distance
          // the lens can focus at (an iPhone cannot focus closer than about 15–20 cm) and still fill the frame.
          var zoom = '';
          if (dec.engine !== 'BarcodeDetector' && caps.zoom && caps.zoom.max >= 1.5) {
            var z = Math.min(2, caps.zoom.max);
            track.applyConstraints({ advanced: [{ zoom: z }] }).then(function () { zoom = ' · zoom ' + z + '×'; }, function () {});
          }
          // Nothing read after 5 s and again after 15 s: a diagnosis report goes to the portal (see sendDiag).
          var st = { frames: 0, ms: 0, errors: 0, lastError: '', sent: 0, since: Date.now() };
          var report = function () {
            var settings = track && track.getSettings ? track.getSettings() : {};
            sendDiag({ kind: 'kamera', engine: dec.engine, fallback: dec.fallback || '', tampered: dec.tampered, video: video.videoWidth + '×' + video.videoHeight,
              zoom: zoom, seconds: Math.round((Date.now() - st.since) / 1000), frames: st.frames, avgMs: st.frames ? Math.round(st.ms / st.frames) : 0,
              errors: st.errors, lastError: st.lastError, settings: settings,
              caps: { zoom: caps.zoom || null, focusMode: caps.focusMode || null, width: caps.width || null, height: caps.height || null } },
            video, video.videoWidth, video.videoHeight);
          };
          var loop = function () {
            if (stopped) return;
            if (video.readyState < 2 || !video.videoWidth) { timer = setTimeout(loop, 150); return; }
            info.textContent = 'Pembaca: ' + dec.engine + ' · kamera ' + video.videoWidth + '×' + video.videoHeight + zoom;
            if (st.sent < 2 && Date.now() - st.since > (st.sent ? 15000 : 5000)) { st.sent++; report(); }
            var t0 = Date.now();
            dec.read(video, video.videoWidth, video.videoHeight).then(function (text) {
              st.frames++; st.ms += Date.now() - t0;
              if (text) found(text); if (!stopped) timer = setTimeout(loop, 100);
            }, function (err) {
              st.errors++; st.lastError = String((err && err.message) || err).slice(0, 300);
              if (!stopped) timer = setTimeout(loop, 300);
            });
          };
          loop();
        }).catch(function (err) {
          sendDiag({ kind: 'kamera', loadError: String((err && err.message) || err) });
          fail('Pemindai kamera gagal dimuat. Muat ulang halaman, atau gunakan scanner USB / ketik kode.');
        });
      }).catch(function (e) {
        sendDiag({ kind: 'kamera', cameraError: (e && e.name) + ': ' + (e && e.message) });
        fail(e && e.name === 'NotAllowedError' ? 'Akses kamera ditolak. Izinkan kamera di pengaturan browser, atau pakai tombol Foto label di bawah.' : 'Kamera tidak dapat dibuka. Pakai tombol Foto label di bawah, scanner USB, atau ketik kode.');
      });
    }
    function stop() {
      stopped = true; clearTimeout(timer);
      if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
      if (video) video.srcObject = null;
    }
    if (o.camera) setMode('camera');
    return m;
  }

  /* ---------------- device lookup (scan / type / pick from suggestions) ---------------- */
  /* lookup(container, { onPick(device), filter(device)->string|null (reason not allowed), placeholder }) */
  function lookup(box, o) {
    o = o || {};
    var lid = 'lk' + Math.random().toString(36).slice(2, 7);
    box.innerHTML = o.gf
      ? '<div class="as-lookup as-lookup--gf"><div class="as-lookup__row">' +
        '<input class="gf-input gf-mono as-lookup__input" type="text" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="' + lid + '" autocomplete="off" placeholder="' + esc(o.placeholder || 'Scan atau ketik barcode') + '" aria-label="' + esc(o.label || 'Barcode / ID Aset') + '">' +
        '<button type="button" class="gf-btn gf-btn--outline" data-scan>' + ic('camera') + '<span>Scan Kamera</span></button>' +
        '<button type="button" class="gf-btn gf-btn--primary" data-go>' + ic('search') + '<span>' + esc(o.goLabel || 'Cari') + '</span></button></div>' +
        '<div class="as-suggest" id="' + lid + '" role="listbox" hidden></div><p class="gf-err as-lookup__err" hidden>' + ic('circle-alert') + '<span class="as-errt"></span></p>' +
        '<div class="as-lookup__reg" hidden><a class="gf-btn gf-btn--primary" data-register href="registrasi">' + ic('circle-plus') + '<span>Daftarkan perangkat ini</span></a></div>' +
        '<p class="gf-q__help as-lookup__hint">' + ic('keyboard') + ' Scanner barcode USB/Bluetooth bisa langsung dipakai — kode terisi otomatis. Bisa juga cari dengan nama, serial, atau IP.</p></div>'
      : '<div class="as-lookup"><div class="as-lookup__row"><div class="as-search as-lookup__input">' + ic('search') +
        '<input class="pm-input as-mono-ph" type="text" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="' + lid + '" autocomplete="off" placeholder="' + esc(o.placeholder || 'Scan barcode atau ketik ID / nama / serial') + '" aria-label="Cari perangkat"></div>' +
        '<button type="button" class="rs-btn rs-btn--outline" data-scan>' + ic('scan-line') + '<span>Scan</span></button></div>' +
        '<div class="as-suggest" id="' + lid + '" role="listbox" hidden></div><p class="pm-err" hidden>' + ic('circle-alert') + '<span class="as-errt"></span></p></div>';
    render(box);
    var input = box.querySelector('input'), list = box.querySelector('.as-suggest'), err = box.querySelector('.pm-err, .as-lookup__err'), items = [], act = -1;
    var reg = box.querySelector('.as-lookup__reg');
    // regCode: a well-formed code that is not registered yet → "Daftarkan perangkat ini" (Registrasi with the code
    // filled in and locked, and a way back to this form).
    function showErr(t, regCode) {
      err.hidden = !t; err.querySelector('.as-errt').textContent = t || '';
      if (!reg) return;
      reg.hidden = !regCode;
      if (regCode) {
        var page = location.pathname.split('/').filter(Boolean).pop() || '';
        reg.querySelector('[data-register]').href = 'registrasi?code=' + encodeURIComponent(regCode) + (/^(servis|disposal)$/.test(page) ? '&dari=' + page : '');
      }
    }
    function pick(d) {
      var why = o.filter ? o.filter(d) : null;
      if (why) { showErr(why); return false; }
      showErr(''); close(); input.value = d.id; if (o.onPick) o.onPick(d); return true;
    }
    function close() { list.hidden = true; input.setAttribute('aria-expanded', 'false'); act = -1; }
    // A scanned label link (…/history/ITEM-00049895) becomes its asset code in the box. Only on Enter/Cari:
    // a USB scanner types the link letter by letter, so tidying while it types would cut the link.
    function tidy() { if (/\//.test(input.value)) input.value = AS.normId(input.value); }
    function draw() {
      var q = input.value.trim();
      if (/\//.test(q)) { close(); return; }
      items = q ? AS.search(q, { includeDisposed: !!o.includeDisposed }).slice(0, 8) : [];
      if (!q) { close(); return; }
      list.innerHTML = items.length ? items.map(function (d, i) {
        return '<button type="button" role="option" id="' + lid + '-' + i + '" aria-selected="' + (i === act) + '" data-idx="' + i + '" class="as-suggest__item"><span class="as-tile as-tile--sm">' + ic(catOf(d).icon) + '</span>' +
          '<span class="as-suggest__text"><b>' + esc(d.name) + '</b><small>' + esc(d.id) + ' · ' + esc((d.location && d.location.dept) || d.department || '—') + '</small></span>' + statusPill(d.status) + '</button>';
      }).join('') : '<div class="as-suggest__none">Tidak ada perangkat yang cocok. Tekan Enter untuk mencari barcode persis.</div>';
      render(list);
      list.hidden = false; input.setAttribute('aria-expanded', 'true');
    }
    function go() {
      tidy();
      if (act >= 0 && items[act]) return pick(items[act]);
      var exact = AS.get(input.value);
      if (exact && (o.includeDisposed || exact.status !== 'disposed')) return pick(exact);
      if (exact && exact.status === 'disposed') return showErr('Perangkat ' + exact.id + ' sudah dihapuskan (disposed).');
      if (!items.length && input.value.trim()) items = AS.search(input.value, { includeDisposed: !!o.includeDisposed });
      if (items.length === 1) return pick(items[0]);
      if (items.length > 1) { draw(); return showErr('Ada ' + items.length + ' perangkat yang cocok — pilih salah satu dari daftar.'); }
      var bad = AS.codeProblem(input.value);
      if (bad) return showErr(bad);
      var c = AS.normId(input.value);
      showErr('Barcode "' + c + '" belum terdaftar.' + (o.notFound || ''), c);
      if (o.onNotFound) o.onNotFound(c);
    }
    var goBtn = box.querySelector('[data-go]'); if (goBtn) goBtn.addEventListener('click', go);
    input.addEventListener('input', function () { act = -1; showErr(''); draw(); if (o.onInput) o.onInput(); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        if (list.hidden) draw(); if (!items.length) return;
        e.preventDefault(); act = (act + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length;
        list.querySelectorAll('[role=option]').forEach(function (b, i) { b.setAttribute('aria-selected', i === act ? 'true' : 'false'); });
        input.setAttribute('aria-activedescendant', lid + '-' + act);
      } else if (e.key === 'Enter') {
        e.preventDefault(); e.stopPropagation(); go();
      } else if (e.key === 'Escape' && !list.hidden) { e.stopPropagation(); close(); }
    });
    list.addEventListener('mousedown', function (e) { e.preventDefault(); });
    list.addEventListener('click', function (e) { var b = e.target.closest('[data-idx]'); if (b) pick(items[+b.getAttribute('data-idx')]); });
    input.addEventListener('blur', function () { setTimeout(close, 120); });
    box.querySelector('[data-scan]').addEventListener('click', function () {
      scan({ title: o.scanTitle || 'Scan Barcode Perangkat', camera: true, onResult: function (c) {
        var d = AS.get(c);
        if (d && d.status === 'disposed' && !o.includeDisposed) return 'Perangkat ' + c + ' sudah dihapuskan (disposed).';
        if (!d) { input.value = c; go(); return true; } // not registered: the form shows "Daftarkan perangkat ini"
        var why = o.filter ? o.filter(d) : null; if (why) return why;
        pick(d); return true;
      } });
    });
    return { input: input, set: function (d) { if (d) pick(d); }, error: showErr, find: function (v) { input.value = v || ''; go(); } };
  }

  /* device summary block used inside modals */
  function deviceCard(d) {
    return '<div class="as-devcard"><span class="as-tile">' + ic(catOf(d).icon) + '</span><div class="as-devcard__text"><b>' + esc(d.name) + '</b>' +
      '<small>' + code(d.id) + ' · ' + esc(catName(d)) + (d.brand ? ' · ' + esc(d.brand + ' ' + d.model) : '') + '</small>' +
      '<small>' + ic('map-pin') + ' ' + esc(AS.deptText(AS.whereIs(d))) + '</small></div>' + statusPill(d.status) + '</div>';
  }

  /* ---------------- files (disposal documents/photos) ---------------- */
  /*
   * Documents stored on the portal (berita acara servis, invoice pembelian, dokumen disposal). fileField() is the
   * drop zone of a question; filePicker() keeps the documents already saved (open them, or take them off the list)
   * and the files newly chosen, and uploads the new ones when the form is sent (AS.upload → [{id, name, type, size}]).
   */
  var DOC_MAX_BYTES = 10 * 1024 * 1024;
  function fileField(name, o) {
    o = o || {};
    return '<div class="gf-drop" data-drop="' + esc(name) + '">' + ic('file-up') + '<p class="gf-drop__title">Tarik file ke sini atau</p>' +
      '<p class="gf-drop__formats">PDF, JPG, atau PNG · maks. 10 MB per file' + (o.max ? ' · maks. ' + o.max + ' file' : '') + '</p>' +
      '<label class="gf-btn gf-btn--outline" for="g-' + esc(name) + '">' + ic('upload') + 'Tambahkan file</label>' +
      '<input class="gf-file" type="file" id="g-' + esc(name) + '" multiple accept="application/pdf,image/jpeg,image/png"></div>' +
      '<div class="as-docs as-picked" data-picked="' + esc(name) + '"></div>';
  }
  function docChip(f, extra) {
    var url = AS.fileUrl(f), icon = f.type === 'image' ? 'image' : 'file-text';
    var label = ic(icon) + '<span>' + esc(f.name) + '</span>';
    return '<span class="as-doc">' + (url ? '<a href="' + esc(url) + '" target="_blank" rel="noopener" title="Buka ' + esc(f.name) + '">' + label + '</a>' : label) + (extra || '') + '</span>';
  }
  function filePicker(root, name, o) {
    o = o || {};
    var max = o.max || 6, kept = (o.existing || []).slice(), added = [];
    var zone = root.querySelector('[data-drop="' + name + '"]'), list = root.querySelector('[data-picked="' + name + '"]'), inp = zone.querySelector('input');
    function draw() {
      list.innerHTML = kept.map(function (f, i) { return docChip(f, '<button type="button" class="as-doc__x" data-unkeep="' + i + '" aria-label="Lepas ' + esc(f.name) + '">' + ic('x') + '</button>'); }).join('') +
        added.map(function (f, i) {
          return '<span class="as-doc as-doc--new">' + ic(/^image\//.test(f.type) ? 'image' : 'file-text') + '<span>' + esc(f.name) + '</span>' +
            '<button type="button" class="as-doc__x" data-unadd="' + i + '" aria-label="Batal ' + esc(f.name) + '">' + ic('x') + '</button></span>';
        }).join('');
      render(list);
      if (o.onChange) o.onChange();
    }
    function addFiles(fl) {
      var refused = [];
      Array.prototype.slice.call(fl || []).forEach(function (f) {
        if (!/^(application\/pdf|image\/jpeg|image\/png)$/.test(f.type) && !/\.(pdf|jpe?g|png)$/i.test(f.name)) refused.push(f.name + ' (bukan PDF/JPG/PNG)');
        else if (f.size > DOC_MAX_BYTES) refused.push(f.name + ' (lebih dari 10 MB)');
        else if (kept.length + added.length >= max) refused.push(f.name + ' (maks. ' + max + ' file)');
        else added.push(f);
      });
      if (refused.length) toast('Sebagian file tidak ditambahkan', refused.join(', '), 'error');
      draw();
    }
    inp.addEventListener('change', function () { addFiles(inp.files); inp.value = ''; });
    ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('is-over'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('is-over'); }); });
    zone.addEventListener('drop', function (e) { addFiles(e.dataTransfer.files); });
    list.addEventListener('click', function (e) {
      var b = e.target.closest('[data-unkeep], [data-unadd]'); if (!b) return;
      if (b.hasAttribute('data-unkeep')) kept.splice(+b.getAttribute('data-unkeep'), 1); else added.splice(+b.getAttribute('data-unadd'), 1);
      draw();
    });
    draw();
    return {
      count: function () { return kept.length + added.length; },
      // Uploads the new files once (a retry after a refused save does not upload them again) → the whole list.
      upload: function (kind) {
        return AS.upload(added, kind).then(function (up) { kept = kept.concat(up); added = []; draw(); return kept.slice(); });
      }
    };
  }
  function docsHtml(docs) {
    if (!docs || !docs.length) return '<span class="rs-muted">Tidak ada lampiran</span>';
    return '<div class="as-docs">' + docs.map(function (d) { return docChip(d); }).join('') + '</div>';
  }

  /* ---------------- CSV ---------------- */
  function downloadCsv(name, rows) {
    var csv = rows.map(function (r) { return r.map(function (c) { c = String(c == null ? '' : c); return /[",;\n]/.test(c) ? '"' + c.replace(/"/g, '""') + '"' : c; }).join(','); }).join('\r\n');
    var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' });
    if (window.rsSaveFile) return window.rsSaveFile(name, blob);
    var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = name; a.dataset.siteWired = '1';
    document.body.appendChild(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
  }

  /* ---------------- module sub-navigation counters ---------------- */
  function navCounts() {
    var S = AS.state();
    var svc = S.services.filter(function (t) { return !AS.svcClosed(t.status); }).length;
    var dsp = S.disposals.filter(function (t) { return t.status === 'pending'; }).length;
    var set = function (k, n) { var el = document.querySelector('[data-count="' + k + '"]'); if (el) { el.textContent = n; el.hidden = !n; } };
    set('servis', svc); set('disposal', dsp);
  }

  /* simple pager */
  function pager(total, page, size) {
    var pages = Math.max(1, Math.ceil(total / size)); page = Math.min(Math.max(1, page), pages);
    var from = total ? (page - 1) * size + 1 : 0, to = Math.min(total, page * size);
    return { page: page, pages: pages, from: from, to: to,
      html: '<div class="as-pager"><span class="rs-count">' + (total ? from + '–' + to + ' dari ' + total : '0 data') + '</span><div class="as-pager__btns">' +
        '<button type="button" class="rs-btn rs-btn--ghost rs-btn--sm" data-pg="' + (page - 1) + '"' + (page <= 1 ? ' disabled' : '') + ' aria-label="Halaman sebelumnya">' + ic('chevron-left') + '</button>' +
        '<span class="as-pager__n">' + page + ' / ' + pages + '</span>' +
        '<button type="button" class="rs-btn rs-btn--ghost rs-btn--sm" data-pg="' + (page + 1) + '"' + (page >= pages ? ' disabled' : '') + ' aria-label="Halaman berikutnya">' + ic('chevron-right') + '</button></div></div>' };
  }

  /* one-shot message carried to the next page (e.g. after saving then redirecting) */
  function flash(t, d) { try { sessionStorage.setItem('as.flash', JSON.stringify([t, d || ''])); } catch (e) {} }
  function consumeFlash() {
    try { var f = JSON.parse(sessionStorage.getItem('as.flash') || 'null'); sessionStorage.removeItem('as.flash'); if (f) setTimeout(function () { toast(f[0], f[1], 'success'); }, 250); } catch (e) {}
  }

  function fail(e) { toast('Gagal', (e && e.message) || String(e), 'error'); }
  function saveThen(msgTitle, msgDesc) {
    return AS.save().then(function () { navCounts(); if (msgTitle) toast(msgTitle, msgDesc || '', 'success'); }, function () { toast('Gagal menyimpan', 'Perubahan belum tersimpan. Coba lagi.', 'error'); });
  }

  /*
   * "Ubah Status" of an open service, from any list (Rekap Servis, Lacak): progress, Selesai (device working), or
   * Ditolak / Rusak (device broken). The latter needs a berita acara (photo or PDF) — the same rule as the server
   * (AssetLedger::needReport). onDone(ticket) runs after the save.
   */
  var OUTCOMES = [
    { key: 'progress', label: 'Dalam Proses', status: 'progress' },
    { key: 'parts', label: 'Menunggu Suku Cadang', status: 'parts' },
    { key: 'done', label: 'Selesai — perangkat berfungsi', help: 'Status perangkat menjadi Aktif.', status: 'done', after: 'aktif' },
    // One choice for "could not be fixed" and "claim refused": the service ends as Ditolak, the device as Rusak.
    { key: 'rejected', label: 'Ditolak / Rusak', help: 'Perangkat tidak bisa diperbaiki atau klaim garansi ditolak. Status perangkat menjadi Rusak. Wajib berita acara.', status: 'rejected', after: 'rusak' }
  ];
  function serviceStatus(t, onDone) {
    var today = new Date(), ymd = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    var picker = null;
    var m = modal({
      title: 'Ubah Status Servis', size: 'md',
      subtitle: esc(t.id) + ' · ' + esc(t.deviceName) + ' · status saat ini: ' + esc(AS.SVC_STATUS[t.status].label),
      body:
        '<div class="pm-field" data-field="outcome"><span class="pm-label">Status baru <i class="req">*</i></span><div class="as-choice" role="radiogroup">' +
        OUTCOMES.map(function (o) {
          return '<label class="as-choice__opt"><input type="radio" name="outcome" value="' + o.key + '"' + (o.status === t.status ? ' disabled' : '') + '>' +
            '<span><b>' + esc(o.label) + '</b>' + (o.help ? '<small>' + esc(o.help) + '</small>' : '') + '</span></label>';
        }).join('') + '</div><p class="pm-err" hidden>' + ic('circle-alert') + '<span class="as-errt"></span></p></div>' +
        '<div data-when-closed hidden>' + field({ name: 'completedAt', label: 'Tanggal', type: 'date', value: ymd, max: ymd, req: true }) + '</div>' +
        field({ name: 'note', label: 'Catatan', type: 'textarea', rows: 3, req: true, ph: 'Hasil pengecekan, tindakan, atau alasan.' }) +
        '<div class="pm-field" data-field="docs"><span class="pm-label">Berita Acara <span data-docs-need>(opsional)</span></span>' +
        (t.docs && t.docs.length ? '<p class="pm-help">Sudah terlampir:</p>' + docsHtml(t.docs) : '') +
        '<p class="pm-help">Foto berita acara (bisa langsung dari kamera HP) atau PDF.</p>' + fileField('stdocs', { max: 10 }) +
        '<p class="pm-err" hidden>' + ic('circle-alert') + '<span class="as-errt"></span></p></div>',
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Batal</button><button type="submit" class="rs-btn rs-btn--primary" data-save>' + ic('save') + ' Simpan Status</button>',
      onMount: function (api) {
        picker = filePicker(api.el, 'stdocs', { max: 10, onChange: function () { setErr(api.el, 'docs', ''); } });
        api.form.addEventListener('change', function (e) {
          if (e.target.name !== 'outcome') return;
          var o = chosen(api), closed = AS.svcClosed(o.status), need = o.after === 'rusak' && closed;
          api.$('[data-when-closed]').hidden = !closed;
          api.$('[data-docs-need]').textContent = need ? '(wajib)' : '(opsional)';
          setErr(api.el, 'outcome', '');
        });
      },
      onSubmit: function (api) {
        clearErrs(api.el);
        var o = chosen(api), note = val(api.el, 'note'), ok = true;
        if (!o) { setErr(api.el, 'outcome', 'Pilih status baru.'); ok = false; }
        if (!note) { setErr(api.el, 'note', 'Tuliskan catatan pembaruan.'); ok = false; }
        if (o && AS.svcClosed(o.status) && o.after === 'rusak' && !picker.count() && !(t.docs && t.docs.length)) {
          setErr(api.el, 'docs', 'Unggah berita acara kerusakan / penolakan servis.'); ok = false;
        }
        var date = val(api.el, 'completedAt');
        if (o && AS.svcClosed(o.status) && date && date < t.reportDate) { setErr(api.el, 'completedAt', 'Tanggal sebelum tanggal laporan.'); ok = false; }
        if (!ok) { focusFirstErr(api.el); return; }
        var btn = api.$('[data-save]'); btn.disabled = true;
        picker.upload('berita_acara').then(function (docs) {
          AS.updateService(t.id, { status: o.status, note: note, notes: AS.svcClosed(o.status) ? note : undefined, completedAt: date, afterStatus: o.after || '', docs: docs });
          return AS.save();
        }).then(function () {
          api.close(); navCounts();
          var saved = AS.state().services.filter(function (x) { return x.id === t.id; })[0] || t;
          toast('Status servis diperbarui', t.id + ' — ' + AS.SVC_STATUS[saved.status].label + (AS.svcClosed(saved.status) ? ' · perangkat ' + AS.STATUS[AS.endStatus(saved.status, saved.afterStatus)].label : ''), 'success');
          if (onDone) onDone(saved);
        }).catch(function (err) { btn.disabled = false; fail(err); });
      }
    });
    function chosen(api) {
      var r = api.$('[name="outcome"]:checked');
      return r ? OUTCOMES.filter(function (o) { return o.key === r.value; })[0] : null;
    }
    return m;
  }

  window.AU = {
    esc: esc, fmtDate: fmtDate, fmtDateTime: fmtDateTime, ago: ago, today: today, nowLocal: nowLocal, ic: ic, pill: pill,
    statusPill: statusPill, svcPill: svcPill, dueBadge: dueBadge, dueText: dueText, dspPill: dspPill, catOf: catOf, catName: catName, catChip: catChip, code: code, locHtml: locHtml,
    toast: toast, render: render, qs: qs, trackUrl: trackUrl, modal: modal, confirm: confirmBox, field: field, setErr: setErr,
    clearErrs: clearErrs, val: val, focusFirstErr: focusFirstErr, locFields: locFields, readLoc: readLoc, checkLoc: checkLoc,
    code128: code128, labelHtml: labelHtml, printLabel: printLabel, scan: scan, lookup: lookup, deviceCard: deviceCard,
    fileField: fileField, filePicker: filePicker, docsHtml: docsHtml, serviceStatus: serviceStatus, downloadCsv: downloadCsv, navCounts: navCounts, pager: pager, fail: fail, saveThen: saveThen,
    flash: flash, consumeFlash: consumeFlash
  };
})();
