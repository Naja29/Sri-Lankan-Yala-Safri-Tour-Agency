<?php

//  admin/testimonials

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
requireLogin();

$db = getDB();

// Ensure table exists 
$db->query("CREATE TABLE IF NOT EXISTS `testimonials` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `location`   VARCHAR(100),
  `rating`     TINYINT DEFAULT 5,
  `message`    TEXT NOT NULL,
  `photo`      VARCHAR(255),
  `source`     ENUM('website','manual') DEFAULT 'website',
  `status`     ENUM('pending','approved','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Photo upload helper 
function uploadReviewPhoto(string $field): string {
    if (empty($_FILES[$field]['name'])) return '';
    $file    = $_FILES[$field];
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return '';
    if ($file['size'] > 3 * 1024 * 1024) return '';
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $dir  = __DIR__ . '/uploads/testimonials/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'review_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return 'admin/uploads/testimonials/' . $filename;
    }
    return '';
}

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    // Approve / Reject / Delete
    if (in_array($action, ['approve','reject','delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($action === 'approve') {
            $db->query("UPDATE testimonials SET status='approved' WHERE id=$id");
            $_SESSION['flash_msg']  = '✅ Review approved and now showing on homepage.';
        } elseif ($action === 'reject') {
            $db->query("UPDATE testimonials SET status='rejected' WHERE id=$id");
            $_SESSION['flash_msg']  = '🚫 Review rejected.';
        } elseif ($action === 'delete') {
            $row = $db->query("SELECT photo FROM testimonials WHERE id=$id")->fetch_row();
            if ($row && $row[0] && str_starts_with($row[0], 'admin/uploads/')) {
                $f = __DIR__ . '/../' . $row[0];
                if (file_exists($f)) unlink($f);
            }
            $db->query("DELETE FROM testimonials WHERE id=$id");
            $_SESSION['flash_msg']  = '🗑️ Review deleted.';
        }
        $_SESSION['flash_type'] = 'success';
        header('Location: testimonials.php'); exit;
    }

    // Add manually
    if ($action === 'add_manual') {
        $name     = trim($_POST['name']     ?? '');
        $location = trim($_POST['location'] ?? '');
        $rating   = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $message  = trim($_POST['message']  ?? '');
        $status   = $_POST['status'] ?? 'approved';
        $photo    = uploadReviewPhoto('photo_file');

        if ($name && $message) {
            $stmt = $db->prepare("INSERT INTO testimonials (name, location, rating, message, photo, source, status)
                                  VALUES (?,?,?,?,?,'manual',?)");
            $stmt->bind_param('ssisss', $name, $location, $rating, $message, $photo, $status);
            $stmt->execute();
            $_SESSION['flash_msg']  = '✅ Review added successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_msg']  = '❌ Name and message are required.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: testimonials.php'); exit;
    }
}

// Load data 
$filter  = $_GET['filter'] ?? 'all';
$where   = $filter !== 'all' ? "WHERE status='$filter'" : '';
$reviews = $db->query("SELECT * FROM testimonials $where ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$counts = [];
foreach (['pending','approved','rejected'] as $s) {
    $counts[$s] = $db->query("SELECT COUNT(*) FROM testimonials WHERE status='$s'")->fetch_row()[0];
}
$counts['all'] = array_sum($counts);

$csrf  = csrfToken();
$flash = $_SESSION['flash_msg']  ?? '';
$ftype = $_SESSION['flash_type'] ?? '';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

function stars(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
function initials(string $name): string {
    $parts = explode(' ', trim($name));
    return strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Testimonials – YalaSafari Admin</title>
  <link rel="stylesheet" href="css/admin.css"/>
  <link rel="stylesheet" href="css/settings.css"/>
  <style>
    /* Tabs */
    .filter-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
    .filter-tab  { padding:7px 18px; border-radius:50px; font-size:0.82rem; font-weight:600;
                   border:1.5px solid var(--border); color:var(--text-muted); text-decoration:none;
                   transition:all 0.2s; background:var(--white); }
    .filter-tab:hover, .filter-tab.active { background:var(--green-accent); color:white; border-color:var(--green-accent); }
    .tab-count { background:rgba(255,255,255,0.25); border-radius:50px; padding:1px 7px; font-size:0.75rem; margin-left:4px; }
    .filter-tab:not(.active) .tab-count { background:var(--bg-light); color:var(--text-muted); }

    /* Review Cards */
    .review-card { background:var(--white); border:1.5px solid var(--border); border-radius:12px;
                   padding:20px 24px; margin-bottom:14px; box-shadow:var(--shadow-sm); }
    .review-card.pending  { border-left:4px solid #f59e0b; }
    .review-card.approved { border-left:4px solid #22c55e; }
    .review-card.rejected { border-left:4px solid #ef4444; opacity:0.7; }

    .review-header { display:flex; align-items:center; gap:14px; margin-bottom:12px; }
    .review-avatar { width:46px; height:46px; border-radius:50%; object-fit:cover; flex-shrink:0; }
    .review-avatar-init { width:46px; height:46px; border-radius:50%; background:var(--green-pale);
                          color:var(--green-dark); font-weight:700; font-size:1rem;
                          display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .review-name     { font-weight:700; color:var(--text-primary); font-size:0.95rem; }
    .review-location { font-size:0.8rem; color:var(--text-muted); }
    .review-stars    { color:#f59e0b; font-size:1rem; letter-spacing:1px; }
    .review-meta     { margin-left:auto; text-align:right; font-size:0.78rem; color:var(--text-muted); }
    .status-pill { display:inline-block; padding:3px 10px; border-radius:50px; font-size:0.72rem;
                   font-weight:700; text-transform:uppercase; }
    .status-pill.pending  { background:#fef3c7; color:#92400e; }
    .status-pill.approved { background:#dcfce7; color:#166534; }
    .status-pill.rejected { background:#fee2e2; color:#991b1b; }
    .review-source { font-size:0.72rem; color:var(--text-muted); margin-top:2px; }

    .review-message { color:var(--text-body); line-height:1.7; font-size:0.92rem;
                      padding:12px 14px; background:var(--bg-light); border-radius:8px; margin-bottom:14px; }

    .review-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .btn-approve { background:#dcfce7; color:#166534; border:none; border-radius:7px;
                   padding:6px 14px; font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s; }
    .btn-approve:hover { background:#22c55e; color:white; }
    .btn-reject  { background:#fee2e2; color:#991b1b; border:none; border-radius:7px;
                   padding:6px 14px; font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s; }
    .btn-reject:hover { background:#ef4444; color:white; }
    .btn-delete  { background:var(--bg-light); color:var(--text-muted); border:none; border-radius:7px;
                   padding:6px 14px; font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s; margin-left:auto; }
    .btn-delete:hover { background:#ef4444; color:white; }

    /* Add Manual Panel */
    .add-panel { margin-bottom: 28px; }
    .add-panel-header {
      background: var(--green-dark); color: Black; padding: 16px 22px;
      font-weight: 700; font-size: 0.95rem; cursor: pointer;
      display: flex; align-items: center; justify-content: space-between;
      border-radius: 12px 12px 0 0; user-select: none;
    }
    .add-panel-header .toggle-icon { font-size: 1.3rem; transition: transform 0.2s; }
    .add-panel-header.open .toggle-icon { transform: rotate(45deg); }
    .add-panel-body {
      display: none;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-top: none;
      border-radius: 0 0 12px 12px;
      padding: 28px 28px 20px;
      box-shadow: var(--shadow-sm);
    }
    .add-panel-body.open { display: block; }
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .form-group textarea {
      width: 100%; padding: 10px 13px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: 'Poppins', sans-serif;
      font-size: 0.86rem; color: var(--text-dark);
      background: var(--white); outline: none;
      transition: var(--transition); resize: vertical;
    }
    .form-group textarea:focus {
      border-color: var(--green-accent);
      box-shadow: 0 0 0 3px rgba(94,184,50,0.1);
    }
    .form-group input[type="file"] {
      width: 100%; padding: 8px 12px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 0.84rem; background: var(--bg-light);
      cursor: pointer;
    }
    /* Star selector */
    .star-select { display: flex; flex-direction: row-reverse; gap: 4px; margin-top: 6px; }
    .star-select input { display: none; }
    .star-select label {
      font-size: 1.8rem; color: #d1d5db; cursor: pointer; transition: color 0.12s; line-height: 1;
    }
    .star-select input:checked ~ label,
    .star-select label:hover,
    .star-select label:hover ~ label { color: #f59e0b; }
    .field-hint { font-size: 0.76rem; color: var(--text-muted); margin-top: 4px; }

    .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); font-size:0.95rem; }
  </style>
</head>
<body>

<?php require_once 'includes/sidebar.php'; ?>

<main class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-title">
      <h1>⭐ Testimonials</h1>
      <p>Manage guest reviews — approve, reject or add manually</p>
    </div>
  </div>

  <div class="admin-content">

    <?php if ($flash): ?>
      <div class="alert alert-<?= $ftype === 'success' ? 'success' : 'error' ?>"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- Add Manually Panel -->
    <div class="add-panel">
      <div class="add-panel-header" onclick="this.classList.toggle('open'); this.nextElementSibling.classList.toggle('open')">
        ✍️ Add Review Manually
        <span class="toggle-icon">＋</span>
      </div>
      <div class="add-panel-body">
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
          <input type="hidden" name="action" value="add_manual"/>

          <div class="form-grid-2" style="margin-bottom:16px">
            <div class="form-group">
              <label>Guest Name *</label>
              <input type="text" name="name" placeholder="e.g. Sarah Mitchell" required/>
            </div>
            <div class="form-group">
              <label>Location / Country</label>
              <input type="text" name="location" placeholder="e.g. United Kingdom"/>
            </div>
          </div>

          <div class="form-grid-3" style="margin-bottom:16px">
            <div class="form-group">
              <label>Star Rating</label>
              <div class="star-select">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>/>
                  <label for="star<?= $i ?>">★</label>
                <?php endfor; ?>
              </div>
            </div>
            <div class="form-group">
              <label>Publish Status</label>
              <select name="status">
                <option value="approved">✅ Approved — show on site</option>
                <option value="pending">⏳ Pending — review later</option>
              </select>
            </div>
            <div class="form-group">
              <label>Guest Photo <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
              <input type="file" name="photo_file" accept="image/*"/>
              <p class="field-hint">JPG / PNG / WebP, max 3MB</p>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:20px">
            <label>Review Message *</label>
            <textarea name="message" rows="4" placeholder="Write the guest's review here..." required></textarea>
          </div>

          <div style="display:flex; align-items:center; gap:12px">
            <button type="submit" class="btn-save" style="margin:0">Add Review</button>
            <span style="font-size:0.8rem;color:var(--text-muted)">⭐ This will be added under your selected status</span>
          </div>
        </form>
      </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
      <?php foreach (['all'=>'All','pending'=>'⏳ Pending','approved'=>'✅ Approved','rejected'=>'🚫 Rejected'] as $key => $label): ?>
        <a href="?filter=<?= $key ?>" class="filter-tab <?= $filter === $key ? 'active' : '' ?>">
          <?= $label ?><span class="tab-count"><?= $counts[$key] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Reviews List -->
    <?php if (empty($reviews)): ?>
      <div class="empty-state">
        <p>No reviews found<?= $filter !== 'all' ? " with status <strong>$filter</strong>" : '' ?>.</p>
      </div>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div class="review-card <?= $r['status'] ?>">
          <div class="review-header">
            <?php if (!empty($r['photo'])): ?>
              <img class="review-avatar" src="../<?= htmlspecialchars($r['photo']) ?>" alt="<?= htmlspecialchars($r['name']) ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"/>
              <div class="review-avatar-init" style="display:none"><?= initials($r['name']) ?></div>
            <?php else: ?>
              <div class="review-avatar-init"><?= initials($r['name']) ?></div>
            <?php endif; ?>
            <div>
              <div class="review-name"><?= htmlspecialchars($r['name']) ?></div>
              <?php if ($r['location']): ?>
                <div class="review-location">📍 <?= htmlspecialchars($r['location']) ?></div>
              <?php endif; ?>
              <div class="review-stars"><?= stars((int)$r['rating']) ?></div>
            </div>
            <div class="review-meta">
              <span class="status-pill <?= $r['status'] ?>"><?= $r['status'] ?></span>
              <div class="review-source"><?= $r['source'] === 'manual' ? '✍️ Manual' : '🌐 Website' ?></div>
              <div><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
            </div>
          </div>

          <div class="review-message">"<?= htmlspecialchars($r['message']) ?>"</div>

          <div class="review-actions">
            <?php if ($r['status'] !== 'approved'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                <input type="hidden" name="action" value="approve"/>
                <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
                <button type="submit" class="btn-approve">✅ Approve</button>
              </form>
            <?php endif; ?>
            <?php if ($r['status'] !== 'rejected'): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                <input type="hidden" name="action" value="reject"/>
                <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
                <button type="submit" class="btn-reject">🚫 Reject</button>
              </form>
            <?php endif; ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this review permanently?')">
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
              <input type="hidden" name="action" value="delete"/>
              <input type="hidden" name="id" value="<?= $r['id'] ?>"/>
              <button type="submit" class="btn-delete">🗑️ Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</main>

</body>
</html>
