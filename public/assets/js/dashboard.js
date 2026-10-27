(() => {
  'use strict';

  /* Editable dashboard: card menu, "Tambah" / "Edit Tautan" dialog, Pindah Kategori, delete
     confirmation, hidden cards and toasts. Markup: home/index.php, components/portal-card.php,
     components/dashboard-dialogs.php. The server validates every change again. */
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  /* ---------- Toast ---------- */
  const TOAST = {
    success: { color: '#15803d', bg: '#dcfce7', icon: 'circle-check' },
    info: { color: '#b45309', bg: '#fef3c7', icon: 'info' },
    error: { color: '#b91c1c', bg: '#fee2e2', icon: 'circle-alert' },
  };
  const toast = $('#site-toast');
  let toastTimer = 0;
  const showToast = (title, description = '', variant = 'success') => {
    if (!toast) return;
    const style = TOAST[variant] ?? TOAST.success;
    const iconBox = $('[data-toast-icon]', toast);
    $('.rs-toast__box', toast).style.setProperty('--rs-toast', style.color);
    iconBox.style.background = style.bg;
    iconBox.style.color = style.color;
    iconBox.innerHTML = $('#rs-icons')?.content.querySelector(`[data-name="${style.icon}"]`)?.outerHTML ?? '';
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
  $('[data-toast-close]', toast ?? document)?.addEventListener('click', () => toast.classList.remove('is-show'));
  if (toast?.dataset.flashTitle) showToast(toast.dataset.flashTitle, toast.dataset.flashDesc ?? '', toast.dataset.flashVariant ?? 'success');

  /* ---------- New or changed card: short highlight, scrolled into view ---------- */
  const fresh = $('.portal-card.is-new');
  if (fresh) {
    if (fresh.hidden) fresh.closest('[data-section]')?.querySelector('[data-toggle-hidden]')?.click();
    fresh.scrollIntoView({ block: 'center' });
    $('.card-link', fresh)?.focus({ preventScroll: true });
    window.setTimeout(() => { fresh.classList.remove('is-new'); $('[data-new-chip]', fresh)?.remove(); }, 8000);
  }
  if (new URLSearchParams(window.location.search).has('baru')) window.history.replaceState(null, '', window.location.pathname);

  /* ---------- Hidden cards per section ---------- */
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toggle-hidden]');
    if (!button) return;
    const section = button.closest('[data-section]');
    const show = button.getAttribute('aria-pressed') !== 'true';
    button.setAttribute('aria-pressed', String(show));
    $('[data-toggle-label]', button).textContent = show ? button.dataset.labelHide : button.dataset.labelShow;
    section.classList.toggle('is-showing-hidden', show);
    $$('[data-hidden-card]', section).forEach((card) => { card.hidden = !show; });
  });

  /* ---------- Dialog helpers ---------- */
  const openDialog = (dialog) => { dialog.showModal(); };
  $$('dialog.dash-dialog').forEach((dialog) => {
    $$('[data-dialog-close]', dialog).forEach((button) => button.addEventListener('click', () => dialog.close()));
    // Small confirmation dialogs close on a click outside; the link dialog keeps typed input.
    if (dialog.id !== 'link-dialog') {
      dialog.addEventListener('click', (event) => {
        if (event.target !== dialog) return;
        const r = dialog.getBoundingClientRect();
        if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
      });
    }
  });
  const cardAction = (template, id) => template.replace('{id}', encodeURIComponent(String(id)));

  /* ---------- Card menu ---------- */
  const menu = $('#card-menu');
  const actionForm = $('#card-action');
  let menuButton = null;
  const menuIcon = (name) => $('#card-menu-icons')?.content.querySelector(`[data-icon="${name}"]`)?.innerHTML ?? '';
  const closeMenu = (focusBack = false) => {
    if (!menuButton) return;
    menu.hidden = true;
    menu.replaceChildren();
    menuButton.setAttribute('aria-expanded', 'false');
    if (focusBack) menuButton.focus();
    menuButton = null;
  };
  const placeMenu = () => {
    if (!menuButton) return;
    const r = menuButton.getBoundingClientRect();
    if (r.bottom < 0 || r.top > window.innerHeight) { closeMenu(); return; }
    const left = Math.min(Math.max(8, r.right - menu.offsetWidth), window.innerWidth - menu.offsetWidth - 8);
    let top = r.bottom + 6;
    if (top + menu.offsetHeight > window.innerHeight - 8) top = Math.max(8, r.top - menu.offsetHeight - 6);
    menu.style.left = `${left}px`;
    menu.style.top = `${top}px`;
  };
  const menuItem = (action, label, extra = {}) => {
    const item = document.createElement(extra.href ? 'a' : 'button');
    item.setAttribute('role', 'menuitem');
    item.dataset.action = action;
    if (extra.href) item.href = extra.href; else item.type = 'button';
    if (extra.danger) item.classList.add('is-danger');
    item.innerHTML = menuIcon(extra.icon ?? action);
    item.append(label);
    return item;
  };
  const openMenu = (button) => {
    if (menuButton === button) { closeMenu(true); return; }
    closeMenu();
    const card = JSON.parse(button.closest('[data-card]').dataset.card);
    const items = [];
    if (card.kind === 'builtin' && card.rekap) items.push(menuItem('rekap', 'Lihat Rekap', { href: card.rekap }));
    if (card.kind === 'link') items.push(menuItem('edit', 'Edit'), menuItem('move', 'Pindah Kategori'));
    items.push(card.hidden ? menuItem('show', 'Tampilkan di dashboard') : menuItem('hide', 'Sembunyikan dari dashboard'));
    if (card.kind === 'link') items.push(document.createElement('hr'), menuItem('delete', 'Hapus', { danger: true }));
    menu.replaceChildren(...items);
    menu.hidden = false;
    menu.card = card;
    menuButton = button;
    button.setAttribute('aria-expanded', 'true');
    placeMenu();
    $('[role="menuitem"]', menu)?.focus({ preventScroll: true });
  };
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-card-menu]');
    if (button) { event.preventDefault(); openMenu(button); return; }
    if (menuButton && !menu.contains(event.target)) closeMenu();
  });
  menu?.addEventListener('keydown', (event) => {
    const items = $$('[role="menuitem"]', menu);
    const i = items.indexOf(document.activeElement);
    if (event.key === 'ArrowDown') { event.preventDefault(); items[(i + 1) % items.length].focus(); }
    else if (event.key === 'ArrowUp') { event.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
    else if (event.key === 'Home') { event.preventDefault(); items[0].focus(); }
    else if (event.key === 'End') { event.preventDefault(); items[items.length - 1].focus(); }
    else if (event.key === 'Escape') { event.preventDefault(); closeMenu(true); }
    else if (event.key === 'Tab') closeMenu();
  });
  menu?.addEventListener('click', (event) => {
    const item = event.target.closest('[data-action]');
    if (!item) return;
    const card = menu.card;
    const action = item.dataset.action;
    if (action === 'rekap') return; // a normal link
    closeMenu();
    if (action === 'hide' || action === 'show') {
      actionForm.action = cardAction(actionForm.dataset.action, card.id) + action;
      actionForm.submit();
    } else if (action === 'edit') openLinkDialog(card);
    else if (action === 'move') openMoveDialog(card);
    else if (action === 'delete') openDeleteDialog(card);
  });
  window.addEventListener('resize', () => closeMenu());
  window.addEventListener('scroll', placeMenu, true);

  /* ---------- Pindah Kategori and delete ---------- */
  const moveDialog = $('#move-dialog');
  const openMoveDialog = (card) => {
    const form = $('form', moveDialog);
    form.action = cardAction(form.dataset.action, card.id);
    $('[data-card-name]', moveDialog).textContent = card.title;
    $$('input[name="section_id"]', moveDialog).forEach((radio) => { radio.checked = Number(radio.value) === card.section; });
    openDialog(moveDialog);
    ($('input:checked', moveDialog) ?? $('input[name="section_id"]', moveDialog))?.focus();
  };
  const deleteDialog = $('#delete-dialog');
  const openDeleteDialog = (card) => {
    const form = $('form', deleteDialog);
    form.action = cardAction(form.dataset.action, card.id);
    $('[data-card-name]', deleteDialog).textContent = card.title;
    openDialog(deleteDialog);
    $('[data-dialog-close]', deleteDialog).focus();
  };

  /* ---------- "Tambah" / "Edit Tautan" ---------- */
  const linkDialog = $('#link-dialog');
  if (!linkDialog) return;
  const form = $('#link-form');
  const field = (name) => form.elements.namedItem(name);
  const iconGrid = $('[data-icon-grid]', form);
  const newCat = $('[data-newcat]', form);
  const openUrl = $('[data-open-url]', form);
  const MAX_IMAGE = 1024 * 1024;
  const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/webp'];
  let editing = null;
  let createdSection = false;
  let previewImage = '';

  const validUrl = (value) => {
    try {
      // Same rule as the server: http or https with a host (internal names such as http://his are allowed).
      const url = new URL(value);
      return ['http:', 'https:'].includes(url.protocol) && url.hostname !== '';
    } catch { return false; }
  };
  const setError = (name, message) => {
    const box = $(`[data-error-for="${name}"]`, form);
    if (box) { box.textContent = message; box.hidden = message === ''; }
    const input = name === 'icon' ? null : field(name);
    if (input instanceof HTMLElement) input.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
  };
  const clearErrors = () => {
    $$('[data-error-for]', form).forEach((box) => { box.textContent = ''; box.hidden = true; });
    $$('[aria-invalid]', form).forEach((input) => input.setAttribute('aria-invalid', 'false'));
    const alert = $('[data-form-error]', form);
    alert.hidden = true;
    alert.textContent = '';
  };
  const iconSource = () => form.querySelector('input[name="icon_source"]:checked').value;
  const setIconSource = (source) => {
    $(`input[name="icon_source"][value="${source}"]`, form).checked = true;
    $$('[data-icon-panel]', form).forEach((panel) => { panel.hidden = panel.dataset.iconPanel !== source; });
    preview();
  };
  const preview = () => {
    const title = field('title').value.trim();
    const description = field('description').value.trim();
    const url = field('url').value.trim();
    $('[data-preview-title]', form).textContent = title || 'Judul tautan';
    $('[data-preview-description]', form).textContent = description;
    $('[data-preview-note]', form).hidden = validUrl(url);
    $('[data-count-for="link-description"]', form).textContent = `${field('description').value.length}/60`;
    const box = $('[data-preview-icon]', form);
    if (iconSource() === 'image' && previewImage) {
      const img = document.createElement('img');
      img.className = 'card-icon-img';
      img.alt = '';
      img.src = previewImage;
      box.replaceChildren(img);
    } else {
      const checked = $('input[name="icon"]:checked', iconGrid);
      box.innerHTML = checked ? checked.parentElement.querySelector('svg').outerHTML.replace('size-5', 'size-6') : '';
    }
    if (validUrl(url)) { openUrl.href = url; openUrl.removeAttribute('aria-disabled'); }
    else { openUrl.removeAttribute('href'); openUrl.setAttribute('aria-disabled', 'true'); }
  };
  const showNewCat = (show) => {
    newCat.hidden = !show;
    setError('category_title', '');
    if (show) { field('category_title').value = ''; field('category_title').focus(); }
    else if (field('section_id').value === '__new') field('section_id').value = field('section_id').dataset.last ?? field('section_id').options[0].value;
  };

  function openLinkDialog(card = null) {
    editing = card;
    form.reset();
    clearErrors();
    newCat.hidden = true;
    previewImage = card?.image ?? '';
    form.action = card ? form.dataset.updateAction + encodeURIComponent(String(card.id)) : form.dataset.createAction;
    $('[data-dialog-title]', form).textContent = card ? 'Edit Tautan' : 'Tambah';
    const select = field('section_id');
    select.value = card ? String(card.section) : select.options[0].value;
    select.dataset.last = select.value;
    field('title').value = card?.title ?? '';
    field('description').value = card?.description ?? '';
    field('url').value = card?.url ?? '';
    const icon = $(`input[name="icon"][value="${CSS.escape(card?.icon ?? 'link')}"]`, iconGrid) ?? $('input[name="icon"][value="link"]', iconGrid);
    if (icon) icon.checked = true;
    $('[data-icon-search]', form).value = '';
    filterIcons('');
    $('[data-current-image]', form).hidden = !card?.image;
    setIconSource(card?.image ? 'image' : 'list');
    openDialog(linkDialog);
    $('.dash-dialog__body', linkDialog).scrollTop = 0;
    field('title').focus();
    // Show the chosen icon inside the icon list only; the dialog itself stays at the top.
    if (icon) iconGrid.scrollTop += icon.parentElement.getBoundingClientRect().top - iconGrid.getBoundingClientRect().top - 4;
  }
  const filterIcons = (query) => {
    const words = query.toLowerCase().trim();
    let shown = 0;
    $$('.icon-option', iconGrid).forEach((option) => {
      const match = words === '' || option.dataset.keywords.includes(words);
      option.hidden = !match;
      if (match) shown++;
    });
    $('[data-icon-empty]', form).hidden = shown > 0;
  };

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-open-add]')) openLinkDialog();
  });
  linkDialog.addEventListener('close', () => { if (createdSection) window.location.reload(); });
  form.addEventListener('input', (event) => {
    if (event.target.matches('[data-icon-search]')) { filterIcons(event.target.value); return; }
    if (event.target.name && event.target.name !== 'category_title') setError(event.target.name, '');
    preview();
  });
  form.addEventListener('change', (event) => {
    const target = event.target;
    if (target.name === 'section_id') {
      if (target.value === '__new') showNewCat(true);
      else { target.dataset.last = target.value; newCat.hidden = true; setError('section_id', ''); }
    } else if (target.name === 'icon_source') setIconSource(target.value);
    else if (target.name === 'icon') { setError('icon', ''); preview(); }
    else if (target.name === 'icon_file') {
      const file = target.files[0];
      setError('icon_file', '');
      if (!file) { previewImage = editing?.image ?? ''; preview(); return; }
      if (!IMAGE_TYPES.includes(file.type)) { setError('icon_file', 'Pilih gambar PNG, JPG, atau WebP.'); target.value = ''; return; }
      if (file.size > MAX_IMAGE) { setError('icon_file', 'Ukuran gambar maksimal 1 MB.'); target.value = ''; return; }
      previewImage = URL.createObjectURL(file);
      preview();
    }
  });
  openUrl.addEventListener('click', (event) => {
    if (openUrl.getAttribute('aria-disabled') === 'true') { event.preventDefault(); field('url').focus(); }
  });
  $('[data-newcat-cancel]', form).addEventListener('click', () => { showNewCat(false); field('section_id').focus(); });

  const post = async (url, body) => {
    const response = await fetch(url, { method: 'POST', body, headers: { Accept: 'application/json' }, credentials: 'same-origin' });
    let data = {};
    try { data = await response.json(); } catch { /* not JSON */ }
    return { status: response.status, data };
  };
  const failMessage = (status, data) => data.message
    ?? (status >= 500 ? 'Server sedang bermasalah. Coba sekali lagi.' : 'Perubahan belum tersimpan. Coba sekali lagi.');
  const showFormError = (message) => {
    const alert = $('[data-form-error]', form);
    alert.textContent = message;
    alert.hidden = false;
    alert.scrollIntoView({ block: 'nearest' });
  };

  $('[data-newcat-save]', form).addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const title = field('category_title').value.trim();
    setError('category_title', '');
    if (title === '') { setError('category_title', 'Nama kategori wajib diisi.'); field('category_title').focus(); return; }
    const body = new FormData();
    body.append('_token', field('_token').value);
    body.append('category_title', title);
    body.append('category_icon', $('input[name="category_icon"]:checked', form)?.value ?? 'folder-open');
    button.disabled = true;
    try {
      const { status, data } = await post(form.dataset.sectionAction, body);
      if (status === 200 && data.section) {
        const option = new Option(data.section.title, String(data.section.id), true, true);
        field('section_id').add(option, field('section_id').options.length - 1);
        field('section_id').dataset.last = option.value;
        newCat.hidden = true;
        createdSection = true;
        showToast('Kategori dibuat', `${data.section.title} tampil sebagai bagian baru di dashboard.`);
        field('title').focus();
      } else if (status === 422 && data.errors) {
        setError('category_title', data.errors.category_title ?? '');
        field('category_title').focus();
      } else showFormError(failMessage(status, data));
    } catch { showFormError('Koneksi terputus. Periksa jaringan lalu coba lagi.'); }
    finally { button.disabled = false; }
  });

  const validate = () => {
    const errors = {};
    const title = field('title').value.trim();
    if (field('section_id').value === '__new') errors.section_id = 'Isi Nama Kategori lalu klik Simpan kategori.';
    if (title === '') errors.title = 'Judul wajib diisi.';
    if (!validUrl(field('url').value.trim())) errors.url = 'Gunakan alamat lengkap yang diawali http:// atau https://';
    if (iconSource() === 'list' && !$('input[name="icon"]:checked', iconGrid)) errors.icon = 'Pilih icon dari daftar.';
    if (iconSource() === 'image' && !field('icon_file').files[0] && !editing?.image) errors.icon_file = 'Pilih gambar PNG, JPG, atau WebP.';
    return errors;
  };
  const showErrors = (errors) => {
    Object.entries(errors).forEach(([name, message]) => setError(name, message));
    const first = ['section_id', 'category_title', 'title', 'description', 'url', 'icon', 'icon_file'].find((name) => errors[name]);
    if (first === 'section_id' && field('section_id').value === '__new') field('category_title').focus();
    else if (first === 'icon') ($('input[name="icon"]:checked', iconGrid) ?? $('input[name="icon"]', iconGrid)).focus();
    else if (first) field(first).focus();
  };

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearErrors();
    const errors = validate();
    if (Object.keys(errors).length) { showErrors(errors); return; }
    const submit = $('[data-submit]', form);
    submit.disabled = true;
    form.setAttribute('aria-busy', 'true');
    try {
      const body = new FormData(form);
      if (iconSource() === 'list') body.delete('icon_file');
      const { status, data } = await post(form.action, body);
      if (status === 200 && data.redirect) { createdSection = false; window.location.assign(data.redirect); return; }
      if (status === 422 && data.errors) showErrors(data.errors);
      else showFormError(failMessage(status, data));
    } catch { showFormError('Koneksi terputus. Periksa jaringan lalu coba lagi.'); }
    submit.disabled = false;
    form.removeAttribute('aria-busy');
  });
})();
