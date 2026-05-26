/* ===================================================
   FORMHANDLER.JS — Contact Form Validation & Submit
   =================================================== */

(function () {
  const form = document.getElementById('contactForm');
  if (!form) return;

  /* ── Pre-fill from URL params (e.g. ?type=media&subject=press-kit) ── */
  (function prefillFromUrl() {
    var params     = new URLSearchParams(window.location.search);
    var type       = params.get('type');
    var subject    = params.get('subject');
    var typeSelect = form.querySelector('[name="enquiryType"]');
    var msgField   = form.querySelector('[name="message"]');

    if (type && typeSelect) {
      var opt = typeSelect.querySelector('option[value="' + type + '"]');
      if (opt) typeSelect.value = type;
    }

    var messages = {
      'press-kit':    'Hi Nyema,\n\nI would like to request your full press kit (PDF) for editorial use. Please let me know the best way to receive it.\n\nThank you.',
      'author-photo': 'Hi Nyema,\n\nI would like to request a high-resolution author photo cleared for editorial use. Please let me know what formats are available.\n\nThank you.',
      'book-covers':  'Hi Nyema,\n\nI would like to request high-resolution book cover images for all four titles for editorial use. Please let me know how to access them.\n\nThank you.'
    };

    if (subject && msgField && messages[subject] && !msgField.value.trim()) {
      msgField.value = messages[subject];
    }
  })();

  /* ── Show/hide the error message under a field ── */
  const showError = (fieldId, show) => {
    const err = document.getElementById(fieldId + 'Error');
    if (err) err.classList.toggle('visible', show);
  };

  /* ── Add/remove the error border on the field itself ── */
  const markField = (el, hasError) => {
    if (el) el.classList.toggle('formControlError', hasError);
  };

  /* ── Validate a single field by its name attribute ──
     Returns true = valid, false = invalid.
     Only shows errors for fields the user has actually touched
     (called on blur/input/change). When called from the submit
     handler it validates everything regardless.               ── */
  const validateField = (fieldName) => {
    const el = form.querySelector('[name="' + fieldName + '"]');
    if (!el) return true;

    let ok = true;

    switch (fieldName) {

      case 'fullName':
        ok = el.value.trim().length >= 2;
        showError('fullName', !ok);
        markField(el, !ok);
        break;

      case 'email':
        /* Empty → "required" message; filled but wrong → "valid email" message */
        if (el.value.trim() === '') {
          ok = false;
          showError('email', true);
          markField(el, true);
        } else {
          ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim());
          showError('email', !ok);
          markField(el, !ok);
        }
        break;

      case 'enquiryType':
        ok = el.value !== '';
        showError('enquiryType', !ok);
        markField(el, !ok);
        break;

      case 'message':
        ok = el.value.trim().length >= 10;
        showError('message', !ok);
        markField(el, !ok);
        break;
    }

    return ok;
  };

  /* ── Run all fields — used on submit ── */
  const validateAll = () => {
    return ['fullName', 'email', 'enquiryType', 'message']
      .map(validateField)
      .every(Boolean);
  };

  /* ── Live feedback per field ──
     • blur  → validate just this field (first time the user leaves it)
     • input → validate as the user types (clears error while correcting)
     • change → handles <select> which fires change, not input            ── */
  form.querySelectorAll('.formControl').forEach(field => {
    field.addEventListener('blur',   () => { if (field.name) validateField(field.name); });
    field.addEventListener('input',  () => { if (field.name) validateField(field.name); });
    field.addEventListener('change', () => { if (field.name) validateField(field.name); });
  });

  /* ── Form submit ── */
  form.addEventListener('submit', (e) => {
    e.preventDefault();

    /* Validate everything — show all errors at once if multiple fields empty */
    if (!validateAll()) return;

    const submitBtn  = form.querySelector('[type="submit"]');
    const networkErr = document.getElementById('formNetworkError');

    if (submitBtn) {
      submitBtn.disabled    = true;
      submitBtn.textContent = 'Sending…';
    }
    if (networkErr) networkErr.classList.remove('visible');

    fetch('https://formspree.io/f/mlgvpozb', {
      method:  'POST',
      headers: { 'Accept': 'application/json' },
      body:    new FormData(form)
    })
    .then(function (res) {
      if (res.ok) {
        form.style.display = 'none';
        const success = document.getElementById('formSuccess');
        if (success) success.classList.add('visible');
      } else {
        throw new Error('Server responded with ' + res.status);
      }
    })
    .catch(function () {
      if (submitBtn) {
        submitBtn.disabled    = false;
        submitBtn.textContent = 'Send Message';
      }
      if (networkErr) networkErr.classList.add('visible');
    });
  });

})();
