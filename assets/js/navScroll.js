/* ===================================================
   NAVSCROLL.JS — Nav background on scroll
   =================================================== */

(function () {
  const nav = document.getElementById('siteNav');
  if (!nav) return;

  const SCROLL_THRESHOLD = 80;

  const onScroll = () => {
    if (window.scrollY > SCROLL_THRESHOLD) {
      nav.classList.add('scrolled');
    } else {
      nav.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();
