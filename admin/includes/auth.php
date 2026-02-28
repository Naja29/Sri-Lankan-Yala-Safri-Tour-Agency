<?php

// auth.php — Session & Auth Helper

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,     // 1 day
        'cookie_secure'   => true,       // HTTPS only
        'cookie_httponly' => true,       // No JS access
        'cookie_samesite' => 'Strict',
    ]);
}

// Require login 
function requireLogin(): void {
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Check if logged in 
function isLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

// Get current admin 
function currentAdmin(): array {
    return [
        'id'       => $_SESSION['admin_id']   ?? 0,
        'username' => $_SESSION['admin_user'] ?? '',
        'name'     => $_SESSION['admin_name'] ?? 'Admin',
    ];
}

// Logout 
function logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

// CSRF token 
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
