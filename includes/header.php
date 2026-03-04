<?php

//  includes/header
require_once __DIR__ . '/db.php';

// Load global SEO settings from DB 
$seoSettings = getSettings(['seo_title','seo_description','seo_keywords','website_url','business_name']);

$siteName    = $seoSettings['business_name'] ?: 'YalaSafari';
$siteUrl     = rtrim($seoSettings['website_url'] ?: 'http://localhost:8080/yala-safari', '/');
$globalTitle = $seoSettings['seo_title']       ?: $siteName . ' – Experience Wild Sri Lanka';
$globalDesc  = $seoSettings['seo_description'] ?: 'Discover the majestic wildlife of Yala National Park. Expert guides, unforgettable safaris.';
$globalKw    = $seoSettings['seo_keywords']    ?: 'Yala Safari, Sri Lanka safari, wildlife tours, Yala National Park';

// ── Per-page overrides (set $pageTitle, $pageDesc, $pageKw before including) ─
$finalTitle = !empty($pageTitle) ? $pageTitle . ' | ' . $siteName : $globalTitle;
$finalDesc  = !empty($pageDesc)  ? $pageDesc  : $globalDesc;
$finalKw    = !empty($pageKw)    ? $pageKw    : $globalKw;

// Canonical URL 
$currentFile = basename($_SERVER['PHP_SELF']);
$canonicalUrl = $siteUrl . '/' . $currentFile;

// OG Image (default) 
$ogImage = !empty($pageOgImage) ? $pageOgImage : $siteUrl . '/images/hero/hero-1.jpg';

function isActive(string $file, string $current): string {
    return $file === $current ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="<?= htmlspecialchars($finalDesc) ?>"/>
  <meta name="keywords"    content="<?= htmlspecialchars($finalKw) ?>"/>
  <meta name="robots"      content="index, follow"/>

  <!-- Open Graph / Social Sharing -->
  <meta property="og:type"        content="website"/>
  <meta property="og:url"         content="<?= htmlspecialchars($canonicalUrl) ?>"/>
  <meta property="og:title"       content="<?= htmlspecialchars($finalTitle) ?>"/>
  <meta property="og:description" content="<?= htmlspecialchars($finalDesc) ?>"/>
  <meta property="og:image"       content="<?= htmlspecialchars($ogImage) ?>"/>
  <meta property="og:site_name"   content="<?= htmlspecialchars($siteName) ?>"/>

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image"/>
  <meta name="twitter:title"       content="<?= htmlspecialchars($finalTitle) ?>"/>
  <meta name="twitter:description" content="<?= htmlspecialchars($finalDesc) ?>"/>
  <meta name="twitter:image"       content="<?= htmlspecialchars($ogImage) ?>"/>

  <!-- Canonical -->
  <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>"/>

  <title><?= htmlspecialchars($finalTitle) ?></title>

  <link rel="icon" href="images/icons/favicon.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="css/global.css"/>
  <link rel="stylesheet" href="css/header.css"/>
  <link rel="stylesheet" href="css/footer.css"/>
  <?php if (!empty($pageCSS)): ?>
    <link rel="stylesheet" href="css/<?= htmlspecialchars($pageCSS) ?>"/>
  <?php endif; ?>


</head>
<body>

<!-- HEADER -->
<header id="site-header">
  <div class="container">
    <nav class="nav-inner">

      <!-- Logo -->
      <a href="index.php" class="nav-logo">
        <img src="images/icons/logo.png" alt="YalaSafari" class="nav-logo-img"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
        <span class="nav-logo-text" style="display:none">Yala<span class="accent">Safari</span></span>
      </a>

      <!-- Desktop nav links + Book Now (matches original exactly) -->
      <div class="nav-links">
        <a href="index.php"    class="<?= ltrim(isActive('index.php',    $currentFile)) ?>">Home</a>
        <a href="packages.php" class="<?= ltrim(isActive('packages.php', $currentFile)) ?>">Packages</a>
        <a href="services.php" class="<?= ltrim(isActive('services.php', $currentFile)) ?>">Services</a>
        <a href="gallery.php"  class="<?= ltrim(isActive('gallery.php',  $currentFile)) ?>">Gallery</a>
        <a href="contact.php"  class="<?= ltrim(isActive('contact.php',  $currentFile)) ?>">Contact</a>
        <a href="contact.php#contactForm" class="nav-book-btn">Book Now</a>
      </div>

      <!-- Hamburger (same id as original) -->
      <button class="nav-hamburger" id="navHamburger" aria-label="Toggle menu">
        <span></span><span></span><span></span>
      </button>

    </nav>
  </div>

  <!-- Mobile full-screen menu (same structure as original) -->
  <nav class="nav-mobile" id="navMobile">
    <button class="nav-mobile-close" id="navMobileClose" aria-label="Close menu">✕</button>
    <a href="index.php"    class="<?= ltrim(isActive('index.php',    $currentFile)) ?>">Home</a>
    <a href="packages.php" class="<?= ltrim(isActive('packages.php', $currentFile)) ?>">Packages</a>
    <a href="services.php" class="<?= ltrim(isActive('services.php', $currentFile)) ?>">Services</a>
    <a href="gallery.php"  class="<?= ltrim(isActive('gallery.php',  $currentFile)) ?>">Gallery</a>
    <a href="contact.php"  class="<?= ltrim(isActive('contact.php',  $currentFile)) ?>">Contact</a>
    <a href="contact.php#contactForm" style="color:var(--green-accent);margin-top:8px">Book Now →</a>
  </nav>

</header>
