(() => {
  'use strict';

  const MOCK_DELAY_MS = 800;

  /**
   * Authentication service. The form only talks to authService.login(), so the
   * backend can be connected here without touching the form logic.
   *
   * TODO(auth): replace the mock with the real request once POST /login exists, e.g.
   *   const response = await fetch(endpoint, {
   *     method: 'POST',
   *     headers: { Accept: 'application/json', 'X-CSRF-Token': csrfToken },
   *     body: new URLSearchParams({ username, password }),
   *     credentials: 'same-origin',
   *   });
   *   const body = await response.json();
   *   return response.ok ? { ok: true, redirect: body.redirect } : { ok: false, message: body.message };
   */
  const authService = {
    async login({ endpoint, username, password }) {
      await new Promise((resolve) => window.setTimeout(resolve, MOCK_DELAY_MS));
      return { ok: true };
    },
  };

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
    submitLabel.textContent = submitting ? 'Memproses…' : 'Sign In';
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

  form.addEventListener('submit', async (event) => {
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

    setSubmitting(true);
    try {
      const result = await authService.login({
        endpoint: form.action,
        username: state.username.trim(),
        password: state.password,
      });
      if (!result.ok) throw new Error(result.message);
      // Keep the loading state while the browser navigates to the dashboard.
      window.location.assign(result.redirect || form.dataset.redirect);
    } catch (error) {
      setSubmitting(false);
      showFormError(error.message || 'Login gagal. Periksa kembali username dan password Anda.');
    }
  });

  // Reset the button if the page is restored from the back/forward cache.
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) setSubmitting(false);
  });
})();
