<?php

//  admin/settings

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
requireLogin();

$db = getDB();

// Ensure settings table exists 
$db->query("CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(100) PRIMARY KEY,
    `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure hours table exists 
$db->query("CREATE TABLE IF NOT EXISTS business_hours (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `day`        VARCHAR(20) NOT NULL,
    `open_time`  TIME DEFAULT '05:00:00',
    `close_time` TIME DEFAULT '18:00:00',
    `is_closed`  TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default hours if empty
$hoursCount = (int)$db->query('SELECT COUNT(*) FROM business_hours')->fetch_row()[0];
if ($hoursCount === 0) {
    $days  = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','Public Holidays'];
    $open  = '05:00';
    $close = '18:00';
    $stmt  = $db->prepare('INSERT INTO business_hours (day,open_time,close_time,is_closed) VALUES (?,?,?,0)');
    foreach ($days as $day) {
        $stmt->bind_param('sss', $day, $open, $close);
        $stmt->execute();
    }
    $stmt->close();
}

// Helpers 
function getSetting($db, $key, $default = '') {
    $stmt = $db->prepare('SELECT value FROM settings WHERE `key`=?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $row ? $row[0] : $default;
}
function setSetting($db, $key, $value) {
    $stmt = $db->prepare('INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)');
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
    $stmt->close();
}
function val($s, $key) { return htmlspecialchars($s[$key] ?? ''); }

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {

    $action = $_POST['action'] ?? '';

    if ($action === 'save_about') {
        foreach (['about_title','about_lead','about_text',
                  'years_experience','happy_guests','species_spotted','five_star_reviews'] as $f)
            setSetting($db, $f, trim($_POST[$f] ?? ''));
        // Handle about image upload
        if (!empty($_FILES['about_image_file']['name'])) {
            $file    = $_FILES['about_image_file'];
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            if (in_array($file['type'], $allowed) && $file['size'] <= 5*1024*1024) {
                $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
                $dest = __DIR__ . '/uploads/about/';
                if (!is_dir($dest)) mkdir($dest, 0755, true);
                $filename = 'about_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dest . $filename)) {
                    setSetting($db, 'about_image', 'admin/uploads/about/' . $filename);
                }
            }
        }
        $_SESSION['flash_msg']  = '✅ About & Stats settings saved.';
        $_SESSION['flash_type'] = 'success';
        header('Location: settings.php'); exit;
    }

    if ($action === 'save_contact') {
        foreach (['business_name','address','phone1','phone2','phone3'] as $f)
            setSetting($db, $f, trim($_POST[$f] ?? ''));
        $_SESSION['flash_msg']  = '✅ Contact details saved!';
        $_SESSION['flash_type'] = 'success';
        header('Location: settings.php'); exit;
    }

    if ($action === 'save_email') {
        foreach (['info_email','bookings_email','support_email','website_url','whatsapp','whatsapp_message'] as $f)
            setSetting($db, $f, trim($_POST[$f] ?? ''));
        $_SESSION['flash_msg']  = '✅ Email & website details saved!';
        $_SESSION['flash_type'] = 'success';
        header('Location: settings.php'); exit;
    }

    if ($action === 'save_social') {
        foreach (['facebook','instagram','youtube','tripadvisor'] as $f)
            setSetting($db, $f, trim($_POST[$f] ?? ''));
        $_SESSION['flash_msg']  = '✅ Social media links saved!';
        $_SESSION['flash_type'] = 'success';
        header('Location: settings.php'); exit;
    }

    if ($action === 'save_smtp') {
        foreach (['smtp_host','smtp_username','smtp_password','smtp_port','smtp_from_name','smtp_recipient'] as $f)
            setSetting($db, $f, trim($_POST[$f] ?? ''));
        $_SESSION['flash_msg']  = '✅ SMTP settings saved!';
        $_SESSION['flash_type'] = 'success';
        header('Location: settings.php'); exit;
    }

    if ($action === 'save_seo') {
        foreach (['seo_title','seo_description','seo_keywords'] as $f)
            setSetting($db, $f, trim($_POST[$f] ?? ''));
        $_SESSION['flash_msg']  = '✅ SEO settings saved!';
        $_SESSION['flash_type'] = 'success';
        header('Location: settings.php'); exit;
    }

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $admin = currentAdmin();
        $stmt  = $db->prepare('SELECT password FROM admins WHERE id=?');
        $stmt->bind_param('i', $admin['id']);
        $stmt->execute();
        $hash = $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        if (!password_verify($current, $hash)) {
            $_SESSION['flash_msg']  = '❌ Current password is incorrect.';
            $_SESSION['flash_type'] = 'error';
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_msg']  = '❌ New password must be at least 8 characters.';
            $_SESSION['flash_type'] = 'error';
        } elseif ($new !== $confirm) {
            $_SESSION['flash_msg']  = '❌ New passwords do not match.';
            $_SESSION['flash_type'] = 'error';
        } else {
            $newHash = password_hash($new, PASSWORD_BCRYPT);
            $stmt    = $db->prepare('UPDATE admins SET password=? WHERE id=?');
            $stmt->bind_param('si', $newHash, $admin['id']);
            $stmt->execute(); $stmt->close();
            $_SESSION['flash_msg']  = '✅ Password changed successfully!';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: settings.php'); exit;
    }

    if ($action === 'save_hours') {
        $days = $_POST['days'] ?? [];
        foreach ($days as $id => $data) {
            $id       = intval($id);
            $open     = $data['open']  ?? '05:00';
            $close    = $data['close'] ?? '18:00';
            $isClosed = isset($data['closed']) ? 1 : 0;
            $stmt = $db->prepare('UPDATE business_hours SET open_time=?,close_time=?,is_closed=? WHERE id=?');
            $stmt->bind_param('ssii', $open, $close, $isClosed, $id);
            $stmt->execute(); $stmt->close();
        }
        $_SESSION['flash_msg']  = '✅ Business hours saved!';
        $_SESSION['flash_type'] = 'success';
        header('Location: settings.php'); exit;
    }
}

// Flash 
$flash = $flashType = '';
if (!empty($_SESSION['flash_msg'])) {
    $flash     = $_SESSION['flash_msg'];
    $flashType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Load settings 
$s    = [];
$keys = ['business_name','address','phone1','phone2','phone3',
         'info_email','bookings_email','support_email','website_url','whatsapp','whatsapp_message',
         'facebook','instagram','youtube','tripadvisor',
         'smtp_host','smtp_username','smtp_password','smtp_port','smtp_from_name','smtp_recipient',
         'seo_title','seo_description','seo_keywords',
         'about_title','about_lead','about_text','about_image',
         'years_experience','happy_guests','species_spotted','five_star_reviews'];
foreach ($keys as $k) $s[$k] = getSetting($db, $k);

// Load hours 
$hoursRes = $db->query('SELECT * FROM business_hours ORDER BY id ASC');
$hours    = $hoursRes ? $hoursRes->fetch_all(MYSQLI_ASSOC) : [];

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Site Settings – YalaSafari Admin</title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="icon" href="../images/icons/favicon.ico"/>
  <link rel="stylesheet" href="css/admin.css"/>
  <link rel="stylesheet" href="css/settings.css"/>
</head>
<body>
<div class="admin-layout">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main class="admin-main">
    <div class="admin-topbar">
      <div class="topbar-title">
        <h1>⚙️ Site Settings</h1>
        <p>Manage contact details, hours, social media and admin credentials</p>
      </div>
    </div>

    <div class="admin-content">

      <?php if ($flash): ?>
        <div class="alert alert-<?= $flashType ?>" id="flashAlert"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <!-- ROW 1 — Contact Details | Email & Website -->
      <div class="settings-grid">

        <!-- Contact Details -->
        <div class="settings-panel">
          <div class="settings-panel-header"><h3>☎️ Contact Details</h3></div>
          <form method="POST" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="save_contact"/>
            <div class="settings-panel-body">
              <div class="form-group">
                <label>Business Name</label>
                <input type="text" name="business_name" value="<?= val($s,'business_name') ?>" placeholder="e.g. YalaSafari"/>
              </div>
              <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" value="<?= val($s,'address') ?>" placeholder="e.g. Tissamaharama, Sri Lanka"/>
              </div>
              <div class="form-group">
                <label>Phone 01</label>
                <input type="text" name="phone1" value="<?= val($s,'phone1') ?>" placeholder="+94 77 123 4567"/>
              </div>
              <div class="form-group">
                <label>Phone 02</label>
                <input type="text" name="phone2" value="<?= val($s,'phone2') ?>" placeholder="+94 77 123 4567"/>
              </div>
              <div class="form-group">
                <label>Phone 03</label>
                <input type="text" name="phone3" value="<?= val($s,'phone3') ?>" placeholder="+94 77 123 4567"/>
              </div>
            </div>
            <div class="settings-panel-footer">
              <button type="submit" class="btn-save">
                <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save
              </button>
            </div>
          </form>
        </div>

        <!-- Email & Website -->
        <div class="settings-panel">
          <div class="settings-panel-header"><h3>🌐 Email & Website</h3></div>
          <form method="POST" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="save_email"/>
            <div class="settings-panel-body">
              <div class="form-group">
                <label>Info Email</label>
                <input type="email" name="info_email" value="<?= val($s,'info_email') ?>" placeholder="info@yalasafari.com"/>
              </div>
              <div class="form-group">
                <label>Bookings Email</label>
                <input type="email" name="bookings_email" value="<?= val($s,'bookings_email') ?>" placeholder="bookings@yalasafari.com"/>
              </div>
              <div class="form-group">
                <label>Support Email</label>
                <input type="email" name="support_email" value="<?= val($s,'support_email') ?>" placeholder="support@yalasafari.com"/>
              </div>
              <div class="form-group">
                <label>Website URL</label>
                <input type="text" name="website_url" value="<?= val($s,'website_url') ?>" placeholder="https://yalasafari.com"/>
              </div>
              <div class="form-group">
                <label>WhatsApp Number</label>
                <input type="text" name="whatsapp" value="<?= val($s,'whatsapp') ?>" placeholder="+94771234567"/>
              </div>
              <div class="form-group">
                <label>WhatsApp Welcome Message</label>
                <input type="text" name="whatsapp_message" value="<?= val($s,'whatsapp_message') ?>" placeholder="Hello! I'm interested in a safari tour..."/>
              </div>
            </div>
            <div class="settings-panel-footer">
              <button type="submit" class="btn-save">
                <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- ROW 2 — Social Media | SMTP Settings -->
      <div class="settings-grid">

        <!-- Social Media -->
        <div class="settings-panel">
          <div class="settings-panel-header"><h3>📱 Social Media Links</h3></div>
          <form method="POST" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="save_social"/>
            <div class="settings-panel-body">
              <div class="form-group">
                <label>Facebook URL</label>
                <input type="url" name="facebook" value="<?= val($s,'facebook') ?>" placeholder="https://facebook.com/yalasafari"/>
              </div>
              <div class="form-group">
                <label>Instagram URL</label>
                <input type="url" name="instagram" value="<?= val($s,'instagram') ?>" placeholder="https://instagram.com/yalasafari"/>
              </div>
              <div class="form-group">
                <label>YouTube URL</label>
                <input type="url" name="youtube" value="<?= val($s,'youtube') ?>" placeholder="https://youtube.com/@yalasafari"/>
              </div>
              <div class="form-group">
                <label>TripAdvisor URL</label>
                <input type="url" name="tripadvisor" value="<?= val($s,'tripadvisor') ?>" placeholder="https://tripadvisor.com/..."/>
              </div>
            </div>
            <div class="settings-panel-footer">
              <button type="submit" class="btn-save">
                <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save
              </button>
            </div>
          </form>
        </div>

        <!-- SMTP Settings -->
        <div class="settings-panel">
          <div class="settings-panel-header"><h3>📧 SMTP Email Settings</h3></div>
          <form method="POST" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="save_smtp"/>
            <div class="settings-panel-body">
              <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:8px;padding:11px 14px;margin-bottom:16px;font-size:0.79rem;color:#0369a1;line-height:1.6;">
                💡 Find these in <strong>cPanel → Email Accounts</strong>. Use the email you created for bookings.
              </div>
              <div class="form-group">
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" value="<?= val($s,'smtp_host') ?>" placeholder="mail.yalasafari.lk"/>
              </div>
              <div class="form-group">
                <label>SMTP Username</label>
                <input type="text" name="smtp_username" value="<?= val($s,'smtp_username') ?>" placeholder="bookings@yalasafari.lk"/>
              </div>
              <div class="form-group">
                <label>SMTP Password</label>
                <div class="input-pw-wrap">
                  <input type="password" name="smtp_password" id="smtp_pw" value="<?= val($s,'smtp_password') ?>" placeholder="Your email password"/>
                  <button type="button" class="pw-toggle" onclick="togglePw('smtp_pw',this)">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label>SMTP Port</label>
                <input type="number" name="smtp_port" value="<?= val($s,'smtp_port') ?: '465' ?>" placeholder="465"/>
              </div>
              <div class="form-group">
                <label>From Name</label>
                <input type="text" name="smtp_from_name" value="<?= val($s,'smtp_from_name') ?>" placeholder="YalaSafari"/>
              </div>
              <div class="form-group">
                <label>Recipient Email <span style="font-size:0.75rem;color:var(--text-muted);font-weight:400">(receives all inquiries)</span></label>
                <input type="email" name="smtp_recipient" value="<?= val($s,'smtp_recipient') ?>" placeholder="bookings@yalasafari.lk"/>
              </div>
            </div>
            <div class="settings-panel-footer">
              <button type="submit" class="btn-save">
                <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- ROW 3 — SEO Settings | Change Password -->
      <div class="settings-grid">

        <!-- SEO Settings -->
        <div class="settings-panel">
          <div class="settings-panel-header"><h3>🔍 SEO Settings</h3></div>
          <form method="POST" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="save_seo"/>
            <div class="settings-panel-body">
              <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:8px;padding:11px 14px;margin-bottom:16px;font-size:0.79rem;color:#15803d;line-height:1.6;">
                💡 These appear in <strong>Google search results</strong> and help customers find your website.
              </div>
              <div class="form-group">
                <label>Site Title</label>
                <input type="text" name="seo_title" value="<?= val($s,'seo_title') ?>"
                  placeholder="YalaSafari | Yala National Park Safari Tours"/>
              </div>
              <div class="form-group">
                <label>
                  Meta Description
                  <span id="descCount" style="float:right;font-size:0.73rem;color:var(--text-muted);font-weight:400">0 / 160</span>
                </label>
                <textarea
                  name="seo_description"
                  id="seoDesc"
                  maxlength="160"
                  rows="3"
                  style="width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:var(--radius-sm);font-family:'Poppins',sans-serif;font-size:0.85rem;resize:none;outline:none;transition:var(--transition);"
                  placeholder="Experience the wildlife of Yala National Park with our expert safari guides. Book morning, full day and multi-day safari packages."><?= val($s,'seo_description') ?></textarea>
              </div>
              <div class="form-group">
                <label>
                  Meta Keywords
                  <span style="font-size:0.73rem;color:var(--text-muted);font-weight:400">(comma separated)</span>
                </label>
                <input type="text" name="seo_keywords" value="<?= val($s,'seo_keywords') ?>"
                  placeholder="yala safari, sri lanka safari, yala national park, wildlife tours"/>
              </div>

              <!-- Google Preview -->
              <div style="margin-top:18px;">
                <div style="font-size:0.78rem;font-weight:600;color:var(--text-muted);margin-bottom:8px;">Google Preview</div>
                <div style="border:1.5px solid var(--border);border-radius:8px;padding:14px 16px;background:var(--white);">
                  <div id="previewTitle" style="font-size:0.95rem;color:#1a0dab;font-weight:500;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= $s['seo_title'] ?: 'YalaSafari | Yala National Park Safari Tours' ?>
                  </div>
                  <div style="font-size:0.78rem;color:#006621;margin-bottom:4px;">
                    <?= htmlspecialchars($s['website_url'] ?: 'https://yalasafari.com') ?>
                  </div>
                  <div id="previewDesc" style="font-size:0.82rem;color:#545454;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                    <?= $s['seo_description'] ?: 'Experience the wildlife of Yala National Park with our expert safari guides.' ?>
                  </div>
                </div>
              </div>

            </div>
            <div class="settings-panel-footer">
              <button type="submit" class="btn-save">
                <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save
              </button>
            </div>
          </form>
        </div>

        <!-- Change Admin Password -->
        <div class="settings-panel">
          <div class="settings-panel-header"><h3>🔒 Change Admin Password</h3></div>
          <form method="POST" action="settings.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="change_password"/>
            <div class="settings-panel-body">
              <div class="form-group">
                <label>Current Password</label>
                <div class="input-pw-wrap">
                  <input type="password" name="current_password" id="pw_current" placeholder="Enter current password"/>
                  <button type="button" class="pw-toggle" onclick="togglePw('pw_current',this)">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label>New Password</label>
                <div class="input-pw-wrap">
                  <input type="password" name="new_password" id="pw_new" placeholder="Min. 8 characters"/>
                  <button type="button" class="pw-toggle" onclick="togglePw('pw_new',this)">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <div class="input-pw-wrap">
                  <input type="password" name="confirm_password" id="pw_confirm" placeholder="Repeat new password"/>
                  <button type="button" class="pw-toggle" onclick="togglePw('pw_confirm',this)">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </button>
                </div>
              </div>

              <!-- Password strength hint -->
              <div style="background:var(--green-pale);border-radius:8px;padding:11px 14px;margin-top:4px;font-size:0.78rem;color:var(--text-muted);line-height:1.7;">
                Password must be at least <strong>8 characters</strong> and should include uppercase, lowercase, numbers and symbols for best security.
              </div>
            </div>
            <div class="settings-panel-footer">
              <button type="submit" class="btn-save">
                <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                Save
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- ROW 4 — Business Hours (full width) -->
<!-- ROW: About & Stats -->
      <div style="margin-top: 32px; margin-bottom: 32px;">
        <div class="settings-panel">
          <div class="settings-panel-header"><h3>📖 Homepage — About Section &amp; Stats</h3></div>
          <form method="POST" action="settings.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="save_about"/>
            <div class="settings-panel-body">

              <div class="form-group">
                <label>About Title</label>
                <input type="text" name="about_title" value="<?= val($s,'about_title') ?>" placeholder="About Yala Safari Tours"/>
              </div>

              <div class="form-group">
                <label>About Lead (tagline under title)</label>
                <input type="text" name="about_lead" value="<?= val($s,'about_lead') ?>" placeholder="Sri Lanka's Most Trusted Safari Experience"/>
              </div>

              <div class="form-group">
                <label>About Description</label>
                <textarea name="about_text" rows="5" placeholder="Describe your business..."><?= val($s,'about_text') ?></textarea>
              </div>

              <div class="form-group">
                <label>About Image</label>
                <?php if (!empty($s['about_image'])): ?>
                  <div style="margin-bottom:10px">
                    <img src="<?= val($s,'about_image') ?>" alt="Current about image"
                         style="width:180px;height:120px;object-fit:cover;border-radius:8px;border:2px solid var(--border)"/>
                    <p style="font-size:0.8rem;color:var(--text-muted);margin-top:6px">Current image — upload a new one to replace</p>
                  </div>
                <?php endif; ?>
                <input type="file" name="about_image_file" accept="image/*" style="padding:6px"/>
                <p style="font-size:0.78rem;color:var(--text-muted);margin-top:4px">Recommended: landscape photo, min 600×400px, max 5MB</p>
              </div>

              <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border)"/>
              <p style="font-weight:600;margin-bottom:16px;color:var(--text-primary)">📊 Stats Numbers (shown on homepage &amp; gallery)</p>

              <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
                <div class="form-group">
                  <label>Years of Experience</label>
                  <input type="number" name="years_experience" value="<?= val($s,'years_experience') ?>" placeholder="15"/>
                </div>
                <div class="form-group">
                  <label>Happy Guests</label>
                  <input type="number" name="happy_guests" value="<?= val($s,'happy_guests') ?>" placeholder="1200"/>
                </div>
                <div class="form-group">
                  <label>Species Spotted</label>
                  <input type="number" name="species_spotted" value="<?= val($s,'species_spotted') ?>" placeholder="50"/>
                </div>
                <div class="form-group">
                  <label>5-Star Reviews</label>
                  <input type="number" name="five_star_reviews" value="<?= val($s,'five_star_reviews') ?>" placeholder="500"/>
                </div>
              </div>

            </div>
            <div class="settings-panel-footer">
              <button type="submit" class="btn-save">Save About &amp; Stats</button>
            </div>
          </form>
        </div>
      </div>

      <div class="hours-panel">
        <div class="hours-panel-header">
          <h3>🕐 Business Hours</h3>
        </div>
        <form method="POST" action="settings.php">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
          <input type="hidden" name="action" value="save_hours"/>
          <table class="hours-table">
            <thead>
              <tr>
                <th>Day</th>
                <th>Open Time</th>
                <th>Close Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($hours as $h): ?>
                <tr class="<?= $h['is_closed'] ? 'is-closed' : '' ?>" id="row_<?= $h['id'] ?>">
                  <td class="day-name"><?= htmlspecialchars($h['day']) ?></td>
                  <td>
                    <input type="time" name="days[<?= $h['id'] ?>][open]"
                      value="<?= htmlspecialchars(substr($h['open_time'],0,5)) ?>"
                      class="time-input"/>
                  </td>
                  <td>
                    <input type="time" name="days[<?= $h['id'] ?>][close]"
                      value="<?= htmlspecialchars(substr($h['close_time'],0,5)) ?>"
                      class="time-input"/>
                  </td>
                  <td>
                    <div class="closed-wrap">
                      <input type="checkbox"
                        name="days[<?= $h['id'] ?>][closed]"
                        value="1"
                        id="closed_<?= $h['id'] ?>"
                        <?= $h['is_closed'] ? 'checked' : '' ?>
                        onchange="toggleRow(<?= $h['id'] ?>,this.checked)"/>
                      <label class="closed-label" for="closed_<?= $h['id'] ?>">Closed</label>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div class="hours-panel-footer">
            <button type="submit" class="btn-save">
              <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              Save Business Hours
            </button>
          </div>
        </form>
      </div>

    </div>
  </main>
</div>

<script>
// Flash dismiss
const flash = document.getElementById('flashAlert');
if (flash) {
  setTimeout(() => {
    flash.style.transition = 'opacity 0.5s';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
  }, 4000);
}

// Password toggle
function togglePw(id, btn) {
  const input = document.getElementById(id);
  const show  = input.type === 'password';
  input.type  = show ? 'text' : 'password';
  btn.style.color = show ? 'var(--green-accent)' : '';
}

// Dim closed rows
function toggleRow(id, closed) {
  document.getElementById('row_' + id).classList.toggle('is-closed', closed);
}

// SEO live preview + char counter
const seoDesc     = document.getElementById('seoDesc');
const descCount   = document.getElementById('descCount');
const previewDesc = document.getElementById('previewDesc');
const seoTitle    = document.querySelector('[name="seo_title"]');
const previewTitle= document.getElementById('previewTitle');

function updatePreview() {
  const len = seoDesc.value.length;
  descCount.textContent = len + ' / 160';
  descCount.style.color = len > 140 ? '#dc2626' : len > 120 ? '#f59e0b' : 'var(--text-muted)';
  previewDesc.textContent = seoDesc.value || 'Experience the wildlife of Yala National Park with our expert safari guides.';
}
function updateTitle() {
  previewTitle.textContent = seoTitle.value || 'YalaSafari | Yala National Park Safari Tours';
}

if (seoDesc)  { seoDesc.addEventListener('input', updatePreview); updatePreview(); }
if (seoTitle) { seoTitle.addEventListener('input', updateTitle); }
</script>

</body>
</html>
