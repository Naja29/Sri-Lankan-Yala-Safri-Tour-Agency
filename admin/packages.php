<?php

//  admin/packages

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
requireLogin();

$db      = getDB();
$message = '';
$msgType = '';

// Image upload helper 
function handleImageUpload(string $field): string {
    if (empty($_FILES[$field]['name'])) return '';
    $file    = $_FILES[$field];
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) return '';
    if ($file['size'] > 5 * 1024 * 1024) return '';

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'pkg_' . uniqid() . '.' . $ext;
    $dest     = __DIR__ . '/uploads/packages/' . $filename;

    if (!is_dir(__DIR__ . '/uploads/packages')) {
        mkdir(__DIR__ . '/uploads/packages', 0755, true);
    }
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'admin/uploads/packages/' . $filename;
    }
    return '';
}

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {

    $action = $_POST['action'] ?? '';

    // Add 
    if ($action === 'add') {
        $name      = trim($_POST['name']        ?? '');
        $category  = trim($_POST['category']    ?? '');
        $duration  = trim($_POST['duration']    ?? '');
        $price     = floatval($_POST['price']   ?? 0);
        $price_per = trim($_POST['price_per']   ?? 'Per Person');
        $badge     = trim($_POST['badge_label'] ?? '');
        $desc      = trim($_POST['description'] ?? '');
        $features  = trim($_POST['features']    ?? '');
        $status    = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

        // Image: upload or URL
        $image = handleImageUpload('image_file');
        if (!$image) $image = trim($_POST['image_url'] ?? '');

        if ($name && $category && $duration) {
            $stmt = $db->prepare('INSERT INTO packages (name,category,duration,price,price_per,badge_label,image,description,features,status) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('sssdssssss', $name, $category, $duration, $price, $price_per, $badge, $image, $desc, $features, $status);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_msg']  = '✅ Package added successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_msg']  = '❌ Please fill in all required fields.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: packages.php'); exit;
    }

    // Edit 
    if ($action === 'edit') {
        $id        = intval($_POST['id']         ?? 0);
        $name      = trim($_POST['name']         ?? '');
        $category  = trim($_POST['category']     ?? '');
        $duration  = trim($_POST['duration']     ?? '');
        $price     = floatval($_POST['price']    ?? 0);
        $price_per = trim($_POST['price_per']    ?? 'Per Person');
        $badge     = trim($_POST['badge_label']  ?? '');
        $desc      = trim($_POST['description']  ?? '');
        $features  = trim($_POST['features']     ?? '');
        $status    = in_array($_POST['status'] ?? '', ['active','inactive']) ? $_POST['status'] : 'active';

        $image = handleImageUpload('image_file');
        if (!$image) $image = trim($_POST['image_url'] ?? '');

        if ($id && $name) {
            if ($image) {
                $stmt = $db->prepare('UPDATE packages SET name=?,category=?,duration=?,price=?,price_per=?,badge_label=?,image=?,description=?,features=?,status=? WHERE id=?');
                $stmt->bind_param('sssdssssssi', $name, $category, $duration, $price, $price_per, $badge, $image, $desc, $features, $status, $id);
            } else {
                $stmt = $db->prepare('UPDATE packages SET name=?,category=?,duration=?,price=?,price_per=?,badge_label=?,description=?,features=?,status=? WHERE id=?');
                $stmt->bind_param('sssdsssssi', $name, $category, $duration, $price, $price_per, $badge, $desc, $features, $status, $id);
            }
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_msg']  = '✅ Package updated successfully!';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: packages.php'); exit;
    }

    // Quick Status Toggle 
    if ($action === 'toggle_status') {
        $id  = intval($_POST['id'] ?? 0);
        $val = $_POST['status'] === 'active' ? 'active' : 'inactive';
        if ($id) {
            $stmt = $db->prepare('UPDATE packages SET status=? WHERE id=?');
            $stmt->bind_param('si', $val, $id);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: packages.php' . ($_GET['cat'] ? '?cat=' . urlencode($_GET['cat']) : '')); exit;
    }

    // Duplicate 
    if ($action === 'duplicate') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare('INSERT INTO packages (name,category,duration,price,price_per,badge_label,image,description,features,status) SELECT CONCAT(name," (Copy)"),category,duration,price,price_per,badge_label,image,description,features,"inactive" FROM packages WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_msg']  = '✅ Package duplicated! Edit it to make changes.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: packages.php'); exit;
    }

    // Delete 
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare('DELETE FROM packages WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_msg']  = '🗑️ Package deleted.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: packages.php'); exit;
    }
}

// Flash message 
if (!empty($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Fetch packages 
$search     = trim($_GET['search'] ?? '');
$filterCat  = trim($_GET['cat']    ?? '');
$openAdd    = isset($_GET['action']) && $_GET['action'] === 'add';
$openEditId = intval($_GET['edit'] ?? 0);

// Category counts
$catCountRes = $db->query('SELECT category, COUNT(*) as cnt FROM packages GROUP BY category');
$catCounts   = [];
$totalCount  = 0;
if ($catCountRes) {
    while ($r = $catCountRes->fetch_assoc()) {
        $catCounts[$r['category']] = (int)$r['cnt'];
        $totalCount += (int)$r['cnt'];
    }
}

// Build query
$where  = [];
$params = [];
$types  = '';
if ($search)    { $where[] = '(name LIKE ? OR category LIKE ?)'; $like = "%$search%"; $params[] = $like; $params[] = $like; $types .= 'ss'; }
if ($filterCat) { $where[] = 'category = ?'; $params[] = $filterCat; $types .= 's'; }

$sql = 'SELECT * FROM packages' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC';
if ($params) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $packages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $res      = $db->query($sql);
    $packages = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Edit package
$editPkg = null;
if ($openEditId) {
    $stmt = $db->prepare('SELECT * FROM packages WHERE id=?');
    $stmt->bind_param('i', $openEditId);
    $stmt->execute();
    $editPkg = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($editPkg) $openAdd = false;
}

$csrf       = csrfToken();
$categories = ['Half Day','Full Day','Multi Day','Photography','Premium'];
$pricePers  = ['Per Person','Per Group','Per Jeep'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Packages Manager – YalaSafari Admin</title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="icon" href="../images/icons/favicon.ico"/>
  <link rel="stylesheet" href="css/admin.css"/>
  <link rel="stylesheet" href="css/packages.css"/>
</head>
<body>
<div class="admin-layout">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <div class="topbar-title">
        <h1>📦 Packages Manager</h1>
        <p>Add, edit and manage your safari packages</p>
      </div>
    </div>

    <div class="admin-content">

      <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>" id="flashAlert"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <!-- Toolbar -->
      <div class="pkg-toolbar">
        <form method="GET" action="packages.php" class="search-form">
          <?php if ($filterCat): ?>
            <input type="hidden" name="cat" value="<?= htmlspecialchars($filterCat) ?>"/>
          <?php endif; ?>
          <div class="search-wrap">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="search" placeholder="Search packages..." value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
          </div>
        </form>
        <a href="packages.php?action=add<?= $filterCat ? '&cat='.urlencode($filterCat) : '' ?>" class="btn-add">+ Add New Package</a>
      </div>

      <!-- Category Filter Tabs -->
      <div class="filter-tabs">
        <a href="packages.php<?= $search ? '?search='.urlencode($search) : '' ?>"
           class="filter-tab <?= !$filterCat ? 'active' : '' ?>">
          All <span class="tab-count"><?= $totalCount ?></span>
        </a>
        <?php foreach ($categories as $cat): ?>
          <?php $cnt = $catCounts[$cat] ?? 0; ?>
          <a href="packages.php?cat=<?= urlencode($cat) ?><?= $search ? '&search='.urlencode($search) : '' ?>"
             class="filter-tab <?= $filterCat === $cat ? 'active' : '' ?>">
            <?= $cat ?> <span class="tab-count"><?= $cnt ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Packages Table -->
      <div class="panel">
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:76px">Image</th>
                <th>Package Name</th>
                <th>Category</th>
                <th>Duration</th>
                <th>Price</th>
                <th style="width:130px">Status</th>
                <th style="width:110px; text-align:center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($packages)): ?>
              <tr>
                <td colspan="7" class="table-empty">
                  <?php if ($search || $filterCat): ?>
                    No packages found
                    <?= $filterCat ? "in <strong>$filterCat</strong>" : '' ?>
                    <?= $search ? "matching &ldquo;<strong>" . htmlspecialchars($search) . "</strong>&rdquo;" : '' ?>
                  <?php else: ?>
                    No packages yet — <a href="packages.php?action=add" class="link-add">Add your first package</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($packages as $p): ?>
              <tr>
                <!-- Image -->
                <td>
                  <?php if (!empty($p['image'])): ?>
                    <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" class="pkg-thumb"
                      onerror="this.outerHTML='<div class=\'pkg-thumb-placeholder\'><svg viewBox=\'0 0 24 24\' stroke-width=\'1.5\' style=\'width:18px;height:18px;stroke:#6b8565;fill:none\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><polyline points=\'21 15 16 10 5 21\'/></svg></div>'"/>
                  <?php else: ?>
                    <div class="pkg-thumb-placeholder">
                      <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                      </svg>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Name -->
                <td>
                  <strong><?= htmlspecialchars($p['name']) ?></strong>
                  <?php if (!empty($p['badge_label'])): ?>
                    <span class="pkg-badge"><?= htmlspecialchars($p['badge_label']) ?></span>
                  <?php endif; ?>
                </td>

                <td><?= htmlspecialchars($p['category'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['duration'] ?? '—') ?></td>
                <td>
                  Rs. <?= number_format((float)$p['price']) ?>
                  <span class="price-per">/ <?= htmlspecialchars($p['price_per'] ?? 'person') ?></span>
                </td>

                <!-- Quick Toggle -->
                <td>
                  <form method="POST" action="packages.php" style="margin:0">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                    <input type="hidden" name="action" value="toggle_status"/>
                    <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                    <div class="toggle-wrap">
                      <label class="toggle-switch">
                        <input
                          type="checkbox"
                          name="status"
                          value="active"
                          <?= $p['status'] === 'active' ? 'checked' : '' ?>
                          onchange="this.form.submit()"
                        />
                        <span class="toggle-slider"></span>
                      </label>
                      <span class="toggle-label"><?= ucfirst($p['status']) ?></span>
                    </div>
                  </form>
                </td>

                <!-- Actions -->
                <td>
                  <div class="action-btns">
                    <!-- Edit -->
                    <a href="packages.php?edit=<?= $p['id'] ?>" class="btn-icon btn-edit" title="Edit package">
                      <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </a>
                    <!-- Duplicate -->
                    <form method="POST" action="packages.php" style="margin:0" title="Duplicate package">
                      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                      <input type="hidden" name="action" value="duplicate"/>
                      <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                      <button type="submit" class="btn-icon btn-copy" title="Duplicate">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <rect x="9" y="9" width="13" height="13" rx="2"/>
                          <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                        </svg>
                      </button>
                    </form>
                    <!-- Delete -->
                    <form method="POST" action="packages.php" style="margin:0" onsubmit="return confirm('Delete «<?= htmlspecialchars(addslashes($p['name'])) ?>»? This cannot be undone.')">
                      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                      <input type="hidden" name="action" value="delete"/>
                      <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
                      <button type="submit" class="btn-icon btn-delete" title="Delete">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="3 6 5 6 21 6"/>
                          <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                          <path d="M10 11v6M14 11v6"/>
                          <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                        </svg>
                      </button>
                    </form>
                  </div>
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


<!-- ADD MODAL -->
<div class="modal-backdrop <?= $openAdd ? 'open' : '' ?>" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">➕ Add New Package</h2>
      <a href="packages.php" class="modal-close">✕</a>
    </div>
    <hr class="modal-divider"/>

    <form method="POST" action="packages.php" enctype="multipart/form-data" id="addForm">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
        <input type="hidden" name="action" value="add"/>

        <div class="form-row-2">
          <div class="form-group">
            <label>Package Name <span class="req">*</span></label>
            <input type="text" name="name" required placeholder="e.g. Morning Safari Adventure"/>
          </div>
          <div class="form-group">
            <label>Category <span class="req">*</span></label>
            <div class="select-wrap">
              <select name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?><option><?= $c ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label>Duration <span class="req">*</span></label>
            <input type="text" name="duration" required placeholder="e.g. 4–5 Hours"/>
          </div>
          <div class="form-group">
            <label>Price (LKR) <span class="req">*</span></label>
            <input type="number" name="price" required placeholder="e.g. 15000" min="0" step="0.01"/>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label>Price Per</label>
            <div class="select-wrap">
              <select name="price_per">
                <?php foreach ($pricePers as $pp): ?><option><?= $pp ?></option><?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Badge Label</label>
            <input type="text" name="badge_label" placeholder="e.g. Best Seller"/>
          </div>
        </div>

        <!-- Image Upload -->
        <div class="form-group">
          <label>Package Image</label>
          <div class="img-upload-area" id="addUploadArea">
            <input type="file" name="image_file" id="addImageFile" accept="image/*"/>
            <div class="img-upload-icon">🖼️</div>
            <div class="img-upload-text">
              <strong>Click to upload</strong> or drag and drop<br/>
              JPG, PNG, WEBP — max 5MB
            </div>
          </div>
          <div class="img-preview-wrap" id="addPreviewWrap">
            <img id="addPreviewImg" src="" alt="Preview"/>
            <div class="img-preview-label" id="addPreviewLabel"></div>
            <button type="button" class="img-preview-remove" onclick="clearImage('add')">✕</button>
          </div>
          <div class="img-url-toggle">
            Or <a onclick="toggleUrlInput('add')">enter image URL instead</a>
          </div>
          <input type="text" name="image_url" id="addImageUrl" placeholder="images/packages/package-01.jpg"
            style="display:none; margin-top:8px"/>
        </div>

        <div class="form-group">
          <label>Description <span class="req">*</span></label>
          <input type="text" name="description" required placeholder="Short description of this package"/>
        </div>

        <div class="form-group">
          <label>Features <span class="features-hint">(one per line)</span></label>
          <textarea name="features" placeholder="Professional Guide&#10;4×4 Safari Jeep&#10;Park Entry Fees&#10;Refreshments"></textarea>
        </div>

        <div class="form-group">
          <label>Status</label>
          <div class="select-wrap">
            <select name="status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

      </div><!-- /modal-body -->

      <!-- BIG SAVE BUTTON -->
      <div class="modal-footer">
        <a href="packages.php" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-save">
          <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/>
            <polyline points="7 3 7 8 15 8"/>
          </svg>
          <span>Save Package</span>
        </button>
      </div>
    </form>
  </div>
</div>


<!-- EDIT MODAL -->
<?php if ($editPkg): ?>
<div class="modal-backdrop open" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">✏️ Edit Package</h2>
      <a href="packages.php" class="modal-close">✕</a>
    </div>
    <hr class="modal-divider"/>

    <form method="POST" action="packages.php" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
        <input type="hidden" name="action" value="edit"/>
        <input type="hidden" name="id" value="<?= $editPkg['id'] ?>"/>

        <div class="form-row-2">
          <div class="form-group">
            <label>Package Name <span class="req">*</span></label>
            <input type="text" name="name" required value="<?= htmlspecialchars($editPkg['name']) ?>"/>
          </div>
          <div class="form-group">
            <label>Category <span class="req">*</span></label>
            <div class="select-wrap">
              <select name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $c): ?>
                  <option <?= $editPkg['category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label>Duration <span class="req">*</span></label>
            <input type="text" name="duration" required value="<?= htmlspecialchars($editPkg['duration'] ?? '') ?>"/>
          </div>
          <div class="form-group">
            <label>Price (LKR) <span class="req">*</span></label>
            <input type="number" name="price" required value="<?= (float)$editPkg['price'] ?>" min="0" step="0.01"/>
          </div>
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label>Price Per</label>
            <div class="select-wrap">
              <select name="price_per">
                <?php foreach ($pricePers as $pp): ?>
                  <option <?= ($editPkg['price_per'] ?? '') === $pp ? 'selected' : '' ?>><?= $pp ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Badge Label</label>
            <input type="text" name="badge_label" value="<?= htmlspecialchars($editPkg['badge_label'] ?? '') ?>" placeholder="e.g. Best Seller"/>
          </div>
        </div>

        <!-- Image -->
        <div class="form-group">
          <label>Package Image</label>
          <?php if (!empty($editPkg['image'])): ?>
            <div class="img-preview-wrap visible" id="editPreviewWrap">
              <img id="editPreviewImg" src="../<?= htmlspecialchars($editPkg['image']) ?>" alt="Current image"/>
              <div class="img-preview-label" id="editPreviewLabel">Current image</div>
              <button type="button" class="img-preview-remove" onclick="clearImage('edit')">✕</button>
            </div>
            <div style="margin-top:10px">
          <?php else: ?>
            <div>
          <?php endif; ?>
            <div class="img-upload-area" id="editUploadArea" <?= !empty($editPkg['image']) ? 'style="display:none"' : '' ?>>
              <input type="file" name="image_file" id="editImageFile" accept="image/*"/>
              <div class="img-upload-icon">🖼️</div>
              <div class="img-upload-text"><strong>Click to upload new image</strong><br/>JPG, PNG, WEBP — max 5MB</div>
            </div>
            <div class="img-url-toggle" id="editUrlToggle" <?= !empty($editPkg['image']) ? '' : '' ?>>
              Or <a onclick="toggleUrlInput('edit')">enter image URL instead</a>
            </div>
            <input type="text" name="image_url" id="editImageUrl"
              value="<?= htmlspecialchars($editPkg['image'] ?? '') ?>"
              placeholder="images/packages/package-01.jpg"
              style="display:none; margin-top:8px"/>
          </div>
        </div>

        <div class="form-group">
          <label>Description <span class="req">*</span></label>
          <input type="text" name="description" required value="<?= htmlspecialchars($editPkg['description'] ?? '') ?>"/>
        </div>

        <div class="form-group">
          <label>Features <span class="features-hint">(one per line)</span></label>
          <textarea name="features"><?= htmlspecialchars($editPkg['features'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Status</label>
          <div class="select-wrap">
            <select name="status">
              <option value="active"   <?= ($editPkg['status'] ?? '') === 'active'   ? 'selected' : '' ?>>Active</option>
              <option value="inactive" <?= ($editPkg['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        </div>

      </div><!-- /modal-body -->

      <!-- BIG UPDATE BUTTON -->
      <div class="modal-footer">
        <a href="packages.php" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-update">
          <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          <span>Update Package</span>
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>


<script>
// Close modal on backdrop/Escape 
document.querySelectorAll('.modal-backdrop').forEach(bd => {
  bd.addEventListener('click', e => { if (e.target === bd) window.location.href = 'packages.php'; });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') window.location.href = 'packages.php';
});

// Auto-dismiss flash 
const flash = document.getElementById('flashAlert');
if (flash) {
  setTimeout(() => {
    flash.style.transition = 'opacity 0.5s ease';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
  }, 4000);
}

// Image upload preview 
function setupImagePreview(prefix) {
  const fileInput  = document.getElementById(prefix + 'ImageFile');
  const previewWrap= document.getElementById(prefix + 'PreviewWrap');
  const previewImg = document.getElementById(prefix + 'PreviewImg');
  const previewLbl = document.getElementById(prefix + 'PreviewLabel');
  const uploadArea = document.getElementById(prefix + 'UploadArea');

  if (!fileInput) return;

  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = e => {
      previewImg.src        = e.target.result;
      previewLbl.textContent = file.name;
      previewWrap.classList.add('visible');
      uploadArea.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });

  // Drag and drop
  uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
  uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
  uploadArea.addEventListener('drop', e => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file && file.type.startsWith('image/')) {
      fileInput.files = e.dataTransfer.files;
      fileInput.dispatchEvent(new Event('change'));
    }
  });
}

function clearImage(prefix) {
  const fileInput  = document.getElementById(prefix + 'ImageFile');
  const previewWrap= document.getElementById(prefix + 'PreviewWrap');
  const uploadArea = document.getElementById(prefix + 'UploadArea');
  if (fileInput)   fileInput.value = '';
  if (previewWrap) previewWrap.classList.remove('visible');
  if (uploadArea)  uploadArea.style.display = '';
}

function toggleUrlInput(prefix) {
  const urlInput  = document.getElementById(prefix + 'ImageUrl');
  const uploadArea= document.getElementById(prefix + 'UploadArea');
  const show = urlInput.style.display === 'none';
  urlInput.style.display   = show ? 'block' : 'none';
  uploadArea.style.display = show ? 'none'  : '';
}

setupImagePreview('add');
setupImagePreview('edit');
</script>

</body>
</html>
