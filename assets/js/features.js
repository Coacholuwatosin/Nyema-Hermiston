/* ===================================================
   FEATURES.JS — Count-Up, Parallax, Typewriter,
   Lightbox, Cookie Banner, Sticky Bar,
   FAQ Accordion, Web Share
   =================================================== */

(function () {
  'use strict';

  /* ===================================================
     COUNT-UP ANIMATION
  =================================================== */
  function easeOutQuart(t) {
    return 1 - Math.pow(1 - t, 4);
  }

  function animateCountUp(el) {
    const target = parseFloat(el.dataset.countup);
    const suffix = el.dataset.suffix || '';
    const decimals = el.dataset.decimals ? parseInt(el.dataset.decimals) : 0;
    const duration = 1800;
    let start = null;

    function step(ts) {
      if (!start) start = ts;
      const elapsed = ts - start;
      const progress = Math.min(elapsed / duration, 1);
      const value = easeOutQuart(progress) * target;
      el.textContent = value.toFixed(decimals) + suffix;
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target.toFixed(decimals) + suffix;
    }

    requestAnimationFrame(step);
  }

  function initCountUp() {
    const els = document.querySelectorAll('[data-countup]');
    if (!els.length) return;

    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting && !entry.target.dataset.counted) {
          entry.target.dataset.counted = 'true';
          animateCountUp(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    els.forEach(function (el) { observer.observe(el); });
  }

  /* ===================================================
     PARALLAX — HERO BACKGROUND
  =================================================== */
  function initParallax() {
    const hero = document.querySelector('.heroSection');
    if (!hero) return;

    let ticking = false;

    function onScroll() {
      if (!ticking) {
        requestAnimationFrame(function () {
          const scrollY = window.scrollY;
          const rate = scrollY * 0.35;
          hero.style.backgroundPositionY = 'calc(top + ' + rate + 'px)';
          ticking = false;
        });
        ticking = true;
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ===================================================
     TYPEWRITER EFFECT — HERO SUBHEADING
  =================================================== */
  function initTypewriter() {
    const el = document.querySelector('[data-typewriter]');
    if (!el) return;

    const original = el.dataset.typewriter;
    el.textContent = '';

    let i = 0;
    const interval = 22;

    function type() {
      if (i <= original.length) {
        el.textContent = original.slice(0, i);
        i++;
        setTimeout(type, interval);
      }
    }

    setTimeout(type, 1400);
  }

  /* ===================================================
     LIGHTBOX
  =================================================== */
  function initLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox) return;

    const lightboxImg = lightbox.querySelector('.lightboxImg');
    const closeBtn = lightbox.querySelector('.lightboxClose');
    const triggers = document.querySelectorAll('.lightboxTrigger');

    function openLightbox(src, alt) {
      lightboxImg.src = src;
      lightboxImg.alt = alt || '';
      lightbox.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.classList.remove('open');
      document.body.style.overflow = '';
      setTimeout(function () { lightboxImg.src = ''; }, 350);
    }

    triggers.forEach(function (trigger) {
      trigger.addEventListener('click', function () {
        const img = trigger.tagName === 'IMG' ? trigger : trigger.querySelector('img');
        if (img) openLightbox(img.src, img.alt);
      });
    });

    closeBtn.addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && lightbox.classList.contains('open')) closeLightbox();
    });
  }

  /* ===================================================
     COOKIE CONSENT BANNER
  =================================================== */
  function initCookieBanner() {
    const banner = document.getElementById('cookieBanner');
    if (!banner) return;

    if (localStorage.getItem('nhCookieConsent')) return;

    setTimeout(function () {
      banner.classList.add('visible');
    }, 2000);

    const acceptBtn = banner.querySelector('.cookieBannerAccept');
    const declineBtn = banner.querySelector('.cookieBannerDecline');

    function dismiss(value) {
      banner.classList.remove('visible');
      localStorage.setItem('nhCookieConsent', value);
    }

    if (acceptBtn) acceptBtn.addEventListener('click', function () { dismiss('accepted'); });
    if (declineBtn) declineBtn.addEventListener('click', function () { dismiss('declined'); });
  }

  /* ===================================================
     STICKY BUY BAR
  =================================================== */
  function initStickyBuyBar() {
    const bar = document.getElementById('stickyBuyBar');
    if (!bar) return;

    const hero = document.querySelector('.heroSection');
    const closeBtn = bar.querySelector('.stickyBuyBarClose');
    let dismissed = false;
    let ticking = false;

    function onScroll() {
      if (dismissed) return;
      if (!ticking) {
        requestAnimationFrame(function () {
          if (hero) {
            const heroBottom = hero.getBoundingClientRect().bottom;
            if (heroBottom < 0) {
              bar.classList.add('visible');
            } else {
              bar.classList.remove('visible');
            }
          }
          ticking = false;
        });
        ticking = true;
      }
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        dismissed = true;
        bar.classList.remove('visible');
      });
    }

    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* ===================================================
     FAQ ACCORDION
  =================================================== */
  function initFaq() {
    const items = document.querySelectorAll('.faqItem');
    if (!items.length) return;

    items.forEach(function (item) {
      const question = item.querySelector('.faqQuestion');
      if (!question) return;

      question.addEventListener('click', function () {
        const isOpen = item.classList.contains('open');

        items.forEach(function (i) { i.classList.remove('open'); });

        if (!isOpen) item.classList.add('open');
      });
    });
  }

  /* ===================================================
     WEB SHARE BUTTON
  =================================================== */
  function initShare() {
    const btn = document.getElementById('shareBtn');
    if (!btn) return;

    if (navigator.share) {
      btn.style.display = 'inline-flex';

      btn.addEventListener('click', function () {
        navigator.share({
          title: document.title,
          url: window.location.href
        }).catch(function () {});
      });
    }
  }

  /* ===================================================
     BUY NOW DROPDOWN
  =================================================== */
  function initBuyNow() {
    var btns = document.querySelectorAll('.buyNowBtn');
    if (!btns.length) return;

    var currentOpen = null;

    function closeAll() {
      if (!currentOpen) return;
      var d = currentOpen.dropdown;
      var b = currentOpen.btn;

      d.classList.remove('open');
      b.setAttribute('aria-expanded', 'false');

      /* Wipe inline positioning */
      d.style.cssText = '';

      /* Restore dropdown to its original place in the DOM */
      if (d._origParent) {
        if (d._origNext) {
          d._origParent.insertBefore(d, d._origNext);
        } else {
          d._origParent.appendChild(d);
        }
        d._origParent = null;
        d._origNext = null;
      }

      currentOpen = null;
    }

    btns.forEach(function(btn) {
      /* Capture the sibling reference once at init — it stays valid
         because closeAll() always restores the dropdown to its
         original DOM position before the next click resolves. */
      var dropdown = btn.nextElementSibling;

      btn.addEventListener('click', function(e) {
        e.stopPropagation();

        var isOpen = currentOpen && currentOpen.dropdown === dropdown;
        closeAll();
        if (isOpen) return;

        /* Remember where dropdown lives so we can put it back */
        dropdown._origParent = dropdown.parentNode;
        dropdown._origNext = dropdown.nextSibling || null;

        /* Move to <body> so clip-path / overflow on any ancestor
           cannot clip or shift the panel */
        document.body.appendChild(dropdown);

        /* Position flush under (or above) the button */
        var rect = btn.getBoundingClientRect();
        var isUp = dropdown.classList.contains('buyNowDropdownUp');

        dropdown.style.position = 'fixed';
        dropdown.style.zIndex  = '99999';
        dropdown.style.margin  = '0';
        dropdown.style.left    = rect.left + 'px';
        dropdown.style.minWidth = Math.max(210, rect.width) + 'px';

        if (isUp) {
          dropdown.style.top    = 'auto';
          dropdown.style.bottom = (window.innerHeight - rect.top + 7) + 'px';
        } else {
          dropdown.style.top    = (rect.bottom + 7) + 'px';
          dropdown.style.bottom = 'auto';
        }

        dropdown.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        currentOpen = { btn: btn, dropdown: dropdown };
      });
    });

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeAll();
    });
    window.addEventListener('scroll', closeAll, { passive: true });
    window.addEventListener('resize', closeAll, { passive: true });
  }

  /* ===================================================
     NEWSLETTER POPUP
  =================================================== */
  function initNewsletterPopup() {
    var popup = document.getElementById('newsletterPopup');
    if (!popup) return;
    if (sessionStorage.getItem('nhPopupSeen')) return;

    var overlay  = document.getElementById('newsletterPopupOverlay');
    var closeBtn = document.getElementById('newsletterPopupClose');
    var skipBtn  = document.getElementById('newsletterPopupSkip');
    var form     = document.getElementById('newsletterPopupForm');
    var shown    = false;

    function showPopup() {
      if (shown) return;
      shown = true;
      sessionStorage.setItem('nhPopupSeen', '1');
      popup.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function hidePopup() {
      popup.classList.remove('active');
      document.body.style.overflow = '';
    }

    setTimeout(showPopup, 45000);

    document.addEventListener('mouseleave', function(e) {
      if (e.clientY <= 0) showPopup();
    });

    if (closeBtn) closeBtn.addEventListener('click', hidePopup);
    if (overlay)  overlay.addEventListener('click', hidePopup);
    if (skipBtn)  skipBtn.addEventListener('click', hidePopup);

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') hidePopup();
    });

    if (form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        var emailInput = form.querySelector('input[type="email"]');
        var submitBtn  = form.querySelector('[type="submit"]');
        if (!emailInput || !emailInput.value.trim()) return;
        if (submitBtn) { submitBtn.textContent = 'Subscribing…'; submitBtn.disabled = true; }

        var MC_URL = 'https://karunahealthcare.us14.list-manage.com/subscribe/post-json' +
                     '?u=4dffaa10c992a6fc6cf288f15&id=7511719bf3&f_id=0077b5e5f0';
        var callbackName = 'mcPopupCallback_' + Date.now();
        var url = MC_URL + '&EMAIL=' + encodeURIComponent(emailInput.value.trim()) + '&c=' + callbackName;

        window[callbackName] = function(data) {
          var successMsg = document.getElementById('newsletterPopupSuccess');
          if (data.result === 'success') {
            form.style.display = 'none';
            if (successMsg) successMsg.classList.add('visible');
          } else {
            var msg = data.msg || '';
            if (submitBtn) {
              submitBtn.textContent = msg.toLowerCase().indexOf('already') > -1
                ? 'Already subscribed'
                : 'Try again';
              submitBtn.disabled = false;
            }
          }
          var s = document.getElementById(callbackName);
          if (s) s.parentNode.removeChild(s);
          delete window[callbackName];
        };

        var script = document.createElement('script');
        script.id  = callbackName;
        script.src = url;
        document.body.appendChild(script);
      });
    }
  }

  /* ===================================================
     INIT ALL
  =================================================== */
  function init() {
    initCountUp();
    initBuyNow();
    initParallax();
    initTypewriter();
    initLightbox();
    initCookieBanner();
    initStickyBuyBar();
    initFaq();
    initShare();
    initNewsletterPopup();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
