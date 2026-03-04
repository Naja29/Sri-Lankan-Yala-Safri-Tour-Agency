<?php

//  admin/hero slider

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
requireLogin();

$db = getDB();

// Ensure table exists 
$db->query("CREATE TABLE IF NOT EXISTS `hero_slides` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `image`      VARCHAR(255) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `status`     ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Image upload helper 
function uploadHeroImage(string $field): string {
    if (empty($_FILES[$field]['name'])) return '';
    $file    = $_FILES[$field];
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return '';
    if ($file['size'] > 8 * 1024 * 1024) return '';

    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $dir  = __DIR__ . '/uploads/hero/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'hero_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return 'admin/uploads/hero/' . $filename;
    }
    return '';
}

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    // Add new slide
    if ($action === 'add') {
        $image = uploadHeroImage('image_file');
        if ($image) {
            $order = (int)($db->query("SELECT MAX(sort_order) FROM hero_slides")->fetch_row()[0] ?? 0) + 1;
            $stmt  = $db->prepare("INSERT INTO hero_slides (image, sort_order, status) VALUES (?,?,'active')");
            $stmt->bind_param('si', $image, $order);
            $stmt->execute();
            $_SESSION['flash_msg']  = '✅ Hero slide added successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_msg']  = '❌ Please upload a valid image (JPG, PNG, WebP, max 8MB).';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: hero.php'); exit;
    }

    // Toggle status
    if ($action === 'toggle') {
        $id   = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare("UPDATE hero_slides SET status = IF(status='active','inactive','active') WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        header('Location: hero.php'); exit;
    }

    // Delete slide
    if ($action === 'delete') {
        $id   = (int)($_POST['id'] ?? 0);
        // Get image path to delete file
        $row  = $db->query("SELECT image FROM hero_slides WHERE id=$id")->fetch_row();
        if ($row && str_starts_with($row[0], 'admin/uploads/')) {
            $file = __DIR__ . '/../' . $row[0];
            if (file_exists($file)) unlink($file);
        }
        $db->query("DELETE FROM hero_slides WHERE id=$id");
        // Re-order remaining
        $slides = $db->query("SELECT id FROM hero_slides ORDER BY sort_order ASC");
        $i = 1;
        while ($s = $slides->fetch_row()) {
            $db->query("UPDATE hero_slides SET sort_order=$i WHERE id={$s[0]}");
            $i++;
        }
        $_SESSION['flash_msg']  = '✅ Slide deleted.';
        $_SESSION['flash_type'] = 'success';
        header('Location: hero.php'); exit;
    }

    // Reorder (drag & drop order save)
    if ($action === 'reorder') {
        $ids = $_POST['ids'] ?? [];
        foreach ($ids as $i => $id) {
            $id  = (int)$id;
            $ord = $i + 1;
            $db->query("UPDATE hero_slides SET sort_order=$ord WHERE id=$id");
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// Load slides 
$slides = $db->query("SELECT * FROM hero_slides ORDER BY sort_order ASC")->fetch_all(MYSQLI_ASSOC);
$csrf   = csrfToken();

// Flash message 
$flash     = $_SESSION['flash_msg']  ?? '';
$flashType = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hero Slider – YalaSafari Admin</title>
  <link rel="stylesheet" href="css/admin.css"/>
  <style>
    .hero-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 24px;
    }
    .hero-slide-card {
      background: var(--white);
      border-radius: 12px;
      border: 1.5px solid var(--border);
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: all 0.2s ease;
      cursor: grab;
    }
    .hero-slide-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
    .hero-slide-card.inactive { opacity: 0.5; }
    .hero-slide-card.dragging { opacity: 0.3; cursor: grabbing; }
    .hero-slide-img {
      width: 100%;
      height: 170px;
      object-fit: cover;
      display: block;
    }
    .hero-slide-body {
      padding: 14px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .hero-slide-order {
      font-size: 0.78rem;
      color: var(--text-muted);
      font-weight: 600;
    }
    .slide-badge {
      padding: 3px 10px;
      border-radius: 50px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
    }
    .slide-badge.active  { background: #e6f9ee; color: #1a7a3a; }
    .slide-badge.inactive{ background: #fef2f2; color: #c0392b; }
    .hero-slide-actions { display: flex; gap: 6px; }
    .btn-toggle, .btn-del {
      border: none;
      border-radius: 6px;
      padding: 6px 12px;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-toggle { background: var(--bg-light); color: var(--text-body); }
    .btn-toggle:hover { background: var(--green-pale); color: var(--green-primary); }
    .btn-del { background: #fef2f2; color: #c0392b; }
    .btn-del:hover { background: #c0392b; color: white; }
    .upload-panel {
      background: var(--white);
      border: 2px dashed var(--border);
      border-radius: 12px;
      padding: 32px;
      text-align: center;
      margin-bottom: 32px;
      transition: border-color 0.2s;
    }
    .upload-panel:hover { border-color: var(--green-accent); }
    .upload-panel h3 { margin-bottom: 8px; color: var(--text-primary); }
    .upload-panel p  { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 20px; }
    .upload-row { display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; }
    .drag-hint {
      text-align: center;
      color: var(--text-muted);
      font-size: 0.82rem;
      margin-bottom: 8px;
    }
    .drag-hint svg { vertical-align: middle; margin-right: 4px; }
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: var(--text-muted);
    }
    .live-preview-banner {
      background: linear-gradient(135deg, #1a3a1a, #2d5a27);
      color: white;
      border-radius: 10px;
      padding: 14px 20px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.88rem;
    }
    .live-preview-banner a { color: #7cc142; text-decoration: underline; }
  </style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<main class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-title">
      <h1 class="page-title">🖼️ Hero Slider</h1>
      <p class="page-subtitle">Manage the homepage slideshow images</p>
    </div>
  </div>

  <div class="admin-content">

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flashType === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- Live preview banner -->
    <div class="live-preview-banner">
      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Changes appear instantly on the <a href="../index.php" target="_blank">homepage</a>. Drag cards to reorder slides. At least 1 active slide required.
    </div>

    <!-- Upload new slide -->
    <div class="upload-panel">
      <h3>➕ Add New Slide</h3>
      <p>Upload a landscape image for best results. Recommended: 1920×1080px, JPG or WebP, max 8MB.</p>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
        <input type="hidden" name="action" value="add"/>
        <div class="upload-row">
          <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" required
                 style="padding: 8px; border: 1.5px solid var(--border); border-radius: 8px;"/>
          <button type="submit" class="btn btn-primary">Upload & Add Slide</button>
        </div>
      </form>
    </div>

    <!-- Slides grid -->
    <?php if (empty($slides)): ?>
      <div class="empty-state">
        <p>No hero slides yet. Upload your first image above!</p>
      </div>
    <?php else: ?>
      <div class="drag-hint">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="5 9 2 12 5 15"/><polyline points="19 9 22 12 19 15"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
        Drag and drop cards to reorder the slideshow
      </div>
      <div class="hero-grid" id="heroGrid">
        <?php foreach ($slides as $slide): ?>
          <div class="hero-slide-card <?= $slide['status'] === 'inactive' ? 'inactive' : '' ?>"
               data-id="<?= $slide['id'] ?>">
            <img class="hero-slide-img"
                 src="../<?= htmlspecialchars($slide['image']) ?>"
                 alt="Hero slide"
                 onerror="this.src='../images/hero/hero-1.jpg'"/>
            <div class="hero-slide-body">
              <div>
                <div class="hero-slide-order">Slide #<?= htmlspecialchars($slide['sort_order']) ?></div>
                <span class="slide-badge <?= $slide['status'] ?>"><?= $slide['status'] ?></span>
              </div>
              <div class="hero-slide-actions">
                <form method="POST" style="display:inline">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                  <input type="hidden" name="action" value="toggle"/>
                  <input type="hidden" name="id" value="<?= $slide['id'] ?>"/>
                  <button type="submit" class="btn-toggle">
                    <?= $slide['status'] === 'active' ? 'Disable' : 'Enable' ?>
                  </button>
                </form>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Delete this slide?')">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                  <input type="hidden" name="action" value="delete"/>
                  <input type="hidden" name="id" value="<?= $slide['id'] ?>"/>
                  <button type="submit" class="btn-del">Delete</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<script>
// Drag & Drop Reorder 
(function() {
  const grid = document.getElementById('heroGrid');
  if (!grid) return;

  let dragging = null;

  grid.querySelectorAll('.hero-slide-card').forEach(card => {
    card.draggable = true;

    card.addEventListener('dragstart', () => {
      dragging = card;
      setTimeout(() => card.classList.add('dragging'), 0);
    });

    card.addEventListener('dragend', () => {
      card.classList.remove('dragging');
      dragging = null;
      saveOrder();
    });

    card.addEventListener('dragover', e => {
      e.preventDefault();
      if (!dragging || dragging === card) return;
      const rect   = card.getBoundingClientRect();
      const midX   = rect.left + rect.width / 2;
      if (e.clientX < midX) {
        grid.insertBefore(dragging, card);
      } else {
        grid.insertBefore(dragging, card.nextSibling);
      }
    });
  });

  function saveOrder() {
    const ids = [...grid.querySelectorAll('.hero-slide-card')].map(c => c.dataset.id);
    fetch('hero.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'reorder',
        csrf_token: '<?= $csrf ?>',
        'ids[]': ids
      }).toString() + '&' + ids.map((id, i) => `ids[${i}]=${id}`).join('&')
    }).then(r => r.json()).then(data => {
      // Update slide numbers visually
      grid.querySelectorAll('.hero-slide-card').forEach((card, i) => {
        card.querySelector('.hero-slide-order').textContent = `Slide #${i + 1}`;
      });
    });
  }
})();
</script>

</body>
</html>
