<?php

//  terms.php — Terms of Service Page

require_once 'includes/db.php';

$s = getSettings(['business_name','info_email','phone1','address','website_url']);

$businessName = $s['business_name'] ?: 'YalaSafari';
$email        = $s['info_email']    ?: 'info@yalasafari.lk';
$phone        = $s['phone1']        ?: '+94 77 123 4567';
$lastUpdated  = 'January 1, 2025';

$pageTitle = 'Terms of Service';
$pageDesc  = $businessName . ' Terms of Service – Please read our terms and conditions before booking a safari with us.';
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
        <span class="current">Terms of Service</span>
      </div>
      <h1>Terms of Service</h1>
      <p>Please read these terms carefully before booking with us</p>
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
          <p>By using our website or booking a safari with <strong><?= htmlspecialchars($businessName) ?></strong>, you agree to these Terms of Service. Please read them carefully before making a booking.</p>
        </div>

        <!-- Section 1 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">01</span> Bookings & Reservations</h2>
          <ul>
            <li>All bookings are subject to availability and confirmed only upon receipt of a booking confirmation from us</li>
            <li>A booking is considered confirmed when you receive a written confirmation via email or WhatsApp</li>
            <li>We reserve the right to decline any booking at our discretion</li>
            <li>Accurate personal information must be provided at the time of booking</li>
          </ul>
        </div>

        <!-- Section 2 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">02</span> Payments</h2>
          <ul>
            <li>Full payment or a deposit may be required to confirm your booking — details will be provided at the time of reservation</li>
            <li>Prices are quoted in Sri Lankan Rupees (LKR) unless otherwise stated</li>
            <li>Prices are subject to change without notice, but confirmed bookings will honour the agreed price</li>
            <li>Additional charges may apply for special requests made after booking</li>
          </ul>
        </div>

        <!-- Section 3 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">03</span> Cancellations & Refunds</h2>
          <ul>
            <li><strong>More than 7 days before:</strong> Full refund or free rescheduling</li>
            <li><strong>3–7 days before:</strong> 50% refund or rescheduling subject to availability</li>
            <li><strong>Less than 48 hours before:</strong> No refund</li>
            <li><strong>No-show:</strong> No refund</li>
          </ul>
          <p>Cancellations due to extreme weather, park closures, or other circumstances beyond our control will be rescheduled at no extra charge.</p>
        </div>

        <!-- Section 4 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">04</span> Safari Guidelines & Conduct</h2>
          <p>For the safety of all guests and wildlife, all visitors must:</p>
          <ul>
            <li>Follow all instructions given by our guides at all times</li>
            <li>Remain inside the safari vehicle unless specifically instructed otherwise</li>
            <li>Not feed, provoke, or interfere with any wildlife</li>
            <li>Maintain a respectful noise level inside the park</li>
            <li>Follow all Yala National Park rules and regulations</li>
          </ul>
          <p>We reserve the right to terminate a safari without refund if a guest's behavior endangers others or wildlife.</p>
        </div>

        <!-- Section 5 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">05</span> Wildlife Sightings</h2>
          <p>We cannot guarantee sightings of specific animals including leopards, elephants, or other wildlife. Safari experiences are subject to the natural behavior of wild animals and environmental conditions. No refunds will be issued for the absence of specific wildlife sightings.</p>
        </div>

        <!-- Section 6 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">06</span> Health & Safety</h2>
          <ul>
            <li>Guests participate in safari activities at their own risk</li>
            <li>We strongly recommend travel insurance that covers safari activities</li>
            <li>Guests with medical conditions should inform us before booking</li>
            <li>Children under 5 years must be accompanied by a responsible adult at all times</li>
            <li>We are not liable for injuries resulting from failure to follow guide instructions</li>
          </ul>
        </div>

        <!-- Section 7 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">07</span> Photography & Media</h2>
          <ul>
            <li>Guests are welcome to photograph and film during safaris for personal use</li>
            <li>Commercial photography or filming requires prior written permission</li>
            <li>By sharing photos on social media, we ask that you tag us — we love seeing your shots!</li>
            <li>We may photograph guests during safaris for promotional purposes — please inform us if you prefer not to be photographed</li>
          </ul>
        </div>

        <!-- Section 8 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">08</span> Limitation of Liability</h2>
          <p><?= htmlspecialchars($businessName) ?> shall not be liable for any indirect, incidental, or consequential damages arising from the use of our services. Our total liability shall not exceed the amount paid for the specific booking in question.</p>
        </div>

        <!-- Section 9 -->
        <div class="legal-block reveal">
          <h2><span class="legal-num">09</span> Changes to Terms</h2>
          <p>We reserve the right to modify these Terms of Service at any time. Updated terms will be posted on this page. Continued use of our services after changes constitutes acceptance of the new terms.</p>
        </div>

        <!-- Contact -->
        <div class="legal-contact reveal">
          <h2>📬 Questions?</h2>
          <p>If you have any questions about these Terms, please contact us before making a booking:</p>
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
