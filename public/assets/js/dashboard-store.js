(() => {
  'use strict';

  /* Shared by the dashboard (dashboard.js) and the form editor (form-editor.js), frontend only.
     The start state is the dummy data of database/dummy/dashboard.php, embedded by the server as
     #dashboard-state; every change lives in this browser tab (sessionStorage) and nothing is sent to
     the server. Also: Lucide icons from #dash-icons, the toast (components/toast.php) and the
     schedule rules (same as app/Helpers/Schedule.php, on WIB calendar dates). */
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));
  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
  const uid = (prefix) => `${prefix}-${Math.random().toString(36).slice(2, 9)}`;
  const clone = (value) => JSON.parse(JSON.stringify(value));

  /* ---------- Icons (Lucide, drawn by the server into #dash-icons) ---------- */
  const icon = (name, className = 'size-6') => {
    const box = $('#dash-icons');
    const svg = box?.content.querySelector(`[data-icon="${CSS.escape(name)}"] svg`) ?? box?.content.querySelector('[data-icon="clipboard-list"] svg');
    if (!svg) return '';
    const copy = svg.cloneNode(true);
    copy.setAttribute('class', className);
    return copy.outerHTML;
  };

  /* ---------- Toast (components/toast.php) ---------- */
  const TOAST = {
    success: { color: '#15803d', bg: '#dcfce7', icon: 'circle-check' },
    info: { color: '#b45309', bg: '#fef3c7', icon: 'info' },
    error: { color: '#b91c1c', bg: '#fee2e2', icon: 'circle-alert' },
  };
  let toastTimer = 0;
  const showToast = (title, description = '', variant = 'success') => {
    const toast = $('#site-toast');
    if (!toast) return;
    const style = TOAST[variant] ?? TOAST.success;
    const box = $('[data-toast-icon]', toast);
    $('.rs-toast__box', toast).style.setProperty('--rs-toast', style.color);
    box.style.background = style.bg;
    box.style.color = style.color;
    box.innerHTML = $('#rs-icons')?.content.querySelector(`[data-name="${style.icon}"]`)?.outerHTML ?? '';
    $('[data-toast-title]', toast).textContent = title;
    const desc = $('[data-toast-desc]', toast);
    desc.textContent = description;
    desc.hidden = description === '';
    const now = new Date();
    $('[data-toast-time]', toast).textContent = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')} WIB`;
    toast.classList.add('is-show');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toast.classList.remove('is-show'), 5000);
  };
  document.addEventListener('click', (event) => {
    if (event.target.closest('#site-toast [data-toast-close]')) $('#site-toast').classList.remove('is-show');
  });

  /* ---------- Schedules ---------- */
  const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  const nowWib = () => {
    const p = Object.fromEntries(new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
      .formatToParts(new Date()).map((part) => [part.type, part.value]));
    return { day: new Date(Date.UTC(+p.year, +p.month - 1, +p.day)), hm: `${p.hour}:${p.minute}` };
  };
  const ymd = (day) => day.toISOString().slice(0, 10);
  const isDue = (s, day) => {
    const key = ymd(day);
    if (s.start && key < s.start) return false;
    if (s.end && key > s.end) return false;
    const last = new Date(Date.UTC(day.getUTCFullYear(), day.getUTCMonth() + 1, 0)).getUTCDate();
    switch (s.type) {
      case 'weekly': return (s.days ?? []).map(Number).includes(day.getUTCDay());
      case 'monthly': return s.month_day === 'last' ? day.getUTCDate() === last : day.getUTCDate() === Math.min(Math.max(1, +s.month_day || 1), last);
      case 'yearly': return Boolean(s.year_md) && key.slice(5) === s.year_md;
      case 'dates': return (s.dates ?? []).includes(key);
      default: return true;
    }
  };
  const nextDue = (s, from) => {
    const day = new Date(from.getTime());
    for (let i = 0; i < 800; i++) {
      day.setUTCDate(day.getUTCDate() + 1);
      if (isDue(s, day)) return day;
    }
    return null;
  };
  const cardStatus = (card, now) => {
    const s = card.schedule;
    const due = isDue(s, now.day);
    if (!card.has_data && !card.done_today) return { text: 'Belum ada data', tone: 'muted', due };
    if (!due) {
      const next = nextDue(s, now.day);
      return { text: next ? `Berikutnya: ${next.getUTCDate()} ${MONTHS[next.getUTCMonth()]}` : 'Tidak terjadwal', tone: 'next', due: false };
    }
    if (card.done_today) return { text: 'Selesai', tone: 'ok', due: true };
    if (s.deadline && s.reminder && now.hm > s.deadline) return { text: 'Belum Diisi', tone: 'warn', due: true };
    if (s.deadline) return { text: `Batas ${s.deadline}`, tone: 'plain', due: true };
    return { text: 'Hari ini', tone: 'plain', due: true };
  };

  /* Badge text of a schedule (design: Harian · Mingguan - Senin · Bulanan - Tgl 1 · Tahunan - 17 Agu · Tanggal tertentu). */
  const DAY_SHORT = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
  const DAY_LONG = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  const scheduleLabel = (s) => {
    switch (s?.type) {
      case 'weekly': {
        const days = [...(s.days ?? [])].map(Number).sort((a, b) => ((a + 6) % 7) - ((b + 6) % 7));
        if (!days.length) return { text: 'Mingguan', tone: 'blue' };
        return { text: `Mingguan - ${days.length === 1 ? DAY_LONG[days[0]] : days.map((d) => DAY_SHORT[d]).join(', ')}`, tone: 'blue' };
      }
      case 'monthly': return { text: `Bulanan - ${s.month_day === 'last' ? 'Akhir bulan' : `Tgl ${s.month_day || 1}`}`, tone: 'amber' };
      case 'yearly': {
        const [month, day] = String(s.year_md || '').split('-');
        return { text: `Tahunan${day ? ` - ${+day} ${MONTHS[+month - 1]}` : ''}`, tone: 'purple' };
      }
      case 'dates': return { text: 'Tanggal tertentu', tone: 'slate' };
      default: return { text: 'Harian', tone: 'teal' };
    }
  };

  /* Sentence under the Jadwal tab (design): "Form ini akan tampil setiap Senin dan Kamis mulai 27 Okt 2026. Batas pengisian pukul 14:00." */
  const MONTHS_LONG = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  const parseYmd = (value) => {
    const [y, m, d] = String(value || '').split('-').map(Number);
    return y && m && d ? new Date(Date.UTC(y, m - 1, d)) : null;
  };
  const dayText = (value) => { const d = parseYmd(value); return d ? `${d.getUTCDate()} ${MONTHS[d.getUTCMonth()]} ${d.getUTCFullYear()}` : ''; };
  const scheduleSummary = (s) => {
    let tail = (s.start ? ` mulai ${dayText(s.start)}` : '') + (s.end ? ` sampai ${dayText(s.end)}` : '');
    let what;
    switch (s.type) {
      case 'weekly': {
        const days = [...(s.days ?? [])].map(Number).sort((a, b) => ((a + 6) % 7) - ((b + 6) % 7)).map((d) => DAY_LONG[d]);
        if (!days.length) return 'Pilih minimal satu hari agar form tampil di dashboard.';
        what = `setiap ${days.length > 1 ? `${days.slice(0, -1).join(', ')} dan ${days.at(-1)}` : days[0]}`;
        break;
      }
      case 'monthly': what = s.month_day === 'last' ? 'setiap hari terakhir bulan' : `setiap tanggal ${s.month_day || 1}`; break;
      case 'yearly': {
        const [month, day] = String(s.year_md || '').split('-');
        if (!day) return 'Pilih tanggal agar form tampil di dashboard.';
        what = `setiap ${+day} ${MONTHS_LONG[+month - 1]}`;
        break;
      }
      case 'dates':
        if (!(s.dates ?? []).length) return 'Tambahkan minimal satu tanggal agar form tampil di dashboard.';
        what = `pada ${[...s.dates].sort().map(dayText).join(', ')}`;
        tail = '';
        break;
      default: what = 'setiap hari';
    }
    return `Form ini akan tampil ${what}${tail}.${s.deadline ? ` Batas pengisian pukul ${s.deadline}.` : ''}`;
  };

  /* ---------- State: dummy data + changes in sessionStorage ---------- */
  const KEY = 'abportal.dashboard';
  const stateEl = $('#dashboard-state');
  const seedText = stateEl?.textContent ?? '';
  // A changed dummy file starts the demo again instead of reusing an older saved state.
  const seedId = String([...seedText].reduce((h, c) => (Math.imul(h, 31) + c.charCodeAt(0)) | 0, 7));
  const seed = () => JSON.parse(seedText);
  const load = () => {
    try {
      const saved = JSON.parse(window.sessionStorage.getItem(KEY) ?? 'null');
      if (saved && saved.seedId === seedId) return saved.state;
    } catch { /* unreadable: start from the dummy data */ }
    return seed();
  };
  const save = (state) => {
    try { window.sessionStorage.setItem(KEY, JSON.stringify({ seedId, state })); } catch { /* private mode: changes last until reload */ }
  };
  const reset = () => {
    try { window.sessionStorage.removeItem(KEY); } catch { /* nothing saved */ }
    return seed();
  };

  window.DashboardStore = {
    ready: Boolean(stateEl), $, $$, esc, uid, clone, icon, showToast, MONTHS, MONTHS_LONG, DAY_SHORT, DAY_LONG, nowWib, ymd, parseYmd, isDue, nextDue, cardStatus, scheduleLabel, scheduleSummary, load, save, reset,
  };
})();
