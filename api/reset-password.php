<?php
/**
 * Reset Password API Handler - Urban Glow Salon
 * Validates the token and updates the user's password.
 */
require_once dirname(__DIR__) . '/app/Config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/login.php');
}

// Verify CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(SITE_URL . '/login.php');
}

$token = trim($_POST['token'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if (empty($token)) {
    setFlash('error', 'Invalid or missing reset token.');
    redirect(SITE_URL . '/login.php');
}

if (empty($password) || strlen($password) < 6) {
    setFlash('error', 'Password must be at least 6 characters.');
    redirect(SITE_URL . '/reset-password.php?token=' . urlencode($token));
}

if ($password !== $confirm_password) {
    setFlash('error', 'Passwords do not match.');
    redirect(SITE_URL . '/reset-password.php?token=' . urlencode($token));
}

// Look up the token
$stmt = $pdo->prepare("
    SELECT prt.id, prt.user_id, prt.expires_at, prt.used, u.full_name 
    FROM password_reset_tokens prt 
    JOIN users u ON u.id = prt.user_id 
    WHERE prt.token = ?
");
$stmt->execute([$token]);
$resetRecord = $stmt->fetch();

if (!$resetRecord) {
    setFlash('error', 'Invalid reset token. Please request a new password reset.');
    redirect(SITE_URL . '/forgot-password.php');
}

if ($resetRecord['used']) {
    setFlash('error', 'This reset link has already been used. Please request a new one.');
    redirect(SITE_URL . '/forgot-password.php');
}

if (strtotime($resetRecord['expires_at']) < time()) {
    setFlash('error', 'This reset link has expired. Please request a new one.');
    redirect(SITE_URL . '/forgot-password.php');
}

// Everything is valid — update the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hashedPassword, $resetRecord['user_id']]);

// Mark the token as used
$stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE id = ?");
$stmt->execute([$resetRecord['id']]);

setFlash('success', 'Your password has been reset successfully! Please login with your new password.');
redirect(SITE_URL . '/login.php');
