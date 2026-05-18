/* ===================================================
   TRUSTBARSCROLL.JS — Handles trust bar marquee
   Pure CSS animation used; this file ensures the
   duplicate content is cloned for seamless looping.
   =================================================== */

(function () {
  const content = document.querySelector('.trustBarContent');
  if (!content) return;

  /* Clone and append for seamless infinite scroll */
  const clone = content.cloneNode(true);
  content.parentElement.appendChild(clone);
})();
