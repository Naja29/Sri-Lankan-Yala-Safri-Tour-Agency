<?php

//  privacy-policy — Privacy Policy Page

require_once 'includes/db.php';

$s = getSettings(['business_name','info_email','phone1','address','website_url']);

$businessName = $s['business_name'] ?: 'YalaSafari';
$email        = $s['info_email']    ?: 'info@yalasafari.lk';
$phone        = $s['phone1']        ?: '+94 77 123 4567';
$address      = $s['address']       ?: 'Yala, Sri Lanka';
$lastUpdated  = 'January 1, 2025';

$pageTitle = 'Privacy Policy';
$pageDesc  = $businessName . ' Privacy Policy – How we collect, use and protect your personal information.';
$pageCSS   = 'legal.css';
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
        <span class="current">Privacy Policy</span>
      </div>
      <h1>Privacy Policy</h1>
      <p>How we collect, use and protect your information</p>
    </div>
  </section>


  <!-- CONTENT -->
  <section class="legal-section">
    <div class="container">
      <div class="legal-wrap">

        <div class="legal-meta">
          <span>📅 Last updated: <?= $lastUpdated ?></span>
          <span>🏢 <?= htmlspecialchars($businessName) ?></span>
        </div>

        <div class="legal-intro">
          <p>At <strong><?= htmlspecialchars($businessName) ?></strong>, we are committed to protecting your privacy. This Privacy Policy explains how we collect, use, and safeguard your personal information when you visit our website or book our safari services.</p>
        </div>

        <!-- Section 1 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">01</span> Information We Collect</h2>
          <p>We collect information you provide directly to us, including:</p>
          <ul>
            <li><strong>Personal details</strong> — your name, email address, phone number</li>
            <li><strong>Booking information</strong> — preferred safari package, travel dates, group size</li>
            <li><strong>Communication data</strong> — messages you send us through our contact form</li>
            <li><strong>Technical data</strong> — IP address, browser type, pages visited (via server logs)</li>
          </ul>
        </div>

        <!-- Section 2 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">02</span> How We Use Your Information</h2>
          <p>We use the information we collect to:</p>
          <ul>
            <li>Process and confirm your safari bookings</li>
            <li>Respond to your inquiries and provide customer support</li>
            <li>Send booking confirmations and important updates</li>
            <li>Improve our website and services</li>
            <li>Comply with legal obligations</li>
          </ul>
          <p>We do <strong>not</strong> sell, trade, or rent your personal information to third parties.</p>
        </div>

        <!-- Section 3 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">03</span> Cookies</h2>
          <p>Our website may use cookies to enhance your browsing experience. Cookies are small text files stored on your device. We use them to:</p>
          <ul>
            <li>Remember your preferences</li>
            <li>Understand how visitors use our site</li>
            <li>Improve site performance</li>
          </ul>
          <p>You can choose to disable cookies through your browser settings, though this may affect some website functionality.</p>
        </div>

        <!-- Section 4 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">04</span> Data Security</h2>
          <p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. Your data is stored securely and only accessible to authorized staff.</p>
        </div>

        <!-- Section 5 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">05</span> Third-Party Links</h2>
          <p>Our website may contain links to third-party websites (such as Google Maps, WhatsApp). We are not responsible for the privacy practices of these external sites and encourage you to review their privacy policies.</p>
        </div>

        <!-- Section 6 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">06</span> Your Rights</h2>
          <p>You have the right to:</p>
          <ul>
            <li>Request access to the personal data we hold about you</li>
            <li>Request correction of inaccurate data</li>
            <li>Request deletion of your personal data</li>
            <li>Withdraw consent at any time</li>
          </ul>
          <p>To exercise any of these rights, please contact us using the details below.</p>
        </div>

        <!-- Section 7 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">07</span> Changes to This Policy</h2>
          <p>We may update this Privacy Policy from time to time. We will notify you of significant changes by posting the new policy on this page with an updated date. We encourage you to review this policy periodically.</p>
        </div>

        <!-- Contact -->
        <div class="legal-contact reveal">
          <h2>📬 Contact Us</h2>
          <p>If you have any questions about this Privacy Policy, please contact us:</p>
          <div class="legal-contact-grid">
            <div class="legal-contact-item">
              <div class="legal-contact-icon">📧</div>
              <div>
                <div class="legal-contact-label">Email</div>
                <a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a>
              </div>
            </div>
            <div class="legal-contact-item">
              <div class="legal-contact-icon">📞</div>
              <div>
                <div class="legal-contact-label">Phone</div>
                <a href="tel:<?= htmlspecialchars($phone) ?>"><?= htmlspecialchars($phone) ?></a>
              </div>
            </div>
            <div class="legal-contact-item">
              <div class="legal-contact-icon">📍</div>
              <div>
                <div class="legal-contact-label">Address</div>
                <span><?= nl2br(htmlspecialchars($address)) ?></span>
              </div>
            </div>
          </div>
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
