<?php

//  admin/dashboard

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
requireLogin();

$db    = getDB();
$admin = currentAdmin();

// Stat counts 
function countTable(mysqli $db, string $table, string $where = ''): int {
    $sql    = "SELECT COUNT(*) as cnt FROM `{$table}`" . ($where ? " WHERE {$where}" : '');
    $result = $db->query($sql);
    return $result ? (int)$result->fetch_assoc()['cnt'] : 0;
}

$totalPackages    = countTable($db, 'packages');
$totalServices    = countTable($db, 'services');
$totalGallery     = countTable($db, 'gallery');
$newMessages      = countTable($db, 'messages', 'is_read = 0');
$pendingReviews   = countTable($db, 'testimonials', "status='pending'");
$approvedReviews  = countTable($db, 'testimonials', "status='approved'");
$totalHeroSlides  = countTable($db, 'hero_slides', "status='active'");

// Recent messages (latest 5) 
$recentMessages = [];
$msgResult = $db->query('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5');
if ($msgResult) {
    while ($row = $msgResult->fetch_assoc()) {
        $recentMessages[] = $row;
    }
}

// Recent packages (latest 5) 
$recentPackages = [];
$pkgResult = $db->query('SELECT * FROM packages ORDER BY created_at DESC LIMIT 5');
if ($pkgResult) {
    while ($row = $pkgResult->fetch_assoc()) {
        $recentPackages[] = $row;
    }
}

// Current time greeting
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard – YalaSafari Admin</title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="icon" href="../images/icons/favicon.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="css/admin.css"/>
</head>
<body>

<div class="admin-layout">

  <!-- SIDEBAR -->
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <!-- MAIN CONTENT -->
  <main class="admin-main">

    <!-- Top Bar -->
    <div class="admin-topbar">
      <div class="topbar-title">
        <h1>Dashboard</h1>
        <p><?= $greeting ?>, <?= htmlspecialchars($admin['name']) ?>! Welcome back!</p>
      </div>
      <div class="topbar-actions">
        <span class="topbar-time" id="liveTime"></span>
      </div>
    </div>

    <!-- Content -->
    <div class="admin-content">

      <!-- Stat Cards -->
      <div class="stats-grid">

        <div class="stat-card">
          <div class="stat-icon">📦</div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalPackages ?></div>
            <div class="stat-label">Total Packages</div>
          </div>
          <a href="packages.php" class="stat-card-link">Manage →</a>
        </div>

        <div class="stat-card">
          <div class="stat-icon">🔔</div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalServices ?></div>
            <div class="stat-label">Services Listed</div>
          </div>
          <a href="services.php" class="stat-card-link">Manage →</a>
        </div>

        <div class="stat-card">
          <div class="stat-icon">🖼️</div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalGallery ?></div>
            <div class="stat-label">Gallery Images</div>
          </div>
          <a href="gallery.php" class="stat-card-link">Manage →</a>
        </div>

        <div class="stat-card <?= $newMessages > 0 ? 'stat-card-alert' : '' ?>">
          <div class="stat-icon">💬</div>
          <div class="stat-info">
            <div class="stat-number"><?= $newMessages ?></div>
            <div class="stat-label">New Messages</div>
          </div>
          <a href="messages.php" class="stat-card-link">View →</a>
        </div>

        <div class="stat-card <?= $pendingReviews > 0 ? 'stat-card-alert' : '' ?>">
          <div class="stat-icon">⭐</div>
          <div class="stat-info">
            <div class="stat-number"><?= $pendingReviews ?></div>
            <div class="stat-label">Pending Reviews</div>
          </div>
          <a href="testimonials.php?filter=pending" class="stat-card-link">Review →</a>
        </div>

        <div class="stat-card">
          <div class="stat-icon">🎬</div>
          <div class="stat-info">
            <div class="stat-number"><?= $totalHeroSlides ?></div>
            <div class="stat-label">Active Hero Slides</div>
          </div>
          <a href="hero.php" class="stat-card-link">Manage →</a>
        </div>

      </div>


      <!-- Two Column: Messages + Quick Actions -->
      <div class="content-grid">

        <!-- Recent Messages -->
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">💬 Recent Messages</span>
            <a href="messages.php" class="panel-link">View all →</a>
          </div>
          <div class="panel-body">
            <?php if (empty($recentMessages)): ?>
              <div class="msg-empty">
                <div class="empty-icon">📭</div>
                No messages yet
              </div>
            <?php else: ?>
              <?php foreach ($recentMessages as $msg): ?>
                <?php $initial = strtoupper(substr($msg['first_name'], 0, 1)); ?>
                <div class="message-item">
                  <div class="msg-avatar"><?= htmlspecialchars($initial) ?></div>
                  <div class="msg-content">
                    <div class="msg-header">
                      <span class="msg-name">
                        <?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?>
                      </span>
                      <?php if (!$msg['is_read']): ?>
                        <span class="msg-badge">NEW</span>
                      <?php endif; ?>
                    </div>
                    <div class="msg-meta">
                      <?= htmlspecialchars($msg['email']) ?>
                      &bull;
                      <?= date('n/j/Y', strtotime($msg['created_at'])) ?>
                    </div>
                    <div class="msg-preview">
                      <?= htmlspecialchars(substr($msg['message'], 0, 65)) ?>...
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="panel">
          <div class="panel-header">
            <span class="panel-title">⚡ Quick Actions</span>
          </div>
          <div class="panel-body">
            <div class="quick-actions-grid">
              <a href="packages.php?action=add" class="quick-action-btn">
                <span class="quick-action-icon">📦</span>
                Add Package
              </a>
              <a href="services.php?action=add" class="quick-action-btn">
                <span class="quick-action-icon">🔔</span>
                Add Service
              </a>
              <a href="gallery.php?action=add" class="quick-action-btn">
                <span class="quick-action-icon">🖼️</span>
                Add Photo
              </a>
              <a href="testimonials.php" class="quick-action-btn">
                <span class="quick-action-icon">⭐</span>
                Reviews
              </a>
              <a href="hero.php" class="quick-action-btn">
                <span class="quick-action-icon">🎬</span>
                Hero Slides
              </a>
              <a href="messages.php" class="quick-action-btn">
                <span class="quick-action-icon">💬</span>
                Messages
              </a>
              <a href="settings.php" class="quick-action-btn">
                <span class="quick-action-icon">⚙️</span>
                Settings
              </a>
              <a href="../index.php" target="_blank" class="quick-action-btn">
                <span class="quick-action-icon">🌐</span>
                View Site
              </a>
            </div>
          </div>
        </div>

      </div>


      <!-- Recent Packages Table -->
      <div class="panel panel-full">
        <div class="panel-header">
          <span class="panel-title">📦 Recent Packages</span>
          <a href="packages.php" class="panel-link">Manage all →</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Package Name</th>
                <th>Category</th>
                <th>Duration</th>
                <th>Price</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentPackages)): ?>
                <tr>
                  <td colspan="5" style="text-align:center; padding:36px; color:var(--text-muted);">
                    No packages yet — <a href="packages.php?action=add" style="color:var(--green-accent); font-weight:600;">Add your first package</a>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentPackages as $pkg): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($pkg['name']) ?></strong></td>
                    <td><?= htmlspecialchars($pkg['category'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($pkg['duration'] ?? '—') ?></td>
                    <td>Rs. <?= number_format($pkg['price'] ?? 0) ?></td>
                    <td>
                      <?php $s = $pkg['status'] ?? 'active'; ?>
                      <span class="status-badge status-<?= $s ?>">
                        <?= ucfirst($s) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
  // Live clock
  function updateTime() {
    const now = new Date();
    document.getElementById('liveTime').textContent = now.toLocaleTimeString('en-US', {
      hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
  }
  updateTime();
  setInterval(updateTime, 1000);
</script>

</body>
</html>
