<?php

//  contact — Contact Page with PHPMailer + DB saving

ob_start(); // Buffer output so header() redirect always works
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'includes/db.php';

// Ensure testimonials table 
$db = getDB();
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

// Handle review submission 
$reviewSuccess = false;
$reviewError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['review_submit'])) {
    // CSRF check for review form
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $reviewError = 'Security check failed. Please refresh and try again.';
    } else
    $rName    = trim($_POST['review_name']    ?? '');
    $rLoc     = trim($_POST['review_location'] ?? '');
    $rRating  = max(1, min(5, (int)($_POST['review_rating'] ?? 5)));
    $rMsg     = trim($_POST['review_message'] ?? '');
    $rPhoto   = '';

    if (!empty($_FILES['review_photo']['name'])) {
        $file    = $_FILES['review_photo'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (in_array($file['type'], $allowed) && $file['size'] <= 3*1024*1024) {
            $dir = __DIR__ . '/admin/uploads/testimonials/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'review_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                $rPhoto = 'admin/uploads/testimonials/' . $filename;
            }
        }
    }

    if ($rName && $rMsg) {
        $stmt = $db->prepare("INSERT INTO testimonials (name, location, rating, message, photo, source, status)
                              VALUES (?,?,?,?,?,'website','pending')");
        $stmt->bind_param('ssiss', $rName, $rLoc, $rRating, $rMsg, $rPhoto);
        $stmt->execute();
        $reviewSuccess = true;
    } else {
        $reviewError = 'Please fill in your name and review message.';
    }
}

// Load settings 
$settings = getSettings([
    'business_name', 'address', 'phone1', 'phone2', 'phone3',
    'info_email', 'bookings_email', 'support_email', 'website_url',
    'facebook', 'instagram', 'whatsapp', 'whatsapp_message',
    'smtp_host', 'smtp_username', 'smtp_password',
    'smtp_port', 'smtp_from_name', 'smtp_recipient'
]);

// Load packages for dropdown 
$db       = getDB();
$pkgRes   = $db->query("SELECT name, price, price_per FROM packages WHERE status='active' ORDER BY created_at ASC");
$packages = $pkgRes ? $pkgRes->fetch_all(MYSQLI_ASSOC) : [];

// Load business hours from DB — seed if empty 
$hoursRes = $db->query('SELECT * FROM business_hours ORDER BY id ASC');
$hours    = $hoursRes ? $hoursRes->fetch_all(MYSQLI_ASSOC) : [];

// Seed default hours if table is empty
if (empty($hours)) {
    $defaultDays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','Public Holidays'];
    $open = '05:00'; $close = '18:00';
    $stmt = $db->prepare('INSERT INTO business_hours (day,open_time,close_time,is_closed) VALUES (?,?,?,0)');
    if ($stmt) {
        foreach ($defaultDays as $day) {
            $stmt->bind_param('sss', $day, $open, $close);
            $stmt->execute();
        }
        $stmt->close();
        $hoursRes = $db->query('SELECT * FROM business_hours ORDER BY id ASC');
        $hours    = $hoursRes ? $hoursRes->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// CSRF 
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Handle form submission 
$formStatus  = ''; // 'success' | 'error'
$formMessage = '';
$formData    = []; // repopulate on error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['review_submit'])) {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $formStatus  = 'error';
        $formMessage = 'Security check failed. Please refresh and try again.';
    } else {

        // Sanitise inputs
        $firstName  = trim(strip_tags($_POST['first_name']   ?? ''));
        $lastName   = trim(strip_tags($_POST['last_name']    ?? ''));
        $email      = trim(strip_tags($_POST['email']        ?? ''));
        $phone      = trim(strip_tags($_POST['phone']        ?? ''));
        $package    = trim(strip_tags($_POST['package']      ?? ''));
        $safariDate = trim(strip_tags($_POST['safari_date']  ?? ''));
        $message    = trim(strip_tags($_POST['message']      ?? ''));

        // Keep form data for repopulation on error
        $formData = compact('firstName','lastName','email','phone','package','safariDate','message');

        // Basic validation
        $errors = [];
        if (!$firstName)              $errors[] = 'First name is required.';
        if (!$lastName)               $errors[] = 'Last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
        if (!$phone)                  $errors[] = 'Phone number is required.';
        if (!$message)                $errors[] = 'Message is required.';
        if ($safariDate && $safariDate < date('Y-m-d')) $errors[] = 'Safari date cannot be in the past.';

        if ($errors) {
            $formStatus  = 'error';
            $formMessage = implode(' ', $errors);
        } else {

            // 1. Save to database 
            $dbSaved  = false;
            $insertId = 0;
            try {
                $safariDateVal = $safariDate ?: null;
                $stmt = $db->prepare('INSERT INTO messages
                    (first_name, last_name, email, phone, package, safari_date, message, is_read)
                    VALUES (?,?,?,?,?,?,?,0)');
                $stmt->bind_param('sssssss',
                    $firstName, $lastName, $email, $phone,
                    $package, $safariDateVal, $message);
                $dbSaved  = $stmt->execute();
                $insertId = $stmt->insert_id; // capture BEFORE close
                $stmt->close();
            } catch (Exception $e) {
                error_log('Contact DB save error: ' . $e->getMessage());
            }

            // 2. Send emails via PHPMailer 
            $mailSent = false;
            $smtpHost = $settings['smtp_host']      ?: 'localhost';
            $smtpUser = $settings['smtp_username']  ?: '';
            $smtpPass = $settings['smtp_password']  ?: '';
            $smtpPort = (int)($settings['smtp_port'] ?: 465);
            $fromName = $settings['smtp_from_name'] ?: 'YalaSafari';
            $recipient= $settings['smtp_recipient'] ?: $settings['bookings_email'] ?: 'bookings@yalasafari.lk';

            // Check if PHPMailer is available
            $phpmailerPath = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
            if (file_exists($phpmailerPath)) {
                require_once $phpmailerPath;
                require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
                require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';

                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = $smtpHost;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $smtpUser;
                    $mail->Password   = $smtpPass;
                    $mail->SMTPSecure = $smtpPort === 587
                        ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS
                        : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = $smtpPort;
                    $mail->CharSet    = 'UTF-8';

                    // Email 1: Notify admin 
                    $mail->setFrom($smtpUser, $fromName);
                    $mail->addAddress($recipient);
                    $mail->addReplyTo($email, "$firstName $lastName");
                    $mail->Subject = "New Inquiry from $firstName $lastName – YalaSafari";
                    $mail->isHTML(true);
                    $mail->Body = "
                    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden'>
                      <div style='background:#1a3a1a;padding:24px 30px'>
                        <h2 style='color:#5eb832;margin:0'>🦁 New Safari Inquiry</h2>
                        <p style='color:#a0c080;margin:6px 0 0'>YalaSafari Contact Form</p>
                      </div>
                      <div style='padding:28px 30px;background:#fff'>
                        <table style='width:100%;border-collapse:collapse'>
                          <tr><td style='padding:8px 0;color:#666;width:140px'>Name</td><td style='padding:8px 0;font-weight:600'>" . htmlspecialchars("$firstName $lastName") . "</td></tr>
                          <tr><td style='padding:8px 0;color:#666'>Email</td><td style='padding:8px 0'><a href='mailto:" . htmlspecialchars($email) . "' style='color:#2563eb'>" . htmlspecialchars($email) . "</a></td></tr>
                          <tr><td style='padding:8px 0;color:#666'>Phone</td><td style='padding:8px 0'>" . htmlspecialchars($phone) . "</td></tr>
                          " . ($package    ? "<tr><td style='padding:8px 0;color:#666'>Package</td><td style='padding:8px 0'>" . htmlspecialchars($package) . "</td></tr>" : '') . "
                          " . ($safariDate ? "<tr><td style='padding:8px 0;color:#666'>Safari Date</td><td style='padding:8px 0'>" . date('F j, Y', strtotime($safariDate)) . "</td></tr>" : '') . "
                        </table>
                        <div style='margin-top:20px;padding:16px;background:#f8fbf5;border-left:4px solid #5eb832;border-radius:4px'>
                          <strong style='color:#1a3a1a'>Message:</strong>
                          <p style='margin:8px 0 0;color:#444;line-height:1.7'>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                      </div>
                      <div style='padding:16px 30px;background:#f5f5f5;text-align:center'>
                        <a href='mailto:" . htmlspecialchars($email) . "?subject=Re: Your YalaSafari Inquiry'
                           style='display:inline-block;padding:10px 24px;background:#1a3a1a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600'>
                          Reply to " . htmlspecialchars($firstName) . "
                        </a>
                      </div>
                    </div>";

                    $mail->AltBody = "New inquiry from $firstName $lastName\nEmail: $email\nPhone: $phone\nMessage: $message";
                    $mail->send();

                    // Email 2: Confirm to customer 
                    $mail->clearAddresses();
                    $mail->clearReplyTos();
                    $mail->setFrom($smtpUser, $fromName);
                    $mail->addAddress($email, "$firstName $lastName");
                    $mail->Subject = "We received your inquiry – YalaSafari";
                    $mail->Body    = "
                    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden'>
                      <div style='background:#1a3a1a;padding:24px 30px'>
                        <h2 style='color:#5eb832;margin:0'>🦁 YalaSafari</h2>
                        <p style='color:#a0c080;margin:6px 0 0'>Thank you for reaching out!</p>
                      </div>
                      <div style='padding:28px 30px;background:#fff'>
                        <p style='font-size:16px;color:#1a3a1a'>Dear <strong>" . htmlspecialchars($firstName) . "</strong>,</p>
                        <p style='color:#444;line-height:1.7'>Thank you for contacting YalaSafari. We have received your message and will get back to you within <strong>24 hours</strong>.</p>
                        <div style='margin:24px 0;padding:16px;background:#f8fbf5;border-radius:8px;border:1px solid #c8e6b0'>
                          <p style='margin:0;color:#1a3a1a;font-weight:600'>Your Message Summary</p>
                          <p style='margin:8px 0 0;color:#555;line-height:1.6'>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                        <p style='color:#444;line-height:1.7'>In the meantime, feel free to reach us at <a href='mailto:" . htmlspecialchars($recipient) . "' style='color:#2563eb'>" . htmlspecialchars($recipient) . "</a> or on WhatsApp.</p>
                        <p style='color:#1a3a1a;font-weight:600;margin-top:24px'>We look forward to welcoming you to Yala! 🦁</p>
                        <p style='color:#666'>Warm regards,<br/><strong>The YalaSafari Team</strong></p>
                      </div>
                      <div style='padding:14px 30px;background:#f5f5f5;text-align:center;font-size:12px;color:#999'>
                        &copy; " . date('Y') . " YalaSafari. All rights reserved.
                      </div>
                    </div>";
                    $mail->AltBody = "Dear $firstName, thank you for contacting YalaSafari. We'll get back to you within 24 hours.";
                    $mail->send();
                    $mailSent = true;

                } catch (Exception $e) {
                    error_log('PHPMailer error: ' . $e->getMessage());
                }
            }

            // Success if saved to DB 
            if ($dbSaved) {
                // Generate booking reference from captured insert ID
                $bookingRef = 'YS-' . date('Y') . '-' . str_pad($insertId, 4, '0', STR_PAD_LEFT);
                // Save reference back to DB
                $updStmt = $db->prepare('UPDATE messages SET booking_ref=? WHERE id=?');
                $updStmt->bind_param('si', $bookingRef, $insertId);
                $updStmt->execute(); $updStmt->close();
                // Store in session and redirect to confirmation page
                $_SESSION['booking'] = [
                    'ref'       => $bookingRef,
                    'firstName' => $firstName,
                    'lastName'  => $lastName,
                    'email'     => $email,
                    'phone'     => $phone,
                    'package'   => $package,
                    'date'      => $safariDate,
                    'message'   => $message,
                    'time'      => date('Y-m-d H:i:s'),
                ];
                header('Location: booking-confirmation.php');
                exit;
            } else {
                $formStatus  = 'error';
                $formMessage = 'Sorry, something went wrong. Please try again or contact us directly.';
            }
        }
    }
}

// Page setup 
// Pre-fill from URL params (e.g. from packages page) 
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($formData)) {
    $nameParts = explode(' ', trim($_GET['name'] ?? ''), 2);
    if (!empty($nameParts[0])) {
        $formData['firstName']  = $nameParts[0];
        $formData['lastName']   = $nameParts[1] ?? '';
        $formData['email']      = $_GET['email']   ?? '';
        $formData['phone']      = $_GET['phone']   ?? '';
        $formData['message']    = $_GET['message'] ?? '';
    }
    // Package pre-select from Book Now button
    if (!empty($_GET['package'])) {
        $formData['package'] = $_GET['package'];
    }
}
$pageTitle = 'Contact Us';
$pageDesc  = 'Get in touch with YalaSafari to plan your perfect safari adventure in Yala National Park, Sri Lanka.';
$pageKw    = 'contact YalaSafari, book safari, Yala National Park booking, safari inquiry Sri Lanka';
$pageCSS   = 'contact.css';
require_once 'includes/header.php';
?>


  <!-- PAGE HERO -->
  <section class="page-hero">
    <div class="page-hero-bg" id="pageHeroBg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span class="breadcrumb-sep">›</span>
        <span class="current">Contact</span>
      </div>
      <h1>Contact Us</h1>
      <p>Get in touch with us to plan your perfect safari adventure in Yala National Park</p>
    </div>
  </section>


  <!-- INFO CARDS -->
  <section class="contact-info-section">
    <div class="container">

      <div class="contact-info-header">
        <h2 class="section-title reveal">We'd Love to Hear From You</h2>
        <p class="section-subtitle reveal reveal-delay-1">Reach out to us for bookings, inquiries, or any questions about our safari packages</p>
      </div>

      <div class="contact-info-cards">

        <!-- Location -->
        <div class="info-card reveal">
          <div class="info-card-icon">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <h4>Our Location</h4>
          <p><?= nl2br(htmlspecialchars($settings['address'] ?: "Yala Road,\nTissamaharama,\nSri Lanka")) ?></p>
        </div>

        <!-- Phone -->
        <div class="info-card reveal reveal-delay-1">
          <div class="info-card-icon">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a2 2 0 012-2.18h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/>
            </svg>
          </div>
          <h4>Phone Numbers</h4>
          <?php foreach (['phone1','phone2','phone3'] as $pk): ?>
            <?php if (!empty($settings[$pk])): ?>
              <a href="tel:<?= htmlspecialchars($settings[$pk]) ?>"><?= htmlspecialchars($settings[$pk]) ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <!-- Email -->
        <div class="info-card reveal reveal-delay-2">
          <div class="info-card-icon">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
          </div>
          <h4>Email Addresses</h4>
          <?php foreach (['info_email','bookings_email','support_email'] as $ek): ?>
            <?php if (!empty($settings[$ek])): ?>
              <a href="mailto:<?= htmlspecialchars($settings[$ek]) ?>"><?= htmlspecialchars($settings[$ek]) ?></a>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>

        <!-- Social -->
        <div class="info-card reveal reveal-delay-3">
          <div class="info-card-icon">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="2" y1="12" x2="22" y2="12"/>
              <path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
            </svg>
          </div>
          <h4>Website &amp; Social</h4>
          <?php if (!empty($settings['website_url'])): ?>
            <a href="<?= htmlspecialchars($settings['website_url']) ?>" target="_blank" rel="noopener">
              <?= htmlspecialchars(preg_replace('#^https?://#', '', $settings['website_url'])) ?>
            </a>
          <?php endif; ?>
          <?php if (!empty($settings['facebook'])): ?>
            <a href="<?= htmlspecialchars($settings['facebook']) ?>" target="_blank" rel="noopener noreferrer">Facebook</a>
          <?php endif; ?>
          <?php if (!empty($settings['instagram'])): ?>
            <a href="<?= htmlspecialchars($settings['instagram']) ?>" target="_blank" rel="noopener noreferrer">Instagram</a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>


  <!-- FORM + MAP -->
  <section class="contact-main">
    <div class="container">
      <div class="contact-main-grid">

        <!-- Form side -->
        <div class="contact-form-side reveal reveal-left" id="contactForm">
          <h3>Send Us a Message</h3>
          <p>Fill out the form below and we'll get back to you within 24 hours</p>

          <?php if ($formStatus === 'success'): ?>
            <!-- Success message -->
            <div class="form-success visible">
              <div class="form-success-icon">
                <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="20 6 9 17 4 12"/>
                </svg>
              </div>
              <h4>Message Sent Successfully!</h4>
              <p>Thank you for reaching out. We'll get back to you within 24 hours. Check your email for a confirmation.</p>
              <a href="contact.php" style="display:inline-block;margin-top:14px;padding:10px 24px;background:#1a3a1a;color:#fff;border-radius:6px;font-weight:600;text-decoration:none;">
                Send Another Message
              </a>
            </div>

          <?php else: ?>
            <!-- Error alert -->
            <?php if ($formStatus === 'error' && $formMessage): ?>
              <div style="background:#fee2e2;border:1.5px solid #fca5a5;color:#dc2626;padding:13px 16px;border-radius:8px;margin-bottom:20px;font-size:0.88rem;font-weight:600;">
                ❌ <?= htmlspecialchars($formMessage) ?>
              </div>
            <?php endif; ?>

            <!-- Contact Form -->
            <form id="contactFormEl" method="POST" action="contact.php" novalidate>
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>

              <div class="response-badge">
                <span class="response-dot"></span>
                ⚡ Average response time: <strong>under 2 hours</strong>
              </div>

              <div class="form-row-2">
                <div class="form-group">
                  <label for="firstName">First Name <span class="required">*</span></label>
                  <input type="text" id="firstName" name="first_name"
                    value="<?= htmlspecialchars($formData['firstName'] ?? '') ?>"
                    placeholder="Enter Your First Name" required/>
                </div>
                <div class="form-group">
                  <label for="lastName">Last Name <span class="required">*</span></label>
                  <input type="text" id="lastName" name="last_name"
                    value="<?= htmlspecialchars($formData['lastName'] ?? '') ?>"
                    placeholder="Enter Your Last Name" required/>
                </div>
              </div>

              <div class="form-row-2">
                <div class="form-group">
                  <label for="emailAddr">Email Address <span class="required">*</span></label>
                  <input type="email" id="emailAddr" name="email"
                    value="<?= htmlspecialchars($formData['email'] ?? '') ?>"
                    placeholder="Enter Your Email" required/>
                </div>
                <div class="form-group">
                  <label for="phoneNum">Phone Number <span class="required">*</span></label>
                  <input type="tel" id="phoneNum" name="phone"
                    value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
                    placeholder="Enter Your Phone Number" required/>
                </div>
              </div>

              <div class="form-row-2">
                <div class="form-group">
                  <label for="packageSel">Interested Package</label>
                  <div class="select-wrap">
                    <select id="packageSel" name="package">
                      <option value="">Select a Package</option>
                      <?php foreach ($packages as $pkg): ?>
                        <?php
                          $label = htmlspecialchars($pkg['name']);
                          if ($pkg['price'] > 0) {
                              $label .= ' – Rs ' . number_format($pkg['price']);
                          }
                          $selected = ($formData['package'] ?? '') === $pkg['name'] ? 'selected' : '';
                        ?>
                        <option value="<?= htmlspecialchars($pkg['name']) ?>" <?= $selected ?>>
                          <?= $label ?>
                        </option>
                      <?php endforeach; ?>
                      <option value="Custom Package" <?= ($formData['package'] ?? '') === 'Custom Package' ? 'selected' : '' ?>>
                        Custom Package
                      </option>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="safariDate">Preferred Safari Date</label>
                  <input type="date" id="safariDate" name="safari_date"
                    min="<?= date('Y-m-d') ?>"
                    value="<?= htmlspecialchars($formData['safariDate'] ?? '') ?>"/>
                </div>
              </div>

              <div class="form-group">
                <label for="message">Your Message <span class="required">*</span></label>
                <textarea id="message" name="message"
                  placeholder="Tell us about your safari plans..." required><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
              </div>

              <button type="submit" class="form-submit" id="submitBtn">
                Send Message
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="22" y1="2" x2="11" y2="13"/>
                  <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
              </button>

            </form>
          <?php endif; ?>

        </div>

        <!-- Map side -->
        <div class="contact-map-side reveal reveal-right">
          <h3>Find Us Here</h3>
          <p>Yala Safari Tours, Tissamaharama – Gateway to Yala National Park</p>
          <div class="contact-map-embed">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3969.8503246826!2d81.29145!3d6.28601!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae6b5c0b0b0b0b1%3A0x0!2sYala+National+Park!5e0!3m2!1sen!2slk!4v1234567890"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              title="Yala Safari Location">
            </iframe>
          </div>
        </div>

      </div>
    </div>
  </section>


  <!-- BUSINESS HOURS -->
  <section class="business-hours">
    <div class="container">
      <div class="business-hours-header">
        <h2 class="section-title reveal">Business Hours</h2>
      </div>
      <div class="hours-grid reveal">
        <?php foreach ($hours as $h): ?>
          <div class="hours-row <?= $h['is_closed'] ? 'closed' : '' ?>" data-day="<?= htmlspecialchars($h['day']) ?>">
            <span class="hours-day"><?= htmlspecialchars($h['day']) ?></span>
            <span class="hours-time">
              <?php if ($h['is_closed']): ?>
                <span style="color:#dc2626;font-weight:600;">Closed</span>
              <?php else: ?>
                <?= date('g:i A', strtotime($h['open_time'])) ?> – <?= date('g:i A', strtotime($h['close_time'])) ?>
              <?php endif; ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- CTA -->
  <?php require_once 'includes/cta.php'; ?>




  <!-- REVIEW FORM -->
  <section class="review-section" id="reviewForm">

    <!-- Decorative blobs -->
    <div class="review-blob review-blob-1"></div>
    <div class="review-blob review-blob-2"></div>

    <div class="container">
      <div class="review-layout">

        <!-- Left: Trust panel -->
        <div class="review-trust reveal">

          <div class="review-trust-badge">
            <span>⭐</span> Guest Reviews
          </div>

          <h2>Your Story <br/>Inspires Others</h2>
          <p>Every review helps future adventurers discover the magic of Yala. Share your experience and become part of our safari family.</p>

          <!-- Animated stats -->
          <div class="review-stats">
            <div class="review-stat" style="--delay:0.1s">
              <div class="review-stat-icon">🦁</div>
              <div class="review-stat-info">
                <strong class="count-up" data-target="500">0</strong>+
                <span>Happy Guests</span>
              </div>
            </div>
            <div class="review-stat" style="--delay:0.2s">
              <div class="review-stat-icon">⭐</div>
              <div class="review-stat-info">
                <strong>4.9</strong>
                <span>Average Rating</span>
              </div>
            </div>
            <div class="review-stat" style="--delay:0.3s">
              <div class="review-stat-icon">🌍</div>
              <div class="review-stat-info">
                <strong class="count-up" data-target="30">0</strong>+
                <span>Countries</span>
              </div>
            </div>
            <div class="review-stat" style="--delay:0.4s">
              <div class="review-stat-icon">📸</div>
              <div class="review-stat-info">
                <strong class="count-up" data-target="1200">0</strong>+
                <span>Safari Memories</span>
              </div>
            </div>
          </div>

          <!-- Floating review previews -->
          <div class="review-floaters">
            <div class="review-float review-float-1">
              <div class="rf-avatar">S</div>
              <div class="rf-content">
                <div class="rf-stars">★★★★★</div>
                <div class="rf-text">"Spotted 3 leopards!"</div>
                <div class="rf-name">Sarah, UK</div>
              </div>
            </div>
            <div class="review-float review-float-2">
              <div class="rf-avatar">J</div>
              <div class="rf-content">
                <div class="rf-stars">★★★★★</div>
                <div class="rf-text">"Absolutely magical!"</div>
                <div class="rf-name">James, Japan</div>
              </div>
            </div>
            <div class="review-float review-float-3">
              <div class="rf-avatar">P</div>
              <div class="rf-content">
                <div class="rf-stars">★★★★★</div>
                <div class="rf-text">"Best day of my trip!"</div>
                <div class="rf-name">Priya, Australia</div>
              </div>
            </div>
          </div>

        </div>

        <!-- Right: Form -->
        <div class="review-form-wrap reveal reveal-delay-2">

          <?php if ($reviewSuccess): ?>
            <div class="review-success">
              <div class="review-success-icon">🎉</div>
              <h3>Thank You!</h3>
              <p>Your review has been submitted and is awaiting approval. We truly appreciate your feedback!</p>
            </div>
          <?php else: ?>

            <div class="review-form-header">
              <h3>Write a Review</h3>
              <p>Takes less than 2 minutes</p>
            </div>

            <?php if ($reviewError): ?>
              <div class="alert-inline"><?= htmlspecialchars($reviewError) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="review-form">
              <input type="hidden" name="review_submit" value="1"/>
              <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>

              <!-- Star rating — prominent at top -->
              <div class="review-rating-block">
                <label>How was your experience?</label>
                <div class="review-star-select">
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="review_rating" id="rstar<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>/>
                    <label for="rstar<?= $i ?>">★</label>
                  <?php endfor; ?>
                </div>
                <div class="star-label" id="starLabel">Excellent!</div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label>Your Name *</label>
                  <input type="text" name="review_name" placeholder="e.g. John Smith" required/>
                </div>
                <div class="form-group">
                  <label>Country / City</label>
                  <input type="text" name="review_location" placeholder="e.g. United Kingdom"/>
                </div>
              </div>

              <div class="form-group">
                <label>Your Review *</label>
                <textarea name="review_message" rows="4" placeholder="Tell us about your safari experience — what did you see? What made it special?" required></textarea>
              </div>

              <div class="form-group">
                <label>Your Photo <span style="font-weight:400;color:var(--text-muted)">(optional)</span></label>
                <input type="file" name="review_photo" accept="image/*" class="review-file-input"/>
                <p class="field-hint">JPG, PNG or WebP · max 3MB</p>
              </div>

              <button type="submit" class="review-submit-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Submit My Review
              </button>
              <p class="review-note">⏳ Reviews are moderated before going live</p>
            </form>

          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>

<?php require_once 'includes/footer.php'; ?>

<script>
// Review section animations 
const reviewObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      // Animate stats
      entry.target.querySelectorAll('.review-stat, .review-float').forEach(el => {
        el.classList.add('visible');
      });
      // Count-up numbers
      entry.target.querySelectorAll('.count-up').forEach(el => {
        const target = parseInt(el.dataset.target);
        const duration = 1800;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
          current = Math.min(current + step, target);
          el.textContent = Math.floor(current).toLocaleString();
          if (current >= target) clearInterval(timer);
        }, 16);
      });
      reviewObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.2 });

const reviewSection = document.querySelector('.review-section');
if (reviewSection) reviewObserver.observe(reviewSection);

// Star rating labels 
const starLabels = { 5: 'Excellent! 🌟', 4: 'Very Good! 😊', 3: 'Good 👍', 2: 'Fair 😐', 1: 'Poor 😞' };
document.querySelectorAll('.review-star-select input').forEach(input => {
  input.addEventListener('change', () => {
    const label = document.getElementById('starLabel');
    if (label) label.textContent = starLabels[input.value] || '';
  });
});

// Auto scroll to form if coming from Book Now
window.addEventListener('load', () => {
  if (window.location.hash === '#contactForm') {
    const target = document.getElementById('contactForm');
    if (target) {
      setTimeout(() => {
        const headerH = document.getElementById('site-header')?.offsetHeight || 80;
        const top = target.getBoundingClientRect().top + window.scrollY - headerH - 20;
        window.scrollTo({ top, behavior: 'smooth' });
      }, 300);
    }
  }
});

// Hero zoom on load
window.addEventListener('load', () => {
  document.getElementById('pageHeroBg')?.classList.add('loaded');
});

// Highlight today's hours row
const days    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const today   = days[new Date().getDay()];
document.querySelectorAll('.hours-row').forEach(row => {
  if (row.dataset.day === today) row.classList.add('today');
});

// Client-side validation feedback
document.getElementById('contactFormEl')?.addEventListener('submit', function(e) {
  let valid = true;
  this.querySelectorAll('[required]').forEach(field => {
    if (!field.value.trim()) {
      field.style.borderColor = '#e05252';
      field.style.boxShadow   = '0 0 0 3px rgba(224,82,82,0.12)';
      valid = false;
      field.addEventListener('input', () => {
        field.style.borderColor = '';
        field.style.boxShadow   = '';
      }, { once: true });
    }
  });
  if (!valid) { e.preventDefault(); return; }

  // Loading state on button
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
    style="animation:spin 0.8s linear infinite"><circle cx="12" cy="12" r="10" stroke-dasharray="40" stroke-dashoffset="15"/></svg> Sending...`;
});
</script>
<style>@keyframes spin { to { transform:rotate(360deg); } }</style>

</body>
</html>
