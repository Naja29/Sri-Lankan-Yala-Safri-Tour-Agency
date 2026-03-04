<?php

//  404.php — Page Not Found

http_response_code(404);
require_once 'includes/db.php';
$pageTitle = 'Page Not Found – YalaSafari';
$pageDesc  = 'The page you are looking for could not be found.';
$pageCSS   = '404.css';
require_once 'includes/header.php';
?>

<section class="not-found">
  <div class="not-found-bg"></div>
  <div class="container">
    <div class="not-found-content">
      <div class="not-found-animal">🦁</div>
      <h1 class="not-found-code">404</h1>
      <h2 class="not-found-title">Lost in the Jungle?</h2>
      <p class="not-found-text">The page you're looking for has wandered off into the wilderness. Let's get you back on the safari trail.</p>
      <div class="not-found-actions">
        <a href="index.php" class="btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
          </svg>
          Back to Home
        </a>
        <a href="packages.php" class="btn-outline">View Packages</a>
        <a href="contact.php#contactForm" class="btn-outline">Contact Us</a>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
