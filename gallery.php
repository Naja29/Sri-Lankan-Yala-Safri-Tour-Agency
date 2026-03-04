<?php

//  gallery.php — Photo Gallery (live from DB)

require_once 'includes/db.php';

$db = getDB();

// Load all gallery images 
$result  = $db->query("SELECT * FROM gallery ORDER BY created_at DESC");
$images  = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total   = count($images);

// Build unique category list 
$categories = [];
foreach ($images as $img) {
    $cat = trim($img['category'] ?? '');
    if ($cat && !in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}

// Load stats from settings 
$gs = getSettings(['years_experience','happy_guests','species_spotted','five_star_reviews']);

$pageTitle = 'Photo Gallery';
$pageDesc  = 'YalaSafari Photo Gallery – Witness the incredible beauty and wildlife of Yala National Park through our lens.';
$pageKw    = 'Yala National Park photos, safari wildlife gallery, Sri Lanka wildlife photography, leopard elephant photos';
$pageCSS   = 'gallery.css';
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
        <span class="current">Gallery</span>
      </div>
      <h1>Photo Gallery</h1>
      <p>Witness the incredible beauty and wildlife of Yala National Park through our lens</p>
    </div>
  </section>


  <!-- GALLERY SECTION -->
  <section class="gallery-section">
    <div class="container">

      <div class="gallery-section-header">
        <h2 class="section-title reveal">Our Wildlife Photography Collection</h2>
        <div class="gallery-hint reveal reveal-delay-1">
          <svg width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            <line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>
          </svg>
          Click on any image to view in full size
        </div>
      </div>

      <?php if (!empty($categories)): ?>
      <!-- Filter buttons (only shown if categories exist) -->
      <div class="gallery-filter reveal">
        <button class="gallery-filter-btn active" data-filter="all">All</button>
        <?php foreach ($categories as $cat): ?>
          <button class="gallery-filter-btn" data-filter="<?= htmlspecialchars(strtolower(preg_replace('/\s+/', '-', $cat))) ?>">
            <?= htmlspecialchars($cat) ?>
          </button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (empty($images)): ?>
        <div style="text-align:center;padding:80px 20px;color:var(--text-muted);">
          <p>No gallery images yet. Check back soon!</p>
        </div>

      <?php else: ?>
        <div class="gallery-grid" id="galleryGrid">
          <?php foreach ($images as $i => $img):
            $delay   = $i % 3;
            $imgSrc  = htmlspecialchars($img['image']);
            $catSlug = strtolower(preg_replace('/\s+/', '-', trim($img['category'] ?? '')));
          ?>
            <div class="gallery-item reveal <?= $delay > 0 ? 'reveal-delay-' . $delay : '' ?>"
                 data-index="<?= $i ?>"
                 data-name="<?= htmlspecialchars($img['name']) ?>"
                 data-desc="<?= htmlspecialchars($img['description'] ?? '') ?>"
                 data-category="<?= htmlspecialchars($catSlug) ?>">

              <img src="<?= $imgSrc ?>"
                   alt="<?= htmlspecialchars($img['name']) ?>"
                   loading="lazy"
                   onerror="this.src='images/gallery/gallery-01.jpg'"/>

              <div class="gallery-item-overlay">
                <div class="gallery-zoom-icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none">
                    <polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/>
                    <line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/>
                  </svg>
                </div>
                <div class="gallery-item-caption">
                  <div class="gallery-item-name"><?= htmlspecialchars($img['name']) ?></div>
                  <?php if (!empty($img['description'])): ?>
                    <div class="gallery-item-desc"><?= htmlspecialchars($img['description']) ?></div>
                  <?php endif; ?>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        </div>

        <!-- No results after filter -->
        <div class="gallery-no-results" id="galleryNoResults" style="display:none;">
          <p>No images found in this category.</p>
        </div>
      <?php endif; ?>

    </div>
  </section>


  <!-- LIGHTBOX -->
  <div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
    <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
    <div class="lightbox-counter" id="lightboxCounter">1 / <?= count($images) ?></div>
    <button class="lightbox-close" id="lightboxClose" aria-label="Close">✕</button>
    <button class="lightbox-prev" id="lightboxPrev" aria-label="Previous image">
      <svg width="20" height="20" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
    </button>
    <div class="lightbox-inner">
      <div class="lightbox-img-wrap">
        <img id="lightboxImg" src="" alt=""/>
      </div>
      <div class="lightbox-caption">
        <div class="lightbox-caption-name" id="lightboxName"></div>
        <div class="lightbox-caption-desc" id="lightboxDesc"></div>
      </div>
    </div>
    <button class="lightbox-next" id="lightboxNext" aria-label="Next image">
      <svg width="20" height="20" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none">
        <polyline points="9 18 15 12 9 6"/>
      </svg>
    </button>
  </div>


  <!-- CTA -->
  <?php
    $ctaTitle    = 'Want to Be Part of Our Story?';
    $ctaSubtitle = 'Book your safari today and create memories that will last a lifetime.';
    require_once 'includes/cta.php';
  ?>


  <!-- STATS -->
  <section class="stats-section">
    <div class="container">
      <div class="stats-grid">

        <div class="stat-item reveal">
          <div class="stat-number">
            <span data-counter="<?= $total ?: 0 ?>"><?= $total ?: 0 ?></span>+
          </div>
          <div class="stat-label">Photos in Collection</div>
        </div>

        <div class="stat-item reveal reveal-delay-1">
          <div class="stat-number">
            <?php $v = (int)($gs['species_spotted'] ?: 0) ?: 50; ?>
            <span data-counter="<?= $v ?>">0</span>+
          </div>
          <div class="stat-label">Wildlife Species Captured</div>
        </div>

        <div class="stat-item reveal reveal-delay-2">
          <div class="stat-number">
            <?php $v = (int)($gs['happy_guests'] ?: 0) ?: 1200; ?>
            <span data-counter="<?= $v ?>">0</span>+
          </div>
          <div class="stat-label">Happy Visitors</div>
        </div>

        <div class="stat-item reveal reveal-delay-3">
          <div class="stat-number">
            <?php $v = (int)($gs['years_experience'] ?: 0) ?: 10; ?>
            <span data-counter="<?= $v ?>">0</span>+
          </div>
          <div class="stat-label">Years of Experience</div>
        </div>

      </div>
    </div>
  </section>


<?php require_once 'includes/footer.php'; ?>

<script>
// Hero bg zoom
window.addEventListener('load', () => {
  document.getElementById('pageHeroBg')?.classList.add('loaded');
});

// Gallery Filter 
const filterBtns   = document.querySelectorAll('.gallery-filter-btn');
const galleryItems = document.querySelectorAll('.gallery-item');
const noResults    = document.getElementById('galleryNoResults');

filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const filter = btn.dataset.filter;
    let count = 0;

    galleryItems.forEach(item => {
      const match = filter === 'all' || item.dataset.category === filter;
      item.style.display = match ? '' : 'none';
      if (match) count++;
    });

    if (noResults) noResults.style.display = count === 0 ? 'block' : 'none';
  });
});

// Lightbox 
const lightbox  = document.getElementById('lightbox');
const lbImg     = document.getElementById('lightboxImg');
const lbName    = document.getElementById('lightboxName');
const lbDesc    = document.getElementById('lightboxDesc');
const lbCounter = document.getElementById('lightboxCounter');
const lbClose   = document.getElementById('lightboxClose');
const lbPrev    = document.getElementById('lightboxPrev');
const lbNext    = document.getElementById('lightboxNext');
const lbBg      = document.getElementById('lightboxBackdrop');

let current = 0;
let visibleItems = () => [...galleryItems].filter(i => i.style.display !== 'none');

function openLightbox(index) {
  current = index;
  updateLightbox();
  lightbox.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  lightbox.classList.remove('open');
  document.body.style.overflow = '';
}

function updateLightbox() {
  const visible = visibleItems();
  const item    = visible[current];
  if (!item) return;

  const img = item.querySelector('img');
  lbImg.classList.add('loading');
  lbImg.src    = img.src;
  lbImg.alt    = img.alt;
  lbImg.onload = () => lbImg.classList.remove('loading');

  lbName.textContent    = item.dataset.name || img.alt;
  lbDesc.textContent    = item.dataset.desc || '';
  lbCounter.textContent = `${current + 1} / ${visible.length}`;
}

function prevImage() {
  const visible = visibleItems();
  current = (current - 1 + visible.length) % visible.length;
  updateLightbox();
}

function nextImage() {
  const visible = visibleItems();
  current = (current + 1) % visible.length;
  updateLightbox();
}

galleryItems.forEach((item, i) => {
  item.addEventListener('click', () => {
    const visible = visibleItems();
    const visibleIndex = visible.indexOf(item);
    if (visibleIndex !== -1) openLightbox(visibleIndex);
  });
});

lbClose.addEventListener('click', closeLightbox);
lbBg.addEventListener('click', closeLightbox);
lbPrev.addEventListener('click', (e) => { e.stopPropagation(); prevImage(); });
lbNext.addEventListener('click', (e) => { e.stopPropagation(); nextImage(); });

document.addEventListener('keydown', (e) => {
  if (!lightbox.classList.contains('open')) return;
  if (e.key === 'Escape')     closeLightbox();
  if (e.key === 'ArrowLeft')  prevImage();
  if (e.key === 'ArrowRight') nextImage();
});

let touchStartX = 0;
lightbox.addEventListener('touchstart', e => {
  touchStartX = e.changedTouches[0].screenX;
}, { passive: true });
lightbox.addEventListener('touchend', e => {
  const diff = touchStartX - e.changedTouches[0].screenX;
  if (Math.abs(diff) > 50) diff > 0 ? nextImage() : prevImage();
}, { passive: true });
</script>

</body>
</html>
