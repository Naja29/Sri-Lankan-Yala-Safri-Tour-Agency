<?php

//  services — Services Page (live from DB)

require_once 'includes/db.php';

$db = getDB();

// Load all active services 
$result   = $db->query("SELECT * FROM services WHERE status='active' ORDER BY created_at ASC");
$services = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$pageTitle = 'Our Services';
$pageDesc  = 'YalaSafari Services – Wildlife safaris, photography tours, sunrise safaris, private tours, camping and bird watching in Yala National Park, Sri Lanka.';
$pageKw    = 'Yala safari services, wildlife photography tours, bird watching Yala, private safari Sri Lanka, sunrise safari';
$pageCSS   = 'services.css';
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
        <span class="current">Services</span>
      </div>
      <h1>Our Services</h1>
      <p>Comprehensive safari services designed to make your wildlife adventure unforgettable and hassle-free</p>
    </div>
  </section>


  <!-- SERVICES SECTION -->
  <section class="services-section">
    <div class="container">

      <div class="services-section-header">
        <h2 class="section-title reveal">What We Offer</h2>
        <p class="section-subtitle reveal reveal-delay-1">From guided safaris to complete tour packages, we provide everything you need for the perfect Yala experience</p>
      </div>

      <div class="services-grid-full">

        <?php if (empty($services)): ?>
          <div style="text-align:center;padding:60px 20px;color:var(--text-muted);">
            <p>No services available yet. Check back soon!</p>
          </div>

        <?php else: ?>
          <?php foreach ($services as $i => $svc):
            $delay    = $i % 3;
            $features = array_filter(array_map('trim', explode("\n", $svc['features'] ?? '')));
            $imgSrc   = !empty($svc['image'])
                        ? htmlspecialchars($svc['image'])
                        : 'images/services/service-0' . (($i % 9) + 1) . '.jpg';
          ?>
            <div class="svc-card reveal <?= $delay > 0 ? 'reveal-delay-' . $delay : '' ?>">

              <!-- Image -->
              <div class="svc-card-img">
                <img src="<?= $imgSrc ?>"
                     alt="<?= htmlspecialchars($svc['name']) ?>"
                     loading="lazy"
                     onerror="this.src='images/services/service-01.jpg'"/>
              </div>

              <!-- Body -->
              <div class="svc-card-body">
                <h3><?= htmlspecialchars($svc['name']) ?></h3>

                <?php if (!empty($svc['description'])): ?>
                  <p><?= htmlspecialchars($svc['description']) ?></p>
                <?php endif; ?>

                <?php if (!empty($features)): ?>
                  <ul class="svc-card-features">
                    <?php foreach (array_slice($features, 0, 4) as $feature): ?>
                      <li>
                        <svg width="15" height="15" viewBox="0 0 24 24" stroke="var(--green-accent)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none">
                          <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <?= htmlspecialchars($feature) ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>

                <a href="contact.php#contactForm" class="svc-card-link">
                  Book Now
                  <svg width="14" height="14" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                  </svg>
                </a>
              </div>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </div>
  </section>


  <!-- CTA -->
  <?php
    $ctaTitle    =  "Let's Plan Your Safari Adventure";
    $ctaSubtitle = "Tell us what you're looking for and we'll create the perfect experience for you. Get in touch today - we reply within 24 hours.";
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
window.addEventListener('load', () => {
  document.getElementById('pageHeroBg')?.classList.add('loaded');
});
</script>

</body>
</html>
