/* ===================================================
   FORMHANDLER.JS — Contact Form Validation & Submit
   =================================================== */

(function () {
  const form = document.getElementById('contactForm');
  if (!form) return;

  const showError = (fieldId, show) => {
    const err = document.getElementById(fieldId + 'Error');
    if (err) err.classList.toggle('visible', show);
  };

  const validate = () => {
    let valid = true;

    const name = form.querySelector('[name="fullName"]');
    const email = form.querySelector('[name="email"]');
    const message = form.querySelector('[name="message"]');

    if (name) {
      const ok = name.value.trim().length >= 2;
      showError('fullName', !ok);
      if (!ok) valid = false;
    }

    if (email) {
      const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
      showError('email', !ok);
      if (!ok) valid = false;
    }

    if (message) {
      const ok = message.value.trim().length >= 10;
      showError('message', !ok);
      if (!ok) valid = false;
    }

    return valid;
  };

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!validate()) return;

    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending…';
    }

    /* Simulated submit — replace with real endpoint */
    setTimeout(() => {
      form.style.display = 'none';
      const success = document.getElementById('formSuccess');
      if (success) success.classList.add('visible');
    }, 800);
  });

  /* Live validation on blur */
  form.querySelectorAll('.formControl').forEach(field => {
    field.addEventListener('blur', validate);
  });

})();
