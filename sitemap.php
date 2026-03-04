<?php

//  sitemap — Dynamic XML Sitemap

require_once 'includes/db.php';

$db      = getDB();
$baseUrl = rtrim(getSetting('website_url', 'http://localhost:8080/yala-safari'), '/');

// Auto-update robots.txt sitemap line 
$robotsPath = __DIR__ . '/robots.txt';
$robotsContent = "User-agent: *\nAllow: /\n\n# Block admin area from search engines\nDisallow: /admin/\nDisallow: /includes/\n\n# Sitemap location\nSitemap: {$baseUrl}/sitemap.php\n";
file_put_contents($robotsPath, $robotsContent);

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

  <!-- Static Pages -->
  <url>
    <loc><?= $baseUrl ?>/index.php</loc>
    <changefreq>weekly</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/packages.php</loc>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/services.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/gallery.php</loc>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/contact.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/privacy-policy.php</loc>
    <changefreq>yearly</changefreq>
    <priority>0.3</priority>
  </url>
  <url>
    <loc><?= $baseUrl ?>/terms.php</loc>
    <changefreq>yearly</changefreq>
    <priority>0.3</priority>
  </url>

</urlset>
