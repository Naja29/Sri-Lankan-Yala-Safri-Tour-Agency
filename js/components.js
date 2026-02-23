/** Dynamically loads the shared header and footer into every page.*/

(function () {
  // Resolve base path 
  // Works whether files are at root or in subdirectories
  function getBasePath() {
    const depth = window.location.pathname
      .replace(/\/[^/]*$/, '')
      .split('/')
      .filter(Boolean).length;
    const isLocal = window.location.protocol === 'file:';
    if (isLocal) {
      // Count how deep we are from the root
      const path = window.location.pathname;
      const parts = path.split('/').filter(p => p && p !== 'index.html');
      // If we're in a subfolder like /admin/, go up one level
      if (document.currentScript) {
        const scriptSrc = document.currentScript.src;
        if (scriptSrc.includes('/admin/')) return '../';
      }
      return '';
    }
    return depth > 1 ? '../'.repeat(depth - 1) : '';
  }

  const base = getBasePath();

  // Inject Header 
  function injectHeader() {
    const placeholder = document.getElementById('header-placeholder');
    if (!placeholder) return;

    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navPages = [
      { href: base + 'index.html',    label: 'Home',     file: 'index.html' },
      { href: base + 'packages.html', label: 'Packages', file: 'packages.html' },
      { href: base + 'services.html', label: 'Services', file: 'services.html' },
      { href: base + 'gallery.html',  label: 'Gallery',  file: 'gallery.html' },
      { href: base + 'contact.html',  label: 'Contact',  file: 'contact.html' },
    ];

    const navLinksHTML = navPages.map(p => {
      const isActive = currentPage === p.file || (currentPage === '' && p.file === 'index.html');
      return `<a href="${p.href}" class="${isActive ? 'active' : ''}">${p.label}</a>`;
    }).join('');

    placeholder.outerHTML = `
    <header id="site-header">
      <div class="container">
        <nav class="nav-inner">
          <a href="${base}index.html" class="nav-logo">
            <img src="${base}images/icons/logo.png" alt="YalaSafari" class="nav-logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
            <span class="nav-logo-text" style="display:none">Yala<span class="accent">Safari</span></span>
          </a>
          <div class="nav-links">
            ${navLinksHTML}
            <a href="${base}contact.html" class="nav-book-btn">Book Now</a>
          </div>
          <button class="nav-hamburger" id="navHamburger" aria-label="Toggle menu">
            <span></span><span></span><span></span>
          </button>
        </nav>
      </div>

      <!-- Mobile full-screen menu -->
      <nav class="nav-mobile" id="navMobile">
        <button class="nav-mobile-close" id="navMobileClose" aria-label="Close menu">✕</button>
        ${navPages.map(p => `<a href="${p.href}">${p.label}</a>`).join('')}
        <a href="${base}contact.html" style="color: var(--green-accent); margin-top: 8px;">Book Now →</a>
      </nav>
    </header>`;

    initHeader();
  }

  // Inject Footer 
  function injectFooter() {
    const placeholder = document.getElementById('footer-placeholder');
    if (!placeholder) return;

    placeholder.outerHTML = `
    <footer id="site-footer">
      <!-- Wildlife silhouette image 
      <img
        class="footer-silhouette-img"
        src="${base}images/footer-animals.png"
        alt=""
        aria-hidden="true"
      /> -->

      <div class="footer-main">
        <div class="container">
          <div class="footer-grid">

            <!-- Brand Column -->
            <div class="footer-brand reveal">
              <a href="${base}index.html" class="footer-logo">
                <img src="${base}images/icons/logo.png" alt="YalaSafari" class="footer-logo-img" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'"/>
                <span style="display:none">Yala<span>Safari</span></span>
              </a>
              <p>Your trusted partner for unforgettable wildlife safaris in Yala National Park, Sri Lanka. Experience the wild like never before.</p>
              <div class="footer-socials">
                <!-- Instagram -->
                <a href="#" class="social-link" aria-label="Instagram">
                  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                  </svg>
                </a>
                <!-- Facebook -->
                <a href="#" class="social-link" aria-label="Facebook">
                  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                  </svg>
                </a>
                <!-- YouTube -->
                <a href="#" class="social-link" aria-label="YouTube">
                  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/>
                  </svg>
                </a>
                <!-- WhatsApp -->
                <a href="#" class="social-link" aria-label="WhatsApp">
                  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                  </svg>
                </a>
              </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-links reveal reveal-delay-2">
              <h4>Quick Links</h4>
              <ul>
                <li><a href="${base}index.html">Home</a></li>
                <li><a href="${base}packages.html">Packages</a></li>
                <li><a href="${base}services.html">Services</a></li>
                <li><a href="${base}gallery.html">Gallery</a></li>
                <li><a href="${base}contact.html">Contact</a></li>
              </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-contact reveal reveal-delay-3">
              <h4>Contact Info</h4>
              <ul>
                <li>
                  <div class="contact-icon">
                    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                      <circle cx="12" cy="10" r="3"/>
                    </svg>
                  </div>
                  <div class="contact-text">
                    <strong>Address</strong>
                    Yala Road, Tissamaharama
                  </div>
                </li>
                <li>
                  <div class="contact-icon">
                    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.06 1.18 2 2 0 012.03 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                    </svg>
                  </div>
                  <div class="contact-text">
                    <strong>Phone</strong>
                    +94 77 123 4567
                  </div>
                </li>
                <li>
                  <div class="contact-icon">
                    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                      <polyline points="22,6 12,13 2,6"/>
                    </svg>
                  </div>
                  <div class="contact-text">
                    <strong>Email</strong>
                    info@yalasafari.lk
                  </div>
                </li>
              </ul>
            </div>

          </div>
        </div>
      </div>

      <!-- Footer Bottom Bar -->
      <div class="footer-bottom">
        <div class="container">
          <div class="footer-bottom-inner">
            <p>&copy; ${new Date().getFullYear()} <a href="${base}index.html">YalaSafari</a>. All rights reserved.</p>
            <div class="footer-bottom-links">
              <a href="#">Privacy Policy</a>
              <a href="#">Terms of Service</a>
              <a href="#">Sitemap</a>
            </div>
          </div>
        </div>
      </div>
    </footer>`;

    // Re-trigger reveal on footer elements
    document.querySelectorAll('#site-footer .reveal').forEach(el => {
      revealObserver.observe(el);
    });
  }

  // Header Scroll + Mobile Menu Logic 
  function initHeader() {
    const header = document.getElementById('site-header');
    const hamburger = document.getElementById('navHamburger');
    const mobileNav = document.getElementById('navMobile');
    const mobileClose = document.getElementById('navMobileClose');

    if (!header) return;

    // Scroll effect
    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          header.classList.toggle('scrolled', window.scrollY > 50);
          ticking = false;
        });
        ticking = true;
      }
    });

    // Hamburger toggle
    if (hamburger && mobileNav) {
      hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        mobileNav.classList.toggle('open');
        document.body.style.overflow = mobileNav.classList.contains('open') ? 'hidden' : '';
      });
    }

    if (mobileClose && mobileNav) {
      mobileClose.addEventListener('click', () => {
        hamburger?.classList.remove('open');
        mobileNav.classList.remove('open');
        document.body.style.overflow = '';
      });
    }

    // Close on nav link click
    mobileNav?.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        hamburger?.classList.remove('open');
        mobileNav.classList.remove('open');
        document.body.style.overflow = '';
      });
    });
  }

  // Reveal Observer 
  window.revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  // Init on DOM Ready 
  function init() {
    injectHeader();
    injectFooter();

    // Observe all reveal elements
    document.querySelectorAll('.reveal').forEach(el => {
      revealObserver.observe(el);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
