/** Scroll-triggered animations, counter animation, parallax effect.*/

(function () {
  'use strict';

  // Parallax Hero 
  function initParallax() {
    const heroBg = document.querySelector('.hero-bg');
    if (!heroBg) return;

    let rafId = null;
    window.addEventListener('scroll', () => {
      if (rafId) return;
      rafId = requestAnimationFrame(() => {
        const scrollY = window.scrollY;
        heroBg.style.transform = `scale(1) translateY(${scrollY * 0.3}px)`;
        rafId = null;
      });
    }, { passive: true });
  }

  // Counter Animation 
  function animateCounter(el, target, duration = 1800) {
    const start = performance.now();
    const suffix = el.dataset.suffix || '';

    function update(time) {
      const elapsed = time - start;
      const progress = Math.min(elapsed / duration, 1);
      // Ease out cubic
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(eased * target) + suffix;
      if (progress < 1) requestAnimationFrame(update);
      else el.textContent = target + suffix;
    }

    requestAnimationFrame(update);
  }

  function initCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.dataset.counted) {
          entry.target.dataset.counted = 'true';
          const target = parseInt(entry.target.dataset.counter, 10);
          animateCounter(entry.target, target);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(el => observer.observe(el));
  }

  // Stagger Cards on scroll 
  function initStaggerCards() {
    const grids = document.querySelectorAll('.services-grid, .packages-grid');
    grids.forEach(grid => {
      const cards = grid.querySelectorAll('.service-card, .package-card');
      cards.forEach((card, i) => {
        card.classList.add('reveal');
        card.style.transitionDelay = `${i * 0.1}s`;
        if (window.revealObserver) window.revealObserver.observe(card);
      });
    });
  }

  // Hero Image Slider 
  function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots   = document.querySelectorAll('.hero-dot');
    const prev   = document.querySelector('.hero-arrow-prev');
    const next   = document.querySelector('.hero-arrow-next');
    if (!slides.length) return;

    let current = 0;
    let timer   = null;
    const INTERVAL = 5000;

    function goTo(index, dir = 'next') {
      const prev_idx = current;
      slides[prev_idx].classList.remove('active');
      slides[prev_idx].classList.add('exit-left');

      current = (index + slides.length) % slides.length;
      slides[current].style.transform = dir === 'next' ? 'translateX(100%)' : 'translateX(-100%)';
      slides[current].classList.add('active');

      // Force reflow then animate in
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          slides[current].style.transform = '';
        });
      });

      setTimeout(() => {
        slides[prev_idx].classList.remove('exit-left');
        slides[prev_idx].style.transform = '';
      }, 950);

      dots.forEach((d, i) => d.classList.toggle('active', i === current));
    }

    function startAuto() {
      clearInterval(timer);
      timer = setInterval(() => goTo(current + 1, 'next'), INTERVAL);
    }

    // Arrow buttons
    next?.addEventListener('click', () => { goTo(current + 1, 'next'); startAuto(); });
    prev?.addEventListener('click', () => { goTo(current - 1, 'prev'); startAuto(); });

    // Dot buttons
    dots.forEach((dot, i) => {
      dot.addEventListener('click', () => { goTo(i, i > current ? 'next' : 'prev'); startAuto(); });
    });

    // Pause on hover
    document.querySelector('.hero')?.addEventListener('mouseenter', () => clearInterval(timer));
    document.querySelector('.hero')?.addEventListener('mouseleave', startAuto);

    // Touch/swipe support
    let touchStartX = 0;
    document.querySelector('.hero')?.addEventListener('touchstart', e => {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    document.querySelector('.hero')?.addEventListener('touchend', e => {
      const diff = touchStartX - e.changedTouches[0].screenX;
      if (Math.abs(diff) > 50) {
        diff > 0 ? goTo(current + 1, 'next') : goTo(current - 1, 'prev');
        startAuto();
      }
    }, { passive: true });

    startAuto();
  }

  // Smooth anchor scrolling 
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', (e) => {
        const target = document.querySelector(anchor.getAttribute('href'));
        if (target) {
          e.preventDefault();
          const headerH = document.getElementById('site-header')?.offsetHeight || 80;
          const top = target.getBoundingClientRect().top + window.scrollY - headerH - 20;
          window.scrollTo({ top, behavior: 'smooth' });
        }
      });
    });
  }

  // Form submit animation 
  function initFormFeedback() {
    const form = document.querySelector('.contact-form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const btn = form.querySelector('.form-submit');
      if (!btn) return;

      btn.disabled = true;
      btn.innerHTML = `
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        Message Sent!`;
      btn.style.background = 'var(--green-primary)';

      setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = `Send Message
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="22" y1="2" x2="11" y2="13"/>
            <polygon points="22 2 15 22 11 13 2 9 22 2"/>
          </svg>`;
        btn.style.background = '';
        form.reset();
      }, 3000);
    });
  }

  // Navbar active link highlight on scroll 
  function initScrollSpy() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-links a');
    if (!sections.length || !navLinks.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${entry.target.id}`) {
              link.classList.add('active');
            }
          });
        }
      });
    }, { threshold: 0.4 });

    sections.forEach(s => observer.observe(s));
  }

  // Init 
  function init() {
    initHeroSlider();
    initParallax();
    initCounters();
    initSmoothScroll();
    initFormFeedback();
    initScrollSpy();
    setTimeout(initStaggerCards, 50);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
