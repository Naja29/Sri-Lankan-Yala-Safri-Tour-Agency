<?php

//  admin/messages

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';
requireLogin();

$db = getDB();

// Load settings using correct column names (key, value)
function getAdminSettings(array $keys): array {
    $db  = getDB();
    $out = array_fill_keys($keys, '');
    foreach ($keys as $key) {
        $stmt = $db->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_row();
            $stmt->close();
            $out[$key] = $row ? (string)$row[0] : '';
        }
    }
    return $out;
}

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {

    $action = $_POST['action'] ?? '';

    // Mark single as read
    if ($action === 'mark_read') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare('UPDATE messages SET is_read=1 WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute(); $stmt->close();
        }
        header('Location: messages.php' . ($_GET['filter'] ? '?filter=' . urlencode($_GET['filter']) : ''));
        exit;
    }

    // Mark all as read
    if ($action === 'mark_all_read') {
        $db->query('UPDATE messages SET is_read=1');
        $_SESSION['flash_msg']  = '✅ All messages marked as read.';
        $_SESSION['flash_type'] = 'success';
        header('Location: messages.php'); exit;
    }

    // Delete message
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare('DELETE FROM messages WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute(); $stmt->close();
            $_SESSION['flash_msg']  = '🗑️ Message deleted.';
            $_SESSION['flash_type'] = 'success';
        }
        header('Location: messages.php'); exit;
    }

    // Send reply email
    if ($action === 'send_reply') {
        $id       = intval($_POST['id'] ?? 0);
        $toEmail  = trim($_POST['to_email']   ?? '');
        $toName   = trim($_POST['to_name']    ?? '');
        $subject  = trim($_POST['subject']    ?? '');
        $body     = trim($_POST['reply_body'] ?? '');

        if ($id && $toEmail && $body) {
            // Load SMTP settings
            $s = getAdminSettings(['smtp_host','smtp_username','smtp_password','smtp_port','smtp_from_name','smtp_recipient','whatsapp']);
            $phpmailerPath = __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';

            $sent = false;
            if (file_exists($phpmailerPath)) {
                require_once $phpmailerPath;
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
                require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $s['smtp_host']     ?: 'localhost';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $s['smtp_username'] ?: '';
                    $mail->Password   = $s['smtp_password'] ?: '';
                    $mail->SMTPSecure = ((int)($s['smtp_port'] ?: 465)) === 587
                        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = (int)($s['smtp_port'] ?: 465);
                    $mail->CharSet    = 'UTF-8';
                    $mail->setFrom($s['smtp_username'] ?: $s['smtp_recipient'], $s['smtp_from_name'] ?: 'YalaSafari');
                    $mail->addAddress($toEmail, $toName);
                    $mail->Subject = $subject ?: 'Re: Your YalaSafari Inquiry';
                    $mail->isHTML(true);
                    $mail->Body = "
                    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden'>
                      <div style='background:#1a3a1a;padding:24px 30px'>
                        <h2 style='color:#5eb832;margin:0'>🦁 YalaSafari</h2>
                        <p style='color:#a0c080;margin:4px 0 0;font-size:13px'>Response to your inquiry</p>
                      </div>
                      <div style='padding:28px 30px;background:#fff'>
                        <p style='margin:0 0 6px;color:#444'>Dear <strong>" . htmlspecialchars($toName) . "</strong>,</p>
                        <div style='margin:20px 0;padding:16px;background:#f8faf8;border-left:4px solid #5eb832;border-radius:4px;line-height:1.8;color:#333'>" . nl2br(htmlspecialchars($body)) . "</div>
                        <p style='color:#1a3a1a;font-weight:600;margin-top:24px'>Warm regards,<br/><strong>The YalaSafari Team</strong></p>
                      </div>
                      <div style='padding:14px 30px;background:#f5f5f5;text-align:center;font-size:12px;color:#999'>
                        &copy; " . date('Y') . " YalaSafari. All rights reserved.
                      </div>
                    </div>";
                    $mail->AltBody = $body;
                    $mail->send();
                    $sent = true;
                } catch (Exception $e) {
                    error_log('Reply mail error: ' . $e->getMessage());
                }
            }

            // Mark as read + store reply sent flag
            $stmt = $db->prepare('UPDATE messages SET is_read=1 WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute(); $stmt->close();

            $_SESSION['flash_msg']  = $sent ? '✅ Reply sent to ' . htmlspecialchars($toName) . '!' : '⚠️ Message marked read but email could not be sent. Check SMTP settings.';
            $_SESSION['flash_type'] = $sent ? 'success' : 'warning';
        }
        header('Location: messages.php'); exit;
    }
}

// Flash 
$message = $msgType = '';
if (!empty($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $msgType = $_SESSION['flash_type'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

// Fetch messages 
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'all'); // all | unread | read

// Counts for tabs
$totalCount  = (int)($db->query('SELECT COUNT(*) FROM messages')->fetch_row()[0] ?? 0);
$unreadCount = (int)($db->query('SELECT COUNT(*) FROM messages WHERE is_read=0')->fetch_row()[0] ?? 0);
$readCount   = $totalCount - $unreadCount;

// Build query
$where  = [];
$params = [];
$types  = '';

if ($search) {
    $like     = "%{$search}%";
    $where[]  = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR message LIKE ?)';
    $params   = array_merge($params, [$like,$like,$like,$like]);
    $types   .= 'ssss';
}
if ($filter === 'unread') { $where[] = 'is_read=0'; }
if ($filter === 'read')   { $where[] = 'is_read=1'; }

// Pagination 
$perPage     = 10;
$currentPage = max(1, intval($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

// Count total matching rows first
$countSql = 'SELECT COUNT(*) FROM messages' . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
if ($params) {
    $stmt = $db->prepare($countSql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $totalRows = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
} else {
    $totalRows = (int)$db->query($countSql)->fetch_row()[0];
}
$totalPages = max(1, ceil($totalRows / $perPage));

// Fetch paginated messages
$sql = 'SELECT * FROM messages' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
$paginatedParams = array_merge($params, [$perPage, $offset]);
$paginatedTypes  = $types . 'ii';
$stmt = $db->prepare($sql);
$stmt->bind_param($paginatedTypes, ...$paginatedParams);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Auto-mark as read when opened
if (isset($_GET['open'])) {
    $openId = intval($_GET['open']);
    $stmt   = $db->prepare('UPDATE messages SET is_read=1 WHERE id=?');
    $stmt->bind_param('i', $openId);
    $stmt->execute(); $stmt->close();
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $unreadCount > 0 ? "($unreadCount) " : '' ?>Messages – YalaSafari Admin</title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="icon" href="../images/icons/favicon.ico"/>
  <link rel="stylesheet" href="css/admin.css"/>
  <link rel="stylesheet" href="css/messages.css"/>
</head>
<body>
<div class="admin-layout">

  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

  <main class="admin-main">

    <div class="admin-topbar">
      <div class="topbar-title">
        <h1>💬 Messages & Inquiries</h1>
        <p>Manage contact form submissions and customer inquiries</p>
      </div>
    </div>

    <div class="admin-content">

      <!-- Flash -->
      <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>" id="flashAlert"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <!-- Toolbar -->
      <div class="msg-toolbar">
        <form method="GET" action="messages.php" class="search-form">
          <?php if ($filter !== 'all'): ?>
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"/>
          <?php endif; ?>
          <div class="search-wrap">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="search" placeholder="Search Messages..."
              value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
          </div>
        </form>

        <?php if ($unreadCount > 0): ?>
          <form method="POST" action="messages.php">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
            <input type="hidden" name="action" value="mark_all_read"/>
            <button type="submit" class="btn-mark-all">
              <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Mark All Read
            </button>
          </form>
        <?php endif; ?>
      </div>

      <!-- Filter Tabs -->
      <div class="filter-tabs">
        <a href="messages.php<?= $search ? '?search='.urlencode($search) : '' ?>"
           class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">
          All <span class="tab-count"><?= $totalCount ?></span>
        </a>
        <a href="messages.php?filter=unread<?= $search ? '&search='.urlencode($search) : '' ?>"
           class="filter-tab <?= $filter === 'unread' ? 'active' : '' ?>">
          Unread <span class="tab-count"><?= $unreadCount ?></span>
        </a>
        <a href="messages.php?filter=read<?= $search ? '&search='.urlencode($search) : '' ?>"
           class="filter-tab <?= $filter === 'read' ? 'active' : '' ?>">
          Read <span class="tab-count"><?= $readCount ?></span>
        </a>
      </div>

      <!-- Messages List -->
      <?php if (empty($messages)): ?>
        <div class="msg-empty">
          <div class="msg-empty-icon">📭</div>
          <p>
            <?php if ($search): ?>
              No messages found for &ldquo;<strong><?= htmlspecialchars($search) ?></strong>&rdquo;
            <?php elseif ($filter === 'unread'): ?>
              No unread messages — you're all caught up! 🎉
            <?php else: ?>
              No messages yet — they will appear here when customers contact you.
            <?php endif; ?>
          </p>
        </div>
      <?php else: ?>
        <div class="messages-list">
          <?php foreach ($messages as $msg): ?>
            <?php
              $isUnread = !$msg['is_read'];
              $initial  = strtoupper(substr($msg['first_name'], 0, 1));
              $fullName = htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']);
              $msgId    = 'msg_' . $msg['id'];
            ?>
            <div class="msg-card <?= $isUnread ? 'unread' : 'read' ?>" id="<?= $msgId ?>">

              <!-- Avatar -->
              <div class="msg-avatar"><?= $initial ?></div>

              <!-- Content -->
              <div class="msg-content">

                <!-- Top row: name + badge + date -->
                <div class="msg-top">
                  <div class="msg-name"><?= $fullName ?></div>
                  <div class="msg-right">
                    <?php if ($isUnread): ?>
                      <span class="msg-new-badge">NEW</span>
                    <?php endif; ?>
                    <span class="msg-date">
                      <?= date('M j, Y · g:i A', strtotime($msg['created_at'])) ?>
                    </span>
                  </div>
                </div>

                <!-- Contact info -->
                <div class="msg-contact">
                  <div class="msg-contact-item">
                    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                      <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color:inherit">
                      <?= htmlspecialchars($msg['email']) ?>
                    </a>
                  </div>
                  <?php if (!empty($msg['phone'])): ?>
                    <div class="msg-contact-item">
                      <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 10.8a19.79 19.79 0 01-3.07-8.67A2 2 0 012 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
                      </svg>
                      <a href="tel:<?= htmlspecialchars($msg['phone']) ?>" style="color:inherit">
                        <?= htmlspecialchars($msg['phone']) ?>
                      </a>
                    </div>
                  <?php endif; ?>
                </div>

                <!-- Reference number -->
                <?php if (!empty($msg['booking_ref'])): ?>
                  <div class="msg-ref-badge">
                    🎫 <?= htmlspecialchars($msg['booking_ref']) ?>
                  </div>
                <?php endif; ?>

                <!-- Package + Safari Date meta -->
                <?php if (!empty($msg['package']) || !empty($msg['safari_date'])): ?>
                  <div class="msg-meta-row">
                    <?php if (!empty($msg['package'])): ?>
                      <span class="msg-meta-item">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                        </svg>
                        <?= htmlspecialchars($msg['package']) ?>
                      </span>
                    <?php endif; ?>
                    <?php if (!empty($msg['safari_date'])): ?>
                      <span class="msg-meta-item">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                          <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        Safari Date: <?= date('M j, Y', strtotime($msg['safari_date'])) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <!-- Message body -->
                <?php $msgText = htmlspecialchars($msg['message']); ?>
                <?php $isLong  = strlen($msg['message']) > 180; ?>
                <div class="msg-body <?= $isLong ? 'collapsed' : '' ?>" id="body_<?= $msg['id'] ?>">
                  <?= nl2br($msgText) ?>
                </div>
                <?php if ($isLong): ?>
                  <button class="msg-read-more" onclick="toggleMsg(<?= $msg['id'] ?>)" id="btn_<?= $msg['id'] ?>">
                    Read more ↓
                  </button>
                <?php endif; ?>

                <!-- Action buttons -->
                <div class="msg-actions">
                  <!-- Reply toggle -->
                  <button type="button" class="btn-action btn-reply"
                    onclick="toggleReply(<?= $msg['id'] ?>)">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="9 17 4 12 9 7"/>
                      <path d="M20 18v-2a4 4 0 00-4-4H4"/>
                    </svg>
                    Reply
                  </button>

                  <!-- Mark as read (only if unread) -->
                  <?php if ($isUnread): ?>
                    <form method="POST" action="messages.php">
                      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                      <input type="hidden" name="action" value="mark_read"/>
                      <input type="hidden" name="id" value="<?= $msg['id'] ?>"/>
                      <button type="submit" class="btn-action btn-mark-read">
                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Mark as Read
                      </button>
                    </form>
                  <?php endif; ?>

                  <!-- Delete -->
                  <form method="POST" action="messages.php"
                    onsubmit="return confirm('Delete this message from <?= htmlspecialchars(addslashes($msg['first_name'].' '.$msg['last_name'])) ?>?')">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                    <input type="hidden" name="action" value="delete"/>
                    <input type="hidden" name="id" value="<?= $msg['id'] ?>"/>
                    <button type="submit" class="btn-action btn-del">
                      <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                      </svg>
                      Delete
                    </button>
                  </form>

                </div>

                <!-- Reply Panel -->
                <div class="reply-panel" id="reply_<?= $msg['id'] ?>">
                  <form method="POST" action="messages.php">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
                    <input type="hidden" name="action"   value="send_reply"/>
                    <input type="hidden" name="id"       value="<?= $msg['id'] ?>"/>
                    <input type="hidden" name="to_email" value="<?= htmlspecialchars($msg['email']) ?>"/>
                    <input type="hidden" name="to_name"  value="<?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?>"/>
                    <div class="reply-to-info">
                      📧 Replying to <strong><?= htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) ?></strong>
                      &lt;<?= htmlspecialchars($msg['email']) ?>&gt;
                    </div>
                    <div class="reply-field">
                      <input type="text" name="subject"
                        value="Re: Your YalaSafari Inquiry"
                        placeholder="Subject" class="reply-subject"/>
                    </div>
                    <div class="reply-field">
                      <textarea name="reply_body" rows="5" placeholder="Type your reply here..." class="reply-textarea" required>Dear <?= htmlspecialchars($msg['first_name']) ?>,
Thank you for contacting YalaSafari. </textarea>
                    </div>
                    <div class="reply-actions">
                      <button type="submit" class="btn-send-reply">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                          <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                        </svg>
                        Send Email Reply
                      </button>
                      <?php if (!empty($msg['phone'])): ?>
                        <?php
                          $waNum = preg_replace('/[^0-9]/', '', $msg['phone']);
                          // If starts with 0, replace with 94 (Sri Lanka)
                          if (str_starts_with($waNum, '0')) $waNum = '94' . substr($waNum, 1);
                          $waRef = !empty($msg['booking_ref']) ? " (Ref: {$msg['booking_ref']})" : '';
                          $waText = urlencode("Dear {$msg['first_name']}, thank you for contacting YalaSafari{$waRef}. ");
                        ?>
                        <a href="https://wa.me/<?= $waNum ?>?text=<?= $waText ?>"
                           target="_blank" class="btn-whatsapp-reply">
                          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                          </svg>
                          WhatsApp
                        </a>
                      <?php endif; ?>
                      <button type="button" class="btn-cancel-reply" onclick="toggleReply(<?= $msg['id'] ?>)">
                        Cancel
                      </button>
                    </div>
                  </form>
                </div>

              </div><!-- /msg-content -->
            </div><!-- /msg-card -->
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <?php
          $pageParams = [];
          if ($search) $pageParams['search'] = $search;
          if ($filter !== 'all') $pageParams['filter'] = $filter;
        ?>
        <div class="pagination">
          <span class="page-info">
            Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalRows) ?> of <?= $totalRows ?> messages
          </span>
          <div class="page-btns">
            <!-- Prev -->
            <?php if ($currentPage > 1): ?>
              <a href="?<?= http_build_query(array_merge($pageParams, ['page' => $currentPage - 1])) ?>" class="page-btn">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
              </a>
            <?php else: ?>
              <span class="page-btn disabled"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></span>
            <?php endif; ?>

            <!-- Numbers -->
            <?php $start = max(1, $currentPage-2); $end = min($totalPages, $currentPage+2); ?>
            <?php if ($start > 1): ?>
              <a href="?<?= http_build_query(array_merge($pageParams, ['page'=>1])) ?>" class="page-btn">1</a>
              <?php if ($start > 2): ?><span class="page-dots">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
              <?php if ($i === $currentPage): ?>
                <span class="page-btn active"><?= $i ?></span>
              <?php else: ?>
                <a href="?<?= http_build_query(array_merge($pageParams, ['page'=>$i])) ?>" class="page-btn"><?= $i ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            <?php if ($end < $totalPages): ?>
              <?php if ($end < $totalPages-1): ?><span class="page-dots">…</span><?php endif; ?>
              <a href="?<?= http_build_query(array_merge($pageParams, ['page'=>$totalPages])) ?>" class="page-btn"><?= $totalPages ?></a>
            <?php endif; ?>

            <!-- Next -->
            <?php if ($currentPage < $totalPages): ?>
              <a href="?<?= http_build_query(array_merge($pageParams, ['page' => $currentPage + 1])) ?>" class="page-btn">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
            <?php else: ?>
              <span class="page-btn disabled"><svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<script>
// Flash dismiss 
const flash = document.getElementById('flashAlert');
if (flash) {
  setTimeout(() => {
    flash.style.transition = 'opacity 0.5s ease';
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
  }, 4000);
}

// Reply panel toggle 
function toggleReply(id) {
  const panel = document.getElementById('reply_' + id);
  const isOpen = panel.classList.contains('open');
  // Close all other open panels
  document.querySelectorAll('.reply-panel.open').forEach(p => p.classList.remove('open'));
  if (!isOpen) panel.classList.add('open');
}

// Read More / Collapse 
function toggleMsg(id) {
  const body = document.getElementById('body_' + id);
  const btn  = document.getElementById('btn_'  + id);
  if (body.classList.contains('collapsed')) {
    body.classList.remove('collapsed');
    btn.textContent = 'Show less ↑';
  } else {
    body.classList.add('collapsed');
    btn.textContent = 'Read more ↓';
  }
}
</script>

</body>
</html>
