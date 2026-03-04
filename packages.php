<?php

//  packages — Safari Packages (live from DB)

require_once 'includes/db.php';

$db = getDB();

// Load all active packages 
$result   = $db->query("SELECT * FROM packages WHERE status='active' ORDER BY created_at ASC");
$packages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Build unique category list from DB 
$categories = [];
foreach ($packages as $pkg) {
    $cat = trim($pkg['category'] ?? '');
    if ($cat && !in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}

// Helper: category → filter slug 
function catSlug(string $cat): string {
    return strtolower(preg_replace('/\s+/', '-', trim($cat)));
}

$pageTitle = 'Safari Packages';
$pageDesc  = 'Explore YalaSafari\'s safari packages – half day, full day, multi-day and photography tours in Yala National Park, Sri Lanka.';
$pageKw    = 'Yala safari packages, half day safari, full day safari, Yala National Park tours, Sri Lanka wildlife packages';
$pageCSS   = 'packages.css';
require_once 'includes/header.php';
?>


  <!-- PAGE HERO -->
  <section class="page-hero">
    <div class="page-hero-bg" id="pageHeroBg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span class="current">Packages</span>
      </div>
      <h1>Safari Packages</h1>
      <p>Choose from our carefully curated safari experiences designed to give you the best wildlife adventure</p>
    </div>
  </section>


  <!-- FILTER SECTION -->
  <section class="packages-filter">
    <div class="container">
      <h2 class="filter-title reveal">Filter by Category</h2>
      <div class="filter-buttons reveal reveal-delay-1">
        <button class="filter-btn active" data-filter="all">All Packages</button>
        <?php foreach ($categories as $cat): ?>
          <button class="filter-btn" data-filter="<?= htmlspecialchars(catSlug($cat)) ?>">
            <?= htmlspecialchars($cat) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- PACKAGES GRID -->
  <section class="packages-section">
    <div class="container">

      <div class="packages-section-header">
        <h2 class="section-title reveal">Explore Our Safari Packages</h2>
        <p class="section-subtitle reveal reveal-delay-1">From quick half-day adventures to comprehensive multi-day expeditions</p>
      </div>

      <div class="packages-grid-full" id="packagesGrid">

        <?php if (empty($packages)): ?>
          <div class="no-results visible">
            <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <h3>No packages available</h3>
            <p>Check back soon or contact us for custom packages</p>
          </div>

        <?php else: ?>
          <?php foreach ($packages as $i => $pkg):
            $delay     = $i % 3;
            $catSlug   = catSlug($pkg['category'] ?? '');
            $features  = array_filter(array_map('trim', explode("\n", $pkg['features'] ?? '')));
            $imgSrc    = !empty($pkg['image'])
                         ? htmlspecialchars($pkg['image'])
                         : 'images/packages/package-0' . (($i % 9) + 1) . '.jpg';
            $price     = (float)($pkg['price'] ?? 0);
            $pricePer  = $pkg['price_per'] ?: 'Per Person';
            $badge     = $pkg['badge_label'] ?? '';
          ?>
            <div class="pkg-card reveal <?= $delay > 0 ? 'reveal-delay-' . $delay : '' ?>"
                 data-category="<?= htmlspecialchars($catSlug) ?>">

              <!-- Image -->
              <div class="pkg-card-img">
                <img src="<?= $imgSrc ?>"
                     alt="<?= htmlspecialchars($pkg['name']) ?>"
                     loading="lazy"
                     onerror="this.src='images/packages/package-01.jpg'"/>
                <div class="pkg-card-img-overlay"></div>

                <?php if ($badge): ?>
                  <span class="pkg-card-badge"><?= htmlspecialchars($badge) ?></span>
                <?php endif; ?>

                <span class="pkg-card-tag"><?= htmlspecialchars($pkg['category'] ?? '') ?></span>

                <div class="pkg-card-img-info">
                  <div class="pkg-card-name"><?= htmlspecialchars($pkg['name']) ?></div>
                  <?php if (!empty($pkg['duration'])): ?>
                    <div class="pkg-card-duration">
                      <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                      </svg>
                      Duration: <?= htmlspecialchars($pkg['duration']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Body -->
              <div class="pkg-card-body">
                <?php if (!empty($pkg['description'])): ?>
                  <p class="pkg-card-desc"><?= htmlspecialchars($pkg['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($features)): ?>
                  <ul class="pkg-card-features">
                    <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                      <li><?= htmlspecialchars($feature) ?></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>

              <!-- Footer -->
              <div class="pkg-card-footer">
                <div class="pkg-price-wrap">
                  <span class="pkg-price-from">From</span>
                  <?php if ($price > 0): ?>
                    <span class="pkg-price-amount">Rs <?= number_format($price) ?></span>
                    <span class="pkg-price-per"><?= htmlspecialchars(strtolower($pricePer)) ?></span>
                  <?php else: ?>
                    <span class="pkg-price-amount">Contact Us</span>
                  <?php endif; ?>
                </div>
                <button class="pkg-book-btn"
                  onclick="window.location.href='contact.php?package=<?= urlencode($pkg['name']) ?>#contactForm'">
                  Book Now
                </button>
              </div>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- No results after filter -->
        <div class="no-results" id="noResults">
          <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>
          <h3>No packages found</h3>
          <p>Try selecting a different category</p>
        </div>

      </div>
    </div>
  </section>


  <!-- CTA -->
  <?php
    $ctaTitle    = "Found Your Perfect Safari?";
    $ctaSubtitle = "Reserve your spot now before it fills up. Our packages are available daily with limited jeep slots - early booking is recommended.";
    require_once 'includes/cta.php';
  ?>


  <!-- WHY CHOOSE US -->
  <section class="why-choose-us">
    <div class="container">
      <div class="why-header">
        <h2 class="section-title reveal">Why Choose YalaSafari?</h2>
        <p class="section-subtitle reveal reveal-delay-1">Everything we do is designed to give you the most unforgettable safari experience</p>
      </div>
      <div class="why-grid">

        <div class="why-card reveal">
          <div class="why-icon-wrap">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/>
            </svg>
          </div>
          <h4>Expert Guides</h4>
          <p>10+ years of experience leading safaris through every corner of Yala National Park</p>
        </div>

        <div class="why-card reveal reveal-delay-1">
          <div class="why-icon-wrap">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
          </div>
          <h4>Premium Vehicles</h4>
          <p>Comfortable 4×4 safari jeeps equipped for the best wildlife viewing experience</p>
        </div>

        <div class="why-card reveal reveal-delay-2">
          <div class="why-icon-wrap">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/>
            </svg>
          </div>
          <h4>Best Locations</h4>
          <p>Exclusive access to prime wildlife spots not available to standard tour operators</p>
        </div>

        <div class="why-card reveal">
          <div class="why-icon-wrap">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
          </div>
          <h4>500+ Happy Guests</h4>
          <p>Hundreds of satisfied adventurers from around the world trust YalaSafari every year</p>
        </div>

        <div class="why-card reveal reveal-delay-1">
          <div class="why-icon-wrap">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>
          <h4>Eco Responsible</h4>
          <p>We practice ethical wildlife tourism that protects and preserves Yala's ecosystem</p>
        </div>

        <div class="why-card reveal reveal-delay-2">
          <div class="why-icon-wrap">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
            </svg>
          </div>
          <h4>Best Price Guarantee</h4>
          <p>Transparent pricing with no hidden fees — the best value safari experience in Sri Lanka</p>
        </div>

      </div>
    </div>
  </section>


<?php require_once 'includes/footer.php'; ?>

<script>
// Hero bg zoom on load
window.addEventListener('load', () => {
  document.getElementById('pageHeroBg')?.classList.add('loaded');
});

// Quick contact form → redirect to contact.php 
document.getElementById('quickForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  const name    = document.getElementById('qName').value.trim();
  const email   = document.getElementById('qEmail').value.trim();
  const phone   = document.getElementById('qPhone').value.trim();
  const message = document.getElementById('qMessage').value.trim();

  if (!name || !email || !message) {
    if (!name)    document.getElementById('qName').style.borderColor    = '#e05252';
    if (!email)   document.getElementById('qEmail').style.borderColor   = '#e05252';
    if (!message) document.getElementById('qMessage').style.borderColor = '#e05252';
    return;
  }

  const params = new URLSearchParams({ name, email, phone, message });
  window.location.href = 'contact.php?' + params.toString();
});
const filterBtns = document.querySelectorAll('.filter-btn');
const pkgCards   = document.querySelectorAll('.pkg-card');
const noResults  = document.getElementById('noResults');

filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const filter = btn.dataset.filter;
    let visibleCount = 0;

    pkgCards.forEach(card => {
      const match = filter === 'all' || card.dataset.category === filter;
      if (match) {
        card.classList.remove('hidden');
        card.style.animationDelay = `${(visibleCount % 3) * 0.08}s`;
        card.classList.remove('pkg-card-animate');
        void card.offsetWidth;
        card.classList.add('pkg-card-animate');
        visibleCount++;
      } else {
        card.classList.add('hidden');
      }
    });

    noResults.classList.toggle('visible', visibleCount === 0);
  });
});
</script>

</body>
</html>
