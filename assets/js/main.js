/* ===================================================
   MAIN.JS — Core Site Functionality
   =================================================== */

document.addEventListener('DOMContentLoaded', () => {

  /* ---------- Page Loader ---------- */
  const loader = document.getElementById('pageLoader');
  if (loader) {
    setTimeout(() => {
      loader.classList.add('loaded');
    }, 1200);
  }

  /* ---------- Scroll Progress Bar ---------- */
  const progressBar = document.getElementById('scrollProgressBar');
  if (progressBar) {
    const updateProgress = () => {
      const scrollTop = window.scrollY;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      progressBar.style.width = `${progress}%`;
    };
    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  /* ---------- Custom Cursor ---------- */
  const cursor = document.getElementById('customCursor');
  if (cursor && window.matchMedia('(pointer: fine)').matches) {
    document.body.classList.add('hasCursor');
    let cursorX = 0, cursorY = 0;
    let raf;

    document.addEventListener('mousemove', (e) => {
      cursorX = e.clientX;
      cursorY = e.clientY;
      if (!raf) {
        raf = requestAnimationFrame(moveCursor);
      }
    });

    function moveCursor() {
      cursor.style.left = cursorX + 'px';
      cursor.style.top = cursorY + 'px';
      raf = null;
    }

    const hoverTargets = document.querySelectorAll('a, button, [role="button"], .reviewsBtn, .reviewsDot');
    hoverTargets.forEach(el => {
      el.addEventListener('mouseenter', () => cursor.classList.add('hovered'));
      el.addEventListener('mouseleave', () => cursor.classList.remove('hovered'));
    });

    document.addEventListener('mouseleave', () => {
      cursor.style.opacity = '0';
    });

    document.addEventListener('mouseenter', () => {
      cursor.style.opacity = '1';
    });
  } else if (cursor) {
    cursor.style.display = 'none';
  }

  /* ---------- AOS Initialisation ---------- */
  if (typeof AOS !== 'undefined') {
    AOS.init({
      duration: 800,
      easing: 'ease-out-cubic',
      once: true,
      offset: 80
    });
  }

  /* ---------- Mobile Nav ---------- */
  const hamburger = document.getElementById('navHamburger');
  const overlay = document.getElementById('navOverlay');
  const overlayClose = document.getElementById('navOverlayClose');
  const overlayLinks = document.querySelectorAll('.navOverlayLink');

  if (hamburger && overlay) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      overlay.classList.toggle('open');
      const isOpen = overlay.classList.contains('open');
      hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });
  }

  if (overlayClose) {
    overlayClose.addEventListener('click', () => {
      hamburger.classList.remove('open');
      overlay.classList.remove('open');
      hamburger.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    });
  }

  overlayLinks.forEach(link => {
    link.addEventListener('click', () => {
      if (hamburger) {
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      }
      if (overlay) overlay.classList.remove('open');
      document.body.style.overflow = '';
    });
  });

  /* ---------- Active Nav Link ---------- */
  const currentPath = window.location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.navLink, .navOverlayLink').forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentPath || (currentPath === '' && href === 'index.html')) {
      link.classList.add('active');
    }
  });

  /* ---------- Back to Top ---------- */
  const backToTop = document.getElementById('backToTop');
  if (backToTop) {
    window.addEventListener('scroll', () => {
      backToTop.classList.toggle('visible', window.scrollY > 400);
    }, { passive: true });
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------- Newsletter Form ---------- */
  const newsletterForm = document.getElementById('newsletterForm');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const emailInput = newsletterForm.querySelector('input[type="email"]');
      const successMsg = document.getElementById('newsletterSuccess');
      if (emailInput && emailInput.value) {
        newsletterForm.style.display = 'none';
        if (successMsg) successMsg.classList.add('visible');
      }
    });
  }

});
