(() => {
  'use strict';

  // Client-side checks only; the form posts to POST /login, which verifies the
  // credentials and redirects (see AuthController::login).
  const form = document.getElementById('login-form');
  if (!form) return;

  const state = { username: '', password: '', submitting: false };
  const touched = new Set();
  const fields = {
    username: {
      input: form.elements.namedItem('username'),
      error: document.getElementById('username-error'),
      validate: (value) => (value.trim() === '' ? 'Username wajib diisi.' : ''),
    },
    password: {
      input: form.elements.namedItem('password'),
      error: document.getElementById('password-error'),
      validate: (value) => (value === '' ? 'Password wajib diisi.' : ''),
    },
  };
  const formError = document.getElementById('login-error');
  const submitButton = form.querySelector('[data-submit]');
  const submitLabel = submitButton.querySelector('[data-submit-label]');
  const submitIcon = submitButton.querySelector('[data-submit-icon]');
  const submitSpinner = submitButton.querySelector('[data-submit-spinner]');

  const showFieldError = (name, message) => {
    const { input, error } = fields[name];
    error.textContent = message;
    error.hidden = message === '';
    input.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
  };

  const showFormError = (message) => {
    formError.textContent = message;
    formError.hidden = message === '';
  };

  const setSubmitting = (submitting) => {
    state.submitting = submitting;
    submitButton.disabled = submitting;
    submitLabel.textContent = submitting ? 'Memproses…' : 'Masuk';
    submitIcon.hidden = submitting;
    submitSpinner.hidden = !submitting;
    form.setAttribute('aria-busy', String(submitting));
    Object.values(fields).forEach(({ input }) => { input.readOnly = submitting; });
  };

  Object.entries(fields).forEach(([name, field]) => {
    field.input.addEventListener('input', () => {
      state[name] = field.input.value;
      if (touched.has(name)) showFieldError(name, field.validate(state[name]));
    });
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (state.submitting) return;
    showFormError('');

    let firstInvalid = null;
    Object.entries(fields).forEach(([name, field]) => {
      // Browser autofill does not always fire input events, so read the live value.
      state[name] = field.input.value;
      touched.add(name);
      const message = field.validate(state[name]);
      showFieldError(name, message);
      if (message !== '' && firstInvalid === null) firstInvalid = field.input;
    });
    if (firstInvalid !== null) {
      firstInvalid.focus();
      return;
    }

    fields.username.input.value = state.username.trim();
    // Keep the loading state while the server checks the credentials and redirects.
    setSubmitting(true);
    form.submit();
  });

  // Reset the button if the page is restored from the back/forward cache.
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) setSubmitting(false);
  });

  // The form is in a window opened by "Masuk" in the header; it opens by itself after a refused login or a notice.
  const dialog = document.getElementById('login-card');
  if (dialog && typeof dialog.showModal === 'function') {
    const open = () => {
      if (!dialog.open) dialog.showModal();
      const target = fields.username.input.value ? fields.password.input : fields.username.input;
      target.focus();
    };
    document.querySelectorAll('[data-open-login]').forEach((button) => button.addEventListener('click', open));
    dialog.querySelectorAll('[data-close-login]').forEach((button) => button.addEventListener('click', () => dialog.close()));
    // A click on the dimmed backdrop (outside the box) closes it, unless a login is being sent.
    dialog.addEventListener('click', (event) => {
      if (event.target !== dialog || state.submitting) return;
      const r = dialog.getBoundingClientRect();
      if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
    });
    dialog.addEventListener('cancel', (event) => { if (state.submitting) event.preventDefault(); });
    if (dialog.hasAttribute('data-autoopen')) open();
  } else if (dialog) {
    dialog.setAttribute('open', ''); // very old browsers: the form simply shows
  }
})();
