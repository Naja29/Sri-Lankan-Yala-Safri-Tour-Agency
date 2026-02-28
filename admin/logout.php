<?php
require_once __DIR__ . '/includes/auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
    logout();
}
header('Location: login.php');
exit;
