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
  let autoPlayInterval;
  let isDragging = false;
  let startX = 0;
  let dragOffset = 0;

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
      dot.addEventListener('click', () => goTo(i));
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
    currentIndex = Math.max(0, Math.min(index, totalSlides - 1));
    const offset = -currentIndex * getCardWidth();
    track.style.transform = `translateX(${offset}px)`;
    updateDots();
  };

  const next = () => goTo(currentIndex < totalSlides - 1 ? currentIndex + 1 : 0);
  const prev = () => goTo(currentIndex > 0 ? currentIndex - 1 : totalSlides - 1);

  if (nextBtn) nextBtn.addEventListener('click', () => { next(); resetAutoPlay(); });
  if (prevBtn) prevBtn.addEventListener('click', () => { prev(); resetAutoPlay(); });

  /* Auto-play */
  const startAutoPlay = () => {
    autoPlayInterval = setInterval(next, 5000);
  };

  const resetAutoPlay = () => {
    clearInterval(autoPlayInterval);
    startAutoPlay();
  };

  startAutoPlay();

  /* Pause on hover */
  track.addEventListener('mouseenter', () => clearInterval(autoPlayInterval));
  track.addEventListener('mouseleave', startAutoPlay);

  /* Touch / swipe support */
  track.addEventListener('touchstart', (e) => {
    startX = e.touches[0].clientX;
    isDragging = true;
    clearInterval(autoPlayInterval);
  }, { passive: true });

  track.addEventListener('touchmove', (e) => {
    if (!isDragging) return;
    dragOffset = e.touches[0].clientX - startX;
  }, { passive: true });

  track.addEventListener('touchend', () => {
    if (Math.abs(dragOffset) > 50) {
      dragOffset > 0 ? prev() : next();
    }
    isDragging = false;
    dragOffset = 0;
    startAutoPlay();
  });

  /* Recalculate on resize */
  window.addEventListener('resize', () => {
    goTo(currentIndex);
  });

})();
