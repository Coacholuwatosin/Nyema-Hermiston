/* ===================================================
   BOOKSCAROUSEL.JS — Books page enhancements
   Handles the 3D tilt effect on book covers
   =================================================== */

(function () {
  const bookCards = document.querySelectorAll('.bookCard');

  bookCards.forEach(card => {
    const cover = card.querySelector('.bookCardCover');
    if (!cover) return;

    card.addEventListener('mousemove', (e) => {
      if (window.innerWidth < 992) return;
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;
      const rotateY = ((x - centerX) / centerX) * 6;
      const rotateX = -((y - centerY) / centerY) * 3;
      cover.style.transform = `perspective(800px) rotateY(${rotateY}deg) rotateX(${rotateX}deg)`;
    });

    card.addEventListener('mouseleave', () => {
      cover.style.transform = '';
    });
  });

})();
