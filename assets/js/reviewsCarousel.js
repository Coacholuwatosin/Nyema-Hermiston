/* ===================================================
   REVIEWSCAROUSEL.JS — Reader Reviews Carousel
   =================================================== */

(function () {
  const track = document.getElementById('reviewsTrack');
  if (!track) return;

  const cards = Array.from(track.querySelectorAll('.reviewCard'));
  const prevBtn = document.getElementById('reviewsPrev');
  const nextBtn = document.getElementById('reviewsNext');
  const dotsContainer = document.getElementById('reviewsDots');

  let currentIndex = 0;
  let autoPlayTimer = null;
  let isVisible = true;
  let isPausedByHover = false;

  const AUTO_DELAY = 4500;

  const getCardWidth = () => {
    const card = cards[0];
    if (!card) return 0;
    const gap = parseFloat(window.getComputedStyle(track).columnGap) || 0;
    return card.offsetWidth + gap;
  };

  const totalSlides = cards.length;

  /* Build dots */
  if (dotsContainer) {
    cards.forEach((_, i) => {
      const dot = document.createElement('button');
      dot.className = 'reviewsDot' + (i === 0 ? ' active' : '');
      dot.setAttribute('aria-label', `Review ${i + 1}`);
      dot.addEventListener('click', () => { goTo(i); resetAutoPlay(); });
      dotsContainer.appendChild(dot);
    });
  }

  const updateDots = () => {
    if (!dotsContainer) return;
    dotsContainer.querySelectorAll('.reviewsDot').forEach((dot, i) => {
      dot.classList.toggle('active', i === currentIndex);
    });
  };

  const goTo = (index) => {
    currentIndex = ((index % totalSlides) + totalSlides) % totalSlides;
    track.style.transform = `translateX(${-currentIndex * getCardWidth()}px)`;
    updateDots();
  };

  const next = () => goTo(currentIndex + 1);
  const prev = () => goTo(currentIndex - 1);

  /* Always clear before setting — prevents stacked intervals */
  const startAutoPlay = () => {
    clearTimeout(autoPlayTimer);
    if (!isVisible || isPausedByHover) return;
    autoPlayTimer = setTimeout(() => {
      next();
      startAutoPlay();
    }, AUTO_DELAY);
  };

  const stopAutoPlay = () => clearTimeout(autoPlayTimer);

  const resetAutoPlay = () => {
    stopAutoPlay();
    startAutoPlay();
  };

  if (nextBtn) nextBtn.addEventListener('click', () => { next(); resetAutoPlay(); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prev(); resetAutoPlay(); });

  /* Pause on hover */
  track.addEventListener('mouseenter', () => {
    isPausedByHover = true;
    stopAutoPlay();
  });
  track.addEventListener('mouseleave', () => {
    isPausedByHover = false;
    startAutoPlay();
  });

  /* Touch / swipe — pause during drag, resume after */
  let startX = 0;
  let dragOffset = 0;

  track.addEventListener('touchstart', (e) => {
    startX = e.touches[0].clientX;
    dragOffset = 0;
    stopAutoPlay();
  }, { passive: true });

  track.addEventListener('touchmove', (e) => {
    dragOffset = e.touches[0].clientX - startX;
  }, { passive: true });

  track.addEventListener('touchend', () => {
    if (Math.abs(dragOffset) > 50) {
      dragOffset > 0 ? prev() : next();
    }
    startAutoPlay();
  });

  /* Pause when section scrolls out of view */
  if ('IntersectionObserver' in window) {
    const section = track.closest('section') || track;
    const observer = new IntersectionObserver((entries) => {
      isVisible = entries[0].isIntersecting;
      isVisible ? startAutoPlay() : stopAutoPlay();
    }, { threshold: 0.2 });
    observer.observe(section);
  }

  /* Pause when tab is hidden */
  document.addEventListener('visibilitychange', () => {
    document.hidden ? stopAutoPlay() : startAutoPlay();
  });

  /* Recalculate position on resize */
  window.addEventListener('resize', () => goTo(currentIndex));

  /* Kick off */
  startAutoPlay();

})();
