<?php
/**
 * Login API Handler - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/login.php');
}

// Verify CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(SITE_URL . '/login.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate
if (empty($username) || empty($password)) {
    setFlash('error', 'Please fill in all fields.');
    redirect(SITE_URL . '/login.php');
}

// Find user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    setFlash('error', 'Invalid username or password.');
    redirect(SITE_URL . '/login.php');
}

// Login successful
loginUser($user);
setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');

// Role-based redirect
switch ($user['role']) {
    case 'ADMIN':
        redirect(SITE_URL . '/admin/');
        break;
    case 'STAFF':
        redirect(SITE_URL . '/staff/');
        break;
    default:
        redirect(SITE_URL . '/index.php');
}

