<?php

//  booking-confirmation — Booking Confirmation Page

session_start();
require_once 'includes/db.php';

// Guard — redirect if no booking in session
if (empty($_SESSION['booking'])) {
    header('Location: contact.php');
    exit;
}

$booking = $_SESSION['booking'];
// Session cleared at the bottom of the page after displaying

$s = getSettings(['business_name','phone1','whatsapp','whatsapp_message','info_email']);
$businessName = $s['business_name'] ?: 'YalaSafari';
$waNumber     = preg_replace('/[^0-9]/', '', $s['whatsapp'] ?: '94771234567');
$waMessage    = urlencode("Hi! My booking reference is {$booking['ref']}. I'd like to confirm my safari booking.");

$pageTitle = 'Booking Confirmed';
$pageDesc  = 'Your safari booking request has been received. Reference: ' . $booking['ref'];
$pageCSS   = 'booking-confirmation.css';
require_once 'includes/header.php';
?>

  <!-- CONFIRMATION -->
  <section class="confirmation-section">
    <div class="container">
      <div class="confirmation-wrap">

        <!-- Success icon -->
        <div class="confirmation-icon">
          <div class="confirmation-check">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
        </div>

        <!-- Heading -->
        <h1 class="confirmation-title">Booking Request Received!</h1>
        <p class="confirmation-subtitle">Thank you, <strong><?= htmlspecialchars($booking['firstName']) ?></strong>! We've received your safari booking request and will confirm within <strong>24 hours</strong>.</p>

        <!-- Reference badge -->
        <div class="booking-ref-badge">
          <div class="booking-ref-label">Your Booking Reference</div>
          <div class="booking-ref-number" id="refNumber"><?= htmlspecialchars($booking['ref']) ?></div>
          <button class="ref-copy-btn" onclick="copyRef()" title="Copy reference">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
            </svg>
            <span id="copyText">Copy</span>
          </button>
        </div>

        <!-- Booking summary -->
        <div class="booking-summary">
          <h3 class="summary-title">📋 Booking Summary</h3>
          <div class="summary-grid">

            <div class="summary-item">
              <div class="summary-label">👤 Full Name</div>
              <div class="summary-value"><?= htmlspecialchars($booking['firstName'] . ' ' . $booking['lastName']) ?></div>
            </div>

            <div class="summary-item">
              <div class="summary-label">📧 Email</div>
              <div class="summary-value"><?= htmlspecialchars($booking['email']) ?></div>
            </div>

            <div class="summary-item">
              <div class="summary-label">📞 Phone</div>
              <div class="summary-value"><?= htmlspecialchars($booking['phone']) ?></div>
            </div>

            <?php if (!empty($booking['package'])): ?>
            <div class="summary-item">
              <div class="summary-label">🦁 Package</div>
              <div class="summary-value"><?= htmlspecialchars($booking['package']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($booking['date'])): ?>
            <div class="summary-item">
              <div class="summary-label">📅 Safari Date</div>
              <div class="summary-value"><?= date('F j, Y', strtotime($booking['date'])) ?></div>
            </div>
            <?php endif; ?>

            <div class="summary-item">
              <div class="summary-label">🕐 Submitted</div>
              <div class="summary-value"><?= date('F j, Y \a\t g:i A', strtotime($booking['time'])) ?></div>
            </div>

          </div>

          <?php if (!empty($booking['message'])): ?>
          <div class="summary-message">
            <div class="summary-label">💬 Your Message</div>
            <div class="summary-message-text"><?= nl2br(htmlspecialchars($booking['message'])) ?></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- What happens next -->
        <div class="next-steps">
          <h3>What Happens Next?</h3>
          <div class="steps-list">
            <div class="step-item">
              <div class="step-num">1</div>
              <div class="step-text">
                <strong>We review your request</strong>
                <span>Our team checks availability for your preferred date and package</span>
              </div>
            </div>
            <div class="step-item">
              <div class="step-num">2</div>
              <div class="step-text">
                <strong>We contact you within 24 hours</strong>
                <span>Via email or WhatsApp to confirm your booking details</span>
              </div>
            </div>
            <div class="step-item">
              <div class="step-num">3</div>
              <div class="step-text">
                <strong>Your safari is confirmed!</strong>
                <span>Get ready for an unforgettable wildlife adventure in Yala 🦁</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Action buttons -->
        <div class="confirmation-actions">
          <a href="https://wa.me/<?= $waNumber ?>?text=<?= $waMessage ?>"
             target="_blank" class="btn-whatsapp">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            Chat on WhatsApp
          </a>
          <button onclick="window.print()" class="btn-print">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>
            Print / Save PDF
          </button>
          <a href="index.php" class="btn-home">
            Back to Home
          </a>
        </div>

      </div>
    </div>
  </section>

        <!-- Email note -->
        <p class="confirmation-email-note">
          📧 A confirmation email has been sent to <strong><?= htmlspecialchars($booking['email']) ?></strong> — please check your spam folder if you don't see it within a few minutes.
        </p>

<?php
// Clear session after displaying
unset($_SESSION['booking']);
require_once 'includes/footer.php';
?>

<script>
window.addEventListener('load', () => {
  document.querySelector('.confirmation-check')?.classList.add('animate');
  setTimeout(() => {
    document.querySelector('.booking-ref-badge')?.classList.add('animate');
  }, 400);
});

function copyRef() {
  const ref  = document.getElementById('refNumber').textContent.trim();
  const btn  = document.getElementById('copyText');
  navigator.clipboard.writeText(ref).then(() => {
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 2000);
  });
}
</script>

</body>
</html>
