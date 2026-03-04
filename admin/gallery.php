<?php

//  admin/gallery.php — Gallery Manager

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
requireLogin();

$db = getDB();

// Image upload helper 
function handleImageUpload(string $field): string {
    if (empty($_FILES[$field]['name'])) return '';
    $file    = $_FILES[$field];
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed) || $file['size'] > 5 * 1024 * 1024) return '';
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'gal_' . uniqid() . '.' . $ext;
    $dir      = __DIR__ . '/uploads/gallery/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        return 'admin/uploads/gallery/' . $filename;
    }
    return '';
}

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {

    $action = $_POST['action'] ?? '';

    // Add 
    if ($action === 'add') {
        $name    = trim($_POST['name']        ?? '');
        $category= trim($_POST['category']    ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $image   = handleImageUpload('image_file');
        if (!$image) $image = trim($_POST['image_url'] ?? '');

        if ($name && $image) {
            $stmt = $db->prepare('INSERT INTO gallery (name, description, image, category) VALUES (?,?,?,?)');
            $stmt->bind_param('ssss', $name, $desc, $image, $category);
            $stmt->execute(); $stmt->close();
            $_SESSION['flash_msg']  = '✅ Photo added successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_msg']  = '❌ Title and image are required.';
            $_SESSION['flash_type'] = 'error';
        }
        header('Location: gallery.php'); exit;
    }

    // Edit 
    if ($action === 'edit') {
        $id      = intval($_POST['id']        ?? 0);
        $name    = trim($_POST['name']        ?? '');
        $category= trim($_POST['category']    ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $image   = handleImageUpload('image_file');
        if (!$image) $image = trim($_POST['image_url'] ?? '');

        if ($id && $name) {
            if ($image) {
                $stmt = $db->prepare('UPDATE gallery SET name=?,description=?,image=?,category=? WHERE id=?');
                $stmt->bind_param('ssssi', $name, $desc, $image, $category, $id);
            } else {
                $stmt = $db->prepare('UPDATE gallery SET name=?,description=?,category=? WHERE id=?');
                $stmt->bind_param('sssi', $name, $desc, $category, $id);
            }
            $stmt->execute(); $stmt->close();
            $_SESSION['flash_msg']  = '✅ Photo updated successfully!';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: gallery.php'); exit;
    }

    // Delete 
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare('DELETE FROM gallery WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute(); $stmt->close();
            $_SESSION['flash_msg']  = '🗑️ Photo deleted.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: gallery.php'); exit;
    }
}

// Flash 
$message = $msgType = '';
if (!empty($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Fetch gallery 
$search     = trim($_GET['search'] ?? '');
$openAdd    = isset($_GET['action']) && $_GET['action'] === 'add';
$openEditId = intval($_GET['edit'] ?? 0);

if ($search) {
    $like = "%{$search}%";
    $stmt = $db->prepare('SELECT * FROM gallery WHERE name LIKE ? OR description LIKE ? OR category LIKE ? ORDER BY created_at DESC');
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $photos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $res    = $db->query('SELECT * FROM gallery ORDER BY created_at DESC');
    $photos = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Edit photo
$editPhoto = null;
if ($openEditId) {
    $stmt = $db->prepare('SELECT * FROM gallery WHERE id=?');
    $stmt->bind_param('i', $openEditId);
    $stmt->execute();
    $editPhoto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($editPhoto) $openAdd = false;
}

$csrf       = csrfToken();
$categories = ['Wildlife','Landscape','Birds','Safari Jeep','Sunset','People','Vegetation','Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gallery Manager – YalaSafari Admin</title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="icon" href="../images/icons/favicon.ico"/>
  <link rel="stylesheet" href="css/admin.css"/>
  <link rel="stylesheet" href="css/gallery.css"/>
</head>
<body>
<div class="admin-layout">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <div class="topbar-title">
        <h1>🖼️ Gallery Manager</h1>
        <p>Add, edit and manage your photo gallery</p>
      </div>
    </div>

    <div class="admin-content">

      <!-- Flash -->
      <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>" id="flashAlert"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <!-- Toolbar -->
      <div class="gal-toolbar">
        <form method="GET" action="gallery.php" class="search-form">
          <div class="search-wrap">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="search" placeholder="Search Photos..."
              value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
          </div>
        </form>
        <a href="gallery.php?action=add" class="btn-add">+ Add New Photo</a>
      </div>

      <!-- Gallery Grid -->
      <div class="gallery-grid">
        <?php if (empty($photos)): ?>
          <div class="gal-empty">
            <div class="gal-empty-icon">🖼️</div>
            <p><?= $search
              ? 'No photos found for &ldquo;<strong>' . htmlspecialchars($search) . '</strong>&rdquo;'
              : 'No photos yet — add your first photo!' ?>
            </p>
            <?php if (!$search): ?>
              <a href="gallery.php?action=add" class="btn-add">+ Add First Photo</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <?php foreach ($photos as $p): ?>
          <div class="gal-card">

            <!-- Image -->
            <div class="gal-card-img">
              <?php if (!empty($p['category'])): ?>
                <span class="gal-cat-badge"><?= htmlspecialchars($p['category']) ?></span>
              <?php endif; ?>

              <?php if (!empty($p['image'])): ?>
                <img
                  src="../<?= htmlspecialchars($p['image']) ?>"
                  alt="<?= htmlspecialchars($p['name']) ?>"
                  onerror="this.parentElement.innerHTML='<svg viewBox=\'0 0 24 24\' stroke-width=\'1.5\' stroke-linecap=\'round\' stroke-linejoin=\'round\' style=\'width:40px;height:40px;stroke:#c0d4b8;fill:none\'><rect x=\'3\' y=\'3\' width=\'18\' height=\'18\' rx=\'2\'/><circle cx=\'8.5\' cy=\'8.5\' r=\'1.5\'/><polyline points=\'21 15 16 10 5 21\'/></svg>'"
                />
              <?php else: ?>
                <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="18" height="18" rx="2"/>
                  <circle cx="8.5" cy="8.5" r="1.5"/>
                  <polyline points="21 15 16 10 5 21"/>
                </svg>
              <?php endif; ?>
            </div>

            <!-- Body -->
            <div class="gal-card-body">
              <div class="gal-card-title"><?= htmlspecialchars($p['name']) ?></div>
              <?php if (!empty($p['description'])): ?>
                <div class="gal-card-caption"><?= htmlspecialchars($p['description']) ?></div>
              <?php else: ?>
                <div class="gal-card-caption" style="color:#ccd9c7;font-style:italic">No caption</div>
              <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="gal-card-footer">
              <a href="gallery.php?edit=<?= $p['id'] ?>" class="btn-icon btn-edit" title="Edit photo">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                  <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
              </a>
              <form method="POST" action="gallery.php" onsubmit="return confirm('Delete «<?= htmlspecialchars(addslashes($p['name'])) ?>»?')">
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

          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>


<!-- ADD MODAL -->
<div class="modal-backdrop <?= $openAdd ? 'open' : '' ?>" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">Add New Photo</h2>
      <a href="gallery.php" class="modal-close">✕</a>
    </div>
    <hr class="modal-divider"/>
    <form method="POST" action="gallery.php" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
        <input type="hidden" name="action" value="add"/>

        <!-- Image Upload -->
        <div class="form-group">
          <label>Image <span class="req">*</span></label>
          <div class="img-upload-area" id="addUploadArea">
            <input type="file" name="image_file" id="addImageFile" accept="image/*"/>
            <div class="img-upload-cloud">
              <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 16 12 12 8 16"/>
                <line x1="12" y1="12" x2="12" y2="21"/>
                <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
              </svg>
            </div>
            <div class="img-upload-info">
              <div class="img-upload-title"><span>Choose Cover Image</span></div>
              <div class="img-upload-sub">JPG, PNG, WEBP — max 5MB</div>
            </div>
          </div>
          <div class="img-preview-wrap" id="addPreviewWrap">
            <img id="addPreviewImg" src="" alt="Preview"/>
            <div class="img-preview-label" id="addPreviewLabel"></div>
            <button type="button" class="img-preview-remove" onclick="clearImg('add')">✕</button>
          </div>
          <div class="img-url-toggle">
            Or <a onclick="toggleUrl('add')">enter image URL instead</a>
          </div>
          <input type="text" name="image_url" id="addImageUrl"
            placeholder="e.g. images/gallery/photo-01.jpg"
            style="display:none; margin-top:8px;
              width:100%; padding:12px 14px;
              border:1.5px solid var(--border); border-radius:var(--radius-sm);
              font-family:'Poppins',sans-serif; font-size:0.88rem;
              outline:none;"/>
        </div>

        <!-- Title -->
        <div class="form-group">
          <label>Title <span class="req">*</span></label>
          <input type="text" name="name" required placeholder="e.g. Leopard at Sunrise"/>
        </div>

        <!-- Category -->
        <div class="form-group">
          <label>Category <span class="req">*</span></label>
          <div class="select-wrap">
            <select name="category" required>
              <option value="">Select Category</option>
              <?php foreach ($categories as $c): ?>
                <option><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Caption -->
        <div class="form-group">
          <label>Caption</label>
          <textarea name="description" rows="4" placeholder="Short description for this photo"></textarea>
        </div>

      </div>
      <div class="modal-footer">
        <a href="gallery.php" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-save">
          <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/>
            <polyline points="7 3 7 8 15 8"/>
          </svg>
          <span>Save Photo</span>
        </button>
      </div>
    </form>
  </div>
</div>


<!-- EDIT MODAL -->
<?php if ($editPhoto): ?>
<div class="modal-backdrop open" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">✏️ Edit Photo</h2>
      <a href="gallery.php" class="modal-close">✕</a>
    </div>
    <hr class="modal-divider"/>
    <form method="POST" action="gallery.php" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
        <input type="hidden" name="action" value="edit"/>
        <input type="hidden" name="id" value="<?= $editPhoto['id'] ?>"/>

        <!-- Image -->
        <div class="form-group">
          <label>Image</label>
          <?php if (!empty($editPhoto['image'])): ?>
            <div class="img-preview-wrap visible" id="editPreviewWrap">
              <img id="editPreviewImg" src="../<?= htmlspecialchars($editPhoto['image']) ?>" alt="Current"/>
              <div class="img-preview-label">Current image</div>
              <button type="button" class="img-preview-remove" onclick="clearImg('edit')">✕</button>
            </div>
            <div id="editUploadAreaWrap" style="display:none; margin-top:10px">
          <?php else: ?>
            <div id="editUploadAreaWrap">
          <?php endif; ?>
              <div class="img-upload-area" id="editUploadArea">
                <input type="file" name="image_file" id="editImageFile" accept="image/*"/>
                <div class="img-upload-cloud">
                  <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 16 12 12 8 16"/>
                    <line x1="12" y1="12" x2="12" y2="21"/>
                    <path d="M20.39 18.39A5 5 0 0018 9h-1.26A8 8 0 103 16.3"/>
                  </svg>
                </div>
                <div class="img-upload-info">
                  <div class="img-upload-title"><span>Choose New Image</span></div>
                  <div class="img-upload-sub">JPG, PNG, WEBP — max 5MB</div>
                </div>
              </div>
            </div>
          <div class="img-url-toggle">
            Or <a onclick="toggleUrl('edit')">enter image URL instead</a>
          </div>
          <input type="text" name="image_url" id="editImageUrl"
            value="<?= htmlspecialchars($editPhoto['image'] ?? '') ?>"
            placeholder="images/gallery/photo-01.jpg"
            style="display:none; margin-top:8px;
              width:100%; padding:12px 14px;
              border:1.5px solid var(--border); border-radius:var(--radius-sm);
              font-family:'Poppins',sans-serif; font-size:0.88rem;
              outline:none;"/>
        </div>

        <div class="form-group">
          <label>Title <span class="req">*</span></label>
          <input type="text" name="name" required value="<?= htmlspecialchars($editPhoto['name']) ?>"/>
        </div>

        <div class="form-group">
          <label>Category <span class="req">*</span></label>
          <div class="select-wrap">
            <select name="category" required>
              <option value="">Select Category</option>
              <?php foreach ($categories as $c): ?>
                <option <?= ($editPhoto['category'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Caption</label>
          <textarea name="description" rows="4"><?= htmlspecialchars($editPhoto['description'] ?? '') ?></textarea>
        </div>

      </div>
      <div class="modal-footer">
        <a href="gallery.php" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-update">
          <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          <span>Update Photo</span>
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>


<script>
// Close modal 
document.querySelectorAll('.modal-backdrop').forEach(bd => {
  bd.addEventListener('click', e => { if (e.target === bd) go(); });
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') go(); });
function go() { window.location.href = 'gallery.php'; }

// Flash dismiss 
const flash = document.getElementById('flashAlert');
if (flash) {
  setTimeout(() => {
    flash.style.transition = 'opacity 0.5s ease';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
  }, 4000);
}

// Image upload preview 
function setupPreview(prefix) {
  const fileInput   = document.getElementById(prefix + 'ImageFile');
  const previewWrap = document.getElementById(prefix + 'PreviewWrap');
  const previewImg  = document.getElementById(prefix + 'PreviewImg');
  const previewLbl  = document.getElementById(prefix + 'PreviewLabel');
  const uploadArea  = document.getElementById(prefix + 'UploadArea');
  const uploadWrap  = document.getElementById(prefix + 'UploadAreaWrap');

  if (!fileInput) return;

  fileInput.addEventListener('change', function() {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
      previewImg.src = e.target.result;
      previewLbl.textContent = file.name;
      previewWrap.classList.add('visible');
      if (uploadWrap) uploadWrap.style.display = 'none';
      else if (uploadArea) uploadArea.style.display = 'none';
    };
    reader.readAsDataURL(file);
  });

  if (uploadArea) {
    uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
    uploadArea.addEventListener('drop', e => {
      e.preventDefault(); uploadArea.classList.remove('dragover');
      const file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
      }
    });
  }
}

function clearImg(prefix) {
  const fileInput   = document.getElementById(prefix + 'ImageFile');
  const previewWrap = document.getElementById(prefix + 'PreviewWrap');
  const uploadWrap  = document.getElementById(prefix + 'UploadAreaWrap');
  const uploadArea  = document.getElementById(prefix + 'UploadArea');
  if (fileInput)   fileInput.value = '';
  if (previewWrap) previewWrap.classList.remove('visible');
  if (uploadWrap)  uploadWrap.style.display = '';
  else if (uploadArea) uploadArea.style.display = '';
}

function toggleUrl(prefix) {
  const urlInput   = document.getElementById(prefix + 'ImageUrl');
  const uploadWrap = document.getElementById(prefix + 'UploadAreaWrap');
  const uploadArea = document.getElementById(prefix + 'UploadArea');
  const show = urlInput.style.display === 'none';
  urlInput.style.display = show ? 'block' : 'none';
  if (uploadWrap)       uploadWrap.style.display  = show ? 'none' : '';
  else if (uploadArea)  uploadArea.style.display  = show ? 'none' : '';
}

setupPreview('add');
setupPreview('edit');
</script>

</body>
</html>
