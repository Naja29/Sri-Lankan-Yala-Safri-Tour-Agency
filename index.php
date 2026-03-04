<?php

//  index.php — Homepage (live from DB)

require_once 'includes/db.php';

$db = getDB();

// Load hero slides 
$db->query("CREATE TABLE IF NOT EXISTS `hero_slides` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `image` VARCHAR(255) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$res        = $db->query("SELECT * FROM hero_slides WHERE status='active' ORDER BY sort_order ASC LIMIT 5");
$heroSlides = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
// Fallback to static images if no DB slides
if (empty($heroSlides)) {
    $heroSlides = [
        ['image' => 'images/hero/hero-1.jpg'],
        ['image' => 'images/hero/hero-2.jpg'],
        ['image' => 'images/hero/hero-3.jpg'],
    ];
}

// Load featured packages (first 3 active) 
$res      = $db->query("SELECT * FROM packages WHERE status='active' ORDER BY created_at ASC LIMIT 3");
$packages = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Load featured services (first 6 active) 
$res      = $db->query("SELECT * FROM services WHERE status='active' ORDER BY created_at ASC LIMIT 6");
$services = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Load approved testimonials 
$db->query("CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100),
  `rating` TINYINT DEFAULT 5,
  `message` TEXT NOT NULL,
  `photo` VARCHAR(255),
  `source` ENUM('website','manual') DEFAULT 'website',
  `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$res          = $db->query("SELECT * FROM testimonials WHERE status='approved' ORDER BY created_at DESC LIMIT 8");
$testimonials = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Load gallery preview (latest 6) 
$res     = $db->query("SELECT * FROM gallery ORDER BY created_at DESC LIMIT 6");
$gallery = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Load settings 
$s = getSettings(['site_name','tagline','about_title','about_lead','about_text','about_image',
                  'phone1','info_email','whatsapp','whatsapp_message',
                  'years_experience','happy_guests','species_spotted','five_star_reviews']);

$pageTitle = ($s['site_name'] ?: 'YalaSafari') . ' – Experience Wild Sri Lanka';
$pageDesc  = 'Your trusted partner for unforgettable wildlife safaris in Yala National Park, Sri Lanka.';
$pageKw    = 'Yala Safari, Sri Lanka safari, wildlife tours, Yala National Park, leopard safari, elephant safari';
$pageCSS   = 'home.css';
require_once 'includes/header.php';
?>


  <!-- HERO -->
  <section class="hero" id="home">
    <div class="hero-slider">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
             style="background-image: url('<?= htmlspecialchars($slide['image']) ?>')"></div>
      <?php endforeach; ?>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-overlay-pattern"></div>

    <div class="container">
      <div class="hero-content">
        <div class="hero-badge">🦁 &nbsp;Yala National Park, Sri Lanka</div>
        <h1 class="hero-title">
          Experience Wild<br/>
          <span>Sri Lanka</span>
        </h1>
        <p class="hero-subtitle">
          Discover the majestic wildlife of Yala National Park. Our expert guides bring you face-to-face with leopards, elephants, and Sri Lanka's most spectacular creatures.
        </p>
        <div class="hero-actions">
          <a href="#packages" class="btn-primary">
            Explore Our Tours
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
          </a>
          <a href="#about" class="hero-btn-secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>
            </svg>
            Learn More
          </a>
        </div>
      </div>
    </div>

    <div class="hero-dots">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <button class="hero-dot <?= $i === 0 ? 'active' : '' ?>"
                data-index="<?= $i ?>" aria-label="Slide <?= $i+1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <button class="hero-arrow hero-arrow-prev" aria-label="Previous slide">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <button class="hero-arrow hero-arrow-next" aria-label="Next slide">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="9 18 15 12 9 6"/>
      </svg>
    </button>
  </section>


  <!-- ABOUT -->
  <section class="about" id="about">
    <div class="container">
      <div class="about-grid">

        <div class="about-image-wrap reveal reveal-left">
          <div class="about-image-main">
            <?php $aboutImg = !empty($s['about_image']) ? $s['about_image'] : 'images/about/about-elephant.jpg'; ?>
            <img src="<?= htmlspecialchars($aboutImg) ?>" alt="About Yala Safari" loading="lazy"
                 onerror="this.src='images/about/about-elephant.jpg'"/>
          </div>
          <div class="about-badge-float">
            <div class="badge-num"><?= htmlspecialchars($s['years_experience'] ?: '15') ?>+</div>
            <div class="badge-text">Years of<br/>Excellence</div>
          </div>
        </div>

        <div class="about-content reveal reveal-right">
          <h2 class="section-title"><?= htmlspecialchars($s['about_title'] ?: 'About Yala Safari Tours') ?></h2>
          <p class="about-lead"><?= htmlspecialchars($s['about_lead'] ?: "Sri Lanka's Most Trusted Safari Experience") ?></p>
          <p class="about-text"><?= nl2br(htmlspecialchars($s['about_text'] ?: "With over 15 years of experience, Yala Safari Tours is your premier gateway to exploring the incredible wildlife of Yala National Park. We specialize in creating unforgettable safari experiences that bring you face-to-face with Sri Lanka's magnificent creatures.")) ?></p>

          <div class="about-features">
            <div class="about-feature">
              <div class="about-feature-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
              </div>Experienced Guides
            </div>
            <div class="about-feature">
              <div class="about-feature-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
              </div>Responsible Tourism
            </div>
            <div class="about-feature">
              <div class="about-feature-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
              </div>Comfortable Vehicles
            </div>
            <div class="about-feature">
              <div class="about-feature-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
              </div>Flexible Packages
            </div>
            <div class="about-feature">
              <div class="about-feature-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
              </div>Best Wildlife Spots
            </div>
            <div class="about-feature">
              <div class="about-feature-icon">
                <svg width="14" height="14" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none" stroke="currentColor"><polyline points="20 6 9 17 4 12"/></svg>
              </div>24/7 Support
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- SERVICES -->
  <section class="services" id="services">
    <div class="container">
      <h2 class="section-title reveal">Our Services</h2>
      <p class="section-subtitle reveal reveal-delay-1">Tailored wildlife experiences for every kind of explorer</p>

      <div class="services-grid">
        <?php if (empty($services)): ?>
          <!-- Fallback static cards if no DB data -->
          <div class="service-card">
            <div class="service-card-img"><img src="images/services/wildlife-safari.jpg" alt="Wildlife Safari Tours" loading="lazy"/></div>
            <div class="service-card-body">
              <h3>Wildlife Safari Tours</h3>
              <p>Experience thrilling safari adventures through Yala National Park with expert guides.</p>
              <a href="services.php" class="service-card-link">Learn More <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></a>
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($services as $svc): ?>
            <div class="service-card reveal">
              <div class="service-card-img">
                <?php if (!empty($svc['image'])): ?>
                  <img src="<?= htmlspecialchars($svc['image']) ?>" alt="<?= htmlspecialchars($svc['name']) ?>" loading="lazy" onerror="this.src='images/services/wildlife-safari.jpg'"/>
                <?php else: ?>
                  <img src="images/services/wildlife-safari.jpg" alt="<?= htmlspecialchars($svc['name']) ?>" loading="lazy"/>
                <?php endif; ?>
              </div>
              <div class="service-card-body">
                <h3><?= htmlspecialchars($svc['name']) ?></h3>
                <p><?= htmlspecialchars(mb_substr($svc['description'] ?? '', 0, 100)) ?>...</p>
                <a href="services.php" class="service-card-link">
                  Learn More
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                  </svg>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div style="text-align:center;margin-top:40px" class="reveal">
        <a href="services.php" class="btn-outline">View All Services</a>
      </div>
    </div>
  </section>


  <!-- PACKAGES -->
  <section class="packages" id="packages">
    <div class="container">
      <h2 class="section-title reveal">Popular Safari Packages</h2>
      <p class="section-subtitle reveal reveal-delay-1">Choose the perfect adventure for you</p>

      <div class="packages-grid">
        <?php if (empty($packages)): ?>
          <p style="text-align:center;color:var(--text-muted);padding:40px 0">No packages available yet.</p>
        <?php else: ?>
          <?php foreach ($packages as $pkg):
            $features = array_filter(array_map('trim', explode("\n", $pkg['features'] ?? '')));
            $isFeatured = !empty($pkg['badge_label']);
            $imgSrc = !empty($pkg['image']) ? htmlspecialchars($pkg['image']) : 'images/packages/full-day.jpg';
          ?>
            <div class="package-card <?= $isFeatured ? 'featured' : '' ?> reveal">
              <?php if ($isFeatured): ?>
                <div class="package-badge"><?= htmlspecialchars($pkg['badge_label']) ?></div>
              <?php endif; ?>

              <div class="package-img">
                <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($pkg['name']) ?>" loading="lazy" onerror="this.src='images/packages/full-day.jpg'"/>
                <div class="package-img-overlay">
                  <div class="package-duration">Duration: <span><?= htmlspecialchars($pkg['duration'] ?: 'Flexible') ?></span></div>
                </div>
              </div>

              <div class="package-body">
                <h3><?= htmlspecialchars($pkg['name']) ?></h3>
                <p><?= htmlspecialchars(mb_substr($pkg['description'] ?? '', 0, 120)) ?>...</p>
                <?php if (!empty($features)): ?>
                  <ul class="package-features">
                    <?php foreach (array_slice($features, 0, 4) as $f): ?>
                      <li><?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>

              <div class="package-footer">
                <div class="package-price">
                  <span class="currency">From</span>
                  <span class="amount">Rs <?= number_format($pkg['price']) ?></span>
                  <span class="per"><?= htmlspecialchars($pkg['price_per'] ?: 'per person') ?></span>
                </div>
                <button class="btn-book" onclick="window.location.href='contact.php?package=<?= urlencode($pkg['name']) ?>#contactForm'">
                  Book Now
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div style="text-align:center;margin-top:48px" class="reveal">
        <a href="packages.php" class="btn-outline">View All Packages</a>
      </div>
    </div>
  </section>


  <!-- STATS -->
  <?php
    $st_years   = (int)($s['years_experience'] ?: 0) ?: 15;
    $st_guests  = (int)($s['happy_guests']     ?: 0) ?: 1200;
    $st_species = (int)($s['species_spotted']  ?: 0) ?: 50;
    $st_reviews = (int)($s['five_star_reviews'] ?: 0) ?: 500;
  ?>
  <section class="stats-section">
    <div class="container">
      <div class="stats-grid">
        <div class="stat-item reveal">
          <div class="stat-number"><span data-counter="<?= $st_years ?>">0</span>+</div>
          <div class="stat-label">Years of Experience</div>
        </div>
        <div class="stat-item reveal reveal-delay-1">
          <div class="stat-number"><span data-counter="<?= $st_guests ?>">0</span>+</div>
          <div class="stat-label">Happy Guests</div>
        </div>
        <div class="stat-item reveal reveal-delay-2">
          <div class="stat-number"><span data-counter="<?= $st_species ?>">0</span>+</div>
          <div class="stat-label">Species Spotted</div>
        </div>
        <div class="stat-item reveal reveal-delay-3">
          <div class="stat-number"><span data-counter="<?= $st_reviews ?>">0</span>+</div>
          <div class="stat-label">5-Star Reviews</div>
        </div>
      </div>
    </div>
  </section>


  <!-- GALLERY PREVIEW -->
  <?php if (!empty($gallery)): ?>
  <section class="gallery-preview">
    <div class="container">
      <h2 class="section-title reveal">Captured in the Wild</h2>
      <p class="section-subtitle reveal reveal-delay-1">A glimpse of what awaits you in Yala National Park</p>

      <div class="gallery-preview-grid">
        <?php foreach ($gallery as $i => $img):
          $delay = $i % 3;
        ?>
          <div class="gallery-preview-item reveal <?= $delay > 0 ? 'reveal-delay-' . $delay : '' ?>">
            <img src="<?= htmlspecialchars($img['image']) ?>"
                 alt="<?= htmlspecialchars($img['name']) ?>"
                 loading="lazy"
                 onerror="this.src='images/gallery/gallery-01.jpg'"/>
            <div class="gallery-preview-overlay">
              <span><?= htmlspecialchars($img['name']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div style="text-align:center;margin-top:40px" class="reveal">
        <a href="gallery.php" class="btn-outline">View Full Gallery</a>
      </div>
    </div>
  </section>
  <?php endif; ?>



  <!-- CTA -->
  <?php
    $ctaTitle    = "Ready to Meet the Wild?";
    $ctaSubtitle = "Book your Yala safari today and experience Sri Lanka's most iconic wildlife up close. Limited slots available daily.";
    require_once 'includes/cta.php';
  ?>



  <!-- TESTIMONIALS -->
  <?php if (!empty($testimonials)):
    $totalReviews = count($testimonials);
    $avgRating    = round(array_sum(array_column($testimonials, 'rating')) / $totalReviews, 1);
  ?>
  <section class="testimonials-section">
    <div class="container">

      <h2 class="section-title reveal">What Our Guests Say</h2>
      <p class="section-subtitle reveal reveal-delay-1">Real experiences from real safari adventurers</p>

      <!-- Rating summary -->
      <div class="testimonials-rating-bar reveal">
        <div class="rating-score"><?= number_format($avgRating, 1) ?></div>
        <div class="rating-right">
          <div class="rating-stars-big">
            <?php for ($i=1;$i<=5;$i++) echo $i <= round($avgRating) ? '★' : '☆'; ?>
          </div>
          <div class="rating-count">Based on <?= $totalReviews ?> verified review<?= $totalReviews > 1 ? 's' : '' ?></div>
        </div>
      </div>

      <!-- Carousel -->
      <div class="testimonials-outer">
        <!-- Prev arrow -->
        <button class="testimonials-arrow arrow-prev" id="tPrev" aria-label="Previous">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </button>

        <div class="testimonials-carousel">
          <div class="testimonials-track" id="testimonialsTrack">
            <?php foreach ($testimonials as $t):
              $parts    = explode(' ', trim($t['name']));
              $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
            ?>
              <div class="testimonial-card">
                <div class="testimonial-quote">❝</div>
                <div class="testimonial-stars">
                  <?php for ($i=1;$i<=5;$i++): ?>
                    <span class="<?= $i <= (int)$t['rating'] ? 'star-filled' : 'star-empty' ?>">★</span>
                  <?php endfor; ?>
                </div>
                <p class="testimonial-text"><?= htmlspecialchars($t['message']) ?></p>
                <div class="testimonial-author">
                  <?php if (!empty($t['photo'])): ?>
                    <img src="<?= htmlspecialchars($t['photo']) ?>" alt="<?= htmlspecialchars($t['name']) ?>"
                         class="testimonial-avatar"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
                    <div class="testimonial-avatar-init" style="display:none"><?= $initials ?></div>
                  <?php else: ?>
                    <div class="testimonial-avatar-init"><?= $initials ?></div>
                  <?php endif; ?>
                  <div>
                    <div class="testimonial-name"><?= htmlspecialchars($t['name']) ?></div>
                    <?php if ($t['location']): ?>
                      <div class="testimonial-location">📍 <?= htmlspecialchars($t['location']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Next arrow -->
        <button class="testimonials-arrow arrow-next" id="tNext" aria-label="Next">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
      </div>

      <!-- Dots -->
      <div class="testimonials-dots" id="tDots"></div>

      <!-- CTA -->
      <div class="testimonials-cta reveal">
        <a href="contact.php#reviewForm" class="btn-primary">
          ⭐ Share Your Experience
        </a>
      </div>

    </div>
  </section>
  <?php endif; ?>

  
<?php require_once 'includes/footer.php'; ?>

<script>
// Hero Slider 
(function() {
  const slides = document.querySelectorAll('.hero-slide');
  const dots   = document.querySelectorAll('.hero-dot');
  let current  = 0;
  let timer;

  function goTo(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    slides[current].classList.add('exit-left');
    current = (index + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
    setTimeout(() => {
      document.querySelectorAll('.hero-slide.exit-left').forEach(s => s.classList.remove('exit-left'));
    }, 800);
    resetTimer();
  }

  function resetTimer() {
    clearInterval(timer);
    timer = setInterval(() => goTo(current + 1), 5000);
  }

  dots.forEach((dot, i) => dot.addEventListener('click', () => goTo(i)));
  document.querySelector('.hero-arrow-prev')?.addEventListener('click', () => goTo(current - 1));
  document.querySelector('.hero-arrow-next')?.addEventListener('click', () => goTo(current + 1));
  resetTimer();
})();
</script>



<script>
// Testimonials Carousel 
(function() {
  const track   = document.getElementById('testimonialsTrack');
  const dotsWrap= document.getElementById('tDots');
  const prevBtn = document.getElementById('tPrev');
  const nextBtn = document.getElementById('tNext');
  if (!track) return;

  const cards = Array.from(track.querySelectorAll('.testimonial-card'));
  let current = 0;
  let timer   = null;

  function getPerView() {
    return window.innerWidth <= 600 ? 1 : window.innerWidth <= 992 ? 2 : 3;
  }

  function totalPages() {
    return Math.max(1, Math.ceil(cards.length / getPerView()));
  }

  function buildDots() {
    dotsWrap.innerHTML = '';
    const pages = totalPages();
    for (let i = 0; i < pages; i++) {
      const d = document.createElement('button');
      d.className = 'testimonial-dot' + (i === current ? ' active' : '');
      d.setAttribute('aria-label', 'Page ' + (i + 1));
      d.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(d);
    }
  }

  function goTo(index) {
    const pages  = totalPages();
    const perView = getPerView();
    current = ((index % pages) + pages) % pages;

    // Each card is exactly 1/perView of the track container width
    // So moving by `current * perView` cards = moving by `current` pages
    const pct = current * perView * (100 / cards.length);
    track.style.transform = `translateX(-${pct}%)`;

    dotsWrap.querySelectorAll('.testimonial-dot').forEach((d, i) => {
      d.classList.toggle('active', i === current);
    });
    resetTimer();
  }

  function resetTimer() {
    clearInterval(timer);
    timer = setInterval(() => goTo(current + 1), 5500);
  }

  prevBtn?.addEventListener('click', () => goTo(current - 1));
  nextBtn?.addEventListener('click', () => goTo(current + 1));

  // Touch / swipe
  let touchX = 0;
  track.addEventListener('touchstart', e => { touchX = e.changedTouches[0].screenX; }, { passive: true });
  track.addEventListener('touchend',   e => {
    const diff = touchX - e.changedTouches[0].screenX;
    if (Math.abs(diff) > 40) diff > 0 ? goTo(current + 1) : goTo(current - 1);
  }, { passive: true });

  // Pause on hover
  track.closest('.testimonials-outer')?.addEventListener('mouseenter', () => clearInterval(timer));
  track.closest('.testimonials-outer')?.addEventListener('mouseleave', resetTimer);

  // Rebuild on resize
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { current = 0; buildDots(); goTo(0); }, 200);
  });

  // Set track total width so cards are perfectly 1/perView each
  function setTrackWidth() {
    const perView = getPerView();
    track.style.width = (cards.length / perView * 100) + '%';
    cards.forEach(c => {
      c.style.width = (100 / cards.length) + '%';
      c.style.minWidth = (100 / cards.length) + '%';
    });
  }

  setTrackWidth();
  buildDots();
  resetTimer();

  window.addEventListener('resize', () => {
    clearTimeout(window._tResizeTimer);
    window._tResizeTimer = setTimeout(() => {
      current = 0;
      setTrackWidth();
      buildDots();
      goTo(0);
    }, 200);
  });
})();
</script>
</body>
</html>
