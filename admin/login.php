<?php
//  admin/login.php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db_config.php';

// Already logged in → go to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$username = '';

// Handle POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password']  ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $db   = getDB();
            $stmt = $db->prepare('SELECT id, username, password, full_name FROM admins WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin  = $result->fetch_assoc();
            $stmt->close();

            if ($admin && password_verify($password, $admin['password'])) {
                // ✅ Login success
                session_regenerate_id(true);
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_user'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];

                // Remember Me — extend session cookie
                if (!empty($_POST['remember'])) {
                    setcookie(session_name(), session_id(), time() + 86400 * 30, '/', '', true, true);
                }

                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Incorrect username or password. Please try again.';
                // Small delay to slow brute force
                sleep(1);
            }
        }
    }
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Login – YalaSafari</title>
  <meta name="robots" content="noindex, nofollow"/>
  <link rel="icon" href="../images/icons/favicon.ico" type="image/x-icon"/>
  <link rel="stylesheet" href="css/login.css"/>
</head>
<body>

  <!-- Background -->
  <div class="login-bg"></div>
  <div class="login-bg-overlay"></div>

  <!-- Login Card -->
  <div class="login-card">

    <!-- Header -->
    <div class="login-header">
      <a href="../index.html" class="login-logo">
        <img
          src="../images/icons/logo-admin.png"
          alt="YalaSafari"
          onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
        />
        <span style="display:none">Yala<span>Safari</span></span>
      </a>
      <p class="login-subtitle">Admin Control Panel</p>
    </div>

    <hr class="login-divider"/>

    <!-- Error Message -->
    <?php if ($error): ?>
    <div class="login-error">
      <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
      </svg>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" action="login.php" id="loginForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>

      <!-- Username -->
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrap">
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Enter Username"
            value="<?= htmlspecialchars($username) ?>"
            autocomplete="username"
            required
            autofocus
          />
          <svg class="input-icon" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
      </div>

      <!-- Password -->
      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter Password"
            autocomplete="current-password"
            required
          />
          <svg class="input-icon" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
          </svg>
          <button type="button" class="toggle-pw" id="togglePw" aria-label="Toggle password visibility">
            <svg id="eyeIcon" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Remember Me -->
      <div class="remember-row">
        <input type="checkbox" id="remember" name="remember" value="1"/>
        <label for="remember">Remember Me</label>
      </div>

      <!-- Submit -->
      <button type="submit" class="btn-login" id="loginBtn">
        Login to Dashboard
      </button>

    </form>

    <!-- Back to website -->
    <a href="../index.html" class="back-link">
      <svg viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="19" y1="12" x2="5" y2="12"/>
        <polyline points="12 19 5 12 12 5"/>
      </svg>
      Back to Website
    </a>

  </div>

  <script>
    // Toggle password visibility 
    const togglePw  = document.getElementById('togglePw');
    const pwInput   = document.getElementById('password');
    const eyeIcon   = document.getElementById('eyeIcon');

    const eyeOpen   = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const eyeClosed = `<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;

    togglePw.addEventListener('click', () => {
      const isHidden = pwInput.type === 'password';
      pwInput.type       = isHidden ? 'text' : 'password';
      eyeIcon.innerHTML  = isHidden ? eyeClosed : eyeOpen;
    });

    // Loading state on submit 
    document.getElementById('loginForm').addEventListener('submit', function() {
      const btn = document.getElementById('loginBtn');
      btn.classList.add('loading');
      btn.textContent = 'Logging in...';
    });
  </script>

</body>
</html>
