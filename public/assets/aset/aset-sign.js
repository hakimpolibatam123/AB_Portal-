/* Signature window shared by Serah Terima Perangkat and Form Clearance: Ketik (typed in a handwriting font),
 * Gambar (drawn with mouse, pen or finger) or Unggah (a photo or scan, its white paper made transparent).
 * ASign.dialog(name, done) calls done(canvas) with the signature cropped to its ink. */
(function () {
  'use strict';
  var AU = window.AU, esc = AU.esc, ic = AU.ic;
  // Handwriting fonts of the viewer's system; the signature is saved as a picture, so it looks the same everywhere.
  var FONTS = ["'Segoe Script','Snell Roundhand',cursive", "'Lucida Handwriting','Bradley Hand',cursive", "'Ink Free','Noteworthy','Brush Script MT',cursive"];
  var PAD_W = 720, PAD_H = 320;

  /* The canvas cropped to its ink, or null when it is empty. */
  function trim(c) {
    var w = c.width, h = c.height, px = c.getContext('2d').getImageData(0, 0, w, h).data, x0 = w, y0 = h, x1 = -1, y1 = -1;
    for (var y = 0; y < h; y++) for (var x = 0; x < w; x++) if (px[(y * w + x) * 4 + 3] > 12) { if (x < x0) x0 = x; if (x > x1) x1 = x; if (y < y0) y0 = y; if (y > y1) y1 = y; }
    if (x1 < 0) return null;
    var out = document.createElement('canvas'); out.width = x1 - x0 + 1; out.height = y1 - y0 + 1;
    out.getContext('2d').drawImage(c, x0, y0, out.width, out.height, 0, 0, out.width, out.height);
    return out;
  }
  function typed(text, font) {
    var c = document.createElement('canvas'); c.width = 1400; c.height = 360;
    var g = c.getContext('2d'); g.font = '110px ' + font; g.fillStyle = '#16233f'; g.textBaseline = 'middle'; g.fillText(text, 30, 180);
    return trim(c);
  }
  /* A photo or scan of a signature: scaled down, and its white paper made transparent. */
  function fromImage(img) {
    var k = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight)), c = document.createElement('canvas');
    c.width = Math.max(1, Math.round(img.naturalWidth * k)); c.height = Math.max(1, Math.round(img.naturalHeight * k));
    var g = c.getContext('2d'); g.drawImage(img, 0, 0, c.width, c.height);
    var d = g.getImageData(0, 0, c.width, c.height), p = d.data;
    for (var i = 0; i < p.length; i += 4) { var v = Math.min(p[i], p[i + 1], p[i + 2]); if (v > 205) p[i + 3] = 0; else if (v > 150) p[i + 3] = Math.round(p[i + 3] * (205 - v) / 55); }
    g.putImageData(d, 0, 0);
    return trim(c);
  }

  /* The window with Ketik / Gambar / Unggah. done(canvas) receives the signature. */
  function signDialog(name, done) {
    var mode = 'type', ink = '#16233f', upload = null, drawing = false, last = null;
    var m = AU.modal({ title: 'Tanda Tangan', size: 'md',
      body: '<div class="pm-tabs as-scan-tabs" role="tablist">' +
        '<button type="button" role="tab" aria-selected="true" data-mode="type">' + ic('type') + ' Ketik</button>' +
        '<button type="button" role="tab" aria-selected="false" data-mode="draw">' + ic('pencil') + ' Gambar</button>' +
        '<button type="button" role="tab" aria-selected="false" data-mode="upload">' + ic('upload') + ' Unggah</button></div>' +
        '<div data-sig="type"><label class="as-sig-label" for="sig-name">Nama</label><input class="pm-input" id="sig-name" maxlength="60" value="' + esc(name) + '">' +
        '<div class="as-sig-styles">' + FONTS.map(function (f, i) { return '<label class="as-sig-style"><input type="radio" name="sigfont" value="' + i + '"' + (i ? '' : ' checked') + '>' + ic('check') + '<span data-preview style="font-family:' + esc(f) + '"></span></label>'; }).join('') + '</div></div>' +
        '<div data-sig="draw" hidden><canvas class="as-sig-draw" width="' + PAD_W + '" height="' + PAD_H + '"></canvas><div class="as-sig-tools"><span class="as-sig-inks">' +
        ['#16233f', '#1d4ed8', '#111111'].map(function (c, i) { return '<button type="button" class="as-sig-ink" data-ink="' + c + '" style="background:' + c + '" aria-label="Warna tinta ' + (i + 1) + '" aria-pressed="' + (i ? 'false' : 'true') + '"></button>'; }).join('') + '</span>' +
        '<button type="button" class="as-textbtn" data-clear-ink>Hapus coretan</button></div></div>' +
        '<div data-sig="upload" hidden><label class="gf-drop as-sig-drop"><input type="file" accept="image/png,image/jpeg" data-file hidden>' + ic('upload') + '<span class="gf-drop__title">Pilih foto atau gambar tanda tangan</span><span class="gf-drop__formats">JPG atau PNG · maks. 10 MB · latar putih dihapus otomatis</span></label>' +
        '<canvas class="as-sig-preview" hidden></canvas></div>' +
        '<p class="pm-err" data-sigerr hidden>' + ic('circle-alert') + '<span class="as-errt"></span></p>',
      foot: '<button type="button" class="rs-btn rs-btn--ghost" data-x>Batal</button><button type="submit" class="rs-btn rs-btn--primary">' + ic('check') + ' Gunakan</button>',
      onSubmit: function (api) {
        var c = mode === 'type' ? (input.value.trim() ? typed(input.value.trim(), FONTS[+api.$('[name="sigfont"]:checked').value]) : null) : mode === 'draw' ? trim(pad) : upload;
        if (!c) return fail(mode === 'type' ? 'Ketik nama terlebih dahulu.' : mode === 'draw' ? 'Buat coretan tanda tangan terlebih dahulu.' : 'Pilih gambar tanda tangan terlebih dahulu.');
        api.close(); done(c, mode === 'type' ? input.value.trim() : '');
      } });
    var input = m.$('#sig-name'), pad = m.$('.as-sig-draw'), g = pad.getContext('2d'), err = m.$('[data-sigerr]');
    function fail(t) { err.hidden = !t; err.querySelector('.as-errt').textContent = t || ''; }
    function previews() { m.$$('[data-preview]').forEach(function (s) { s.textContent = input.value || 'Tanda tangan'; }); }
    previews(); input.addEventListener('input', function () { fail(''); previews(); });
    m.$$('[data-mode]').forEach(function (b) {
      b.addEventListener('click', function () {
        mode = b.getAttribute('data-mode'); fail('');
        m.$$('[data-mode]').forEach(function (x) { x.setAttribute('aria-selected', String(x === b)); });
        m.$$('[data-sig]').forEach(function (p) { p.hidden = p.getAttribute('data-sig') !== mode; });
      });
    });
    function at(e) { var r = pad.getBoundingClientRect(); return [(e.clientX - r.left) * PAD_W / r.width, (e.clientY - r.top) * PAD_H / r.height]; }
    pad.addEventListener('pointerdown', function (e) { drawing = true; last = at(e); pad.setPointerCapture(e.pointerId); g.fillStyle = ink; g.beginPath(); g.arc(last[0], last[1], 2, 0, 7); g.fill(); fail(''); });
    pad.addEventListener('pointermove', function (e) {
      if (!drawing) return;
      var p = at(e); g.strokeStyle = ink; g.lineWidth = 4; g.lineCap = 'round'; g.lineJoin = 'round'; g.beginPath(); g.moveTo(last[0], last[1]); g.lineTo(p[0], p[1]); g.stroke(); last = p;
    });
    ['pointerup', 'pointercancel'].forEach(function (ev) { pad.addEventListener(ev, function () { drawing = false; }); });
    m.$('[data-clear-ink]').addEventListener('click', function () { g.clearRect(0, 0, PAD_W, PAD_H); });
    m.$$('[data-ink]').forEach(function (b) { b.addEventListener('click', function () { ink = b.getAttribute('data-ink'); m.$$('[data-ink]').forEach(function (x) { x.setAttribute('aria-pressed', String(x === b)); }); }); });
    m.$('[data-file]').addEventListener('change', function (e) {
      var f = e.target.files && e.target.files[0]; e.target.value = ''; upload = null; fail('');
      var view = m.$('.as-sig-preview'); view.hidden = true;
      if (!f) return;
      if (!/^image\/(png|jpeg)$/.test(f.type)) return fail('Pilih gambar JPG atau PNG.');
      if (f.size > 10 * 1024 * 1024) return fail('Ukuran gambar lebih dari 10 MB.');
      var url = URL.createObjectURL(f), img = new Image();
      img.onload = function () {
        upload = fromImage(img); URL.revokeObjectURL(url);
        if (!upload) return fail('Tanda tangan tidak terlihat di gambar ini.');
        view.width = upload.width; view.height = upload.height; view.getContext('2d').drawImage(upload, 0, 0); view.hidden = false;
      };
      img.onerror = function () { URL.revokeObjectURL(url); fail('Gambar tidak dapat dibaca.'); };
      img.src = url;
    });
  }

  window.ASign = { dialog: signDialog, trim: trim, PAD_W: PAD_W, PAD_H: PAD_H };
})();
