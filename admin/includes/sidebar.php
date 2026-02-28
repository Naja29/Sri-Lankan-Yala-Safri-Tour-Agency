<?php

//  includes/sidebar.php — Shared sidebar for all admin pages


$admin        = currentAdmin();
$currentPage  = basename($_SERVER['PHP_SELF'], '.php');

// Unread messages count for badge
$db           = getDB();
$unreadResult = $db->query('SELECT COUNT(*) as cnt FROM messages WHERE is_read = 0');
$unreadCount  = $unreadResult ? (int)$unreadResult->fetch_assoc()['cnt'] : 0;

function navItem(string $href, string $icon, string $label, string $page, string $current, int $badge = 0): string {
    $active = ($page === $current) ? ' active' : '';
    $badgeHtml = $badge > 0 ? "<span class='nav-badge'>{$badge}</span>" : '';
    return "<a href='{$href}' class='nav-item{$active}'>
              <span class='nav-item-icon'>{$icon}</span>
              {$label}
              {$badgeHtml}
            </a>";
}

$initial = strtoupper(substr($admin['name'], 0, 1));
?>

<aside class="sidebar" id="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <a href="../index.html">
      <img
        src="../images/icons/logo-admin.png"
        alt="YalaSafari"
        onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
      />
      <span class="sidebar-logo-text" style="display:none">Yala<span>Safari</span></span>
    </a>
    <span class="sidebar-logo-sub">Admin Panel</span>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <div class="nav-section">
      <div class="nav-section-label">Main</div>
      <?= navItem('dashboard.php', '📊', 'Dashboard', 'dashboard', $currentPage) ?>
    </div>

    <div class="nav-section">
      <div class="nav-section-label">Manage</div>
      <?= navItem('packages.php', '📦', 'Packages',  'packages',  $currentPage) ?>
      <?= navItem('services.php', '🔔', 'Services',  'services',  $currentPage) ?>
      <?= navItem('gallery.php',  '🖼️', 'Gallery',   'gallery',   $currentPage) ?>
      <?= navItem('messages.php', '💬', 'Messages',  'messages',  $currentPage, $unreadCount) ?>
    </div>

    <div class="nav-section">
      <div class="nav-section-label">System</div>
      <?= navItem('settings.php', '⚙️', 'Site Settings', 'settings', $currentPage) ?>
    </div>

  </nav>

  <hr class="sidebar-divider"/>

  <!-- Profile -->
  <div class="sidebar-profile">
    <div class="profile-avatar"><?= htmlspecialchars($initial) ?></div>
    <div class="profile-info">
      <div class="profile-name"><?= htmlspecialchars($admin['name']) ?></div>
      <div class="profile-role">Super Administrator</div>
    </div>
  </div>

  <!-- Logout -->
  <form method="POST" action="logout.php">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>"/>
    <button type="submit" class="btn-logout">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Logout
    </button>
  </form>

</aside>
