<?php
/**
 * Verify Reset Code API Handler - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/app/Config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/forgot-password.php');
}

// Verify CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(SITE_URL . '/forgot-password.php');
}

$code1 = trim($_POST['code1'] ?? '');
$code2 = trim($_POST['code2'] ?? '');
$code3 = trim($_POST['code3'] ?? '');
$code4 = trim($_POST['code4'] ?? '');
$code5 = trim($_POST['code5'] ?? '');
$code6 = trim($_POST['code6'] ?? '');

$enteredCode = $code1 . $code2 . $code3 . $code4 . $code5 . $code6;
$token = $_SESSION['reset_token'] ?? '';

if (empty($token) || strlen($enteredCode) !== 6 || !is_numeric($enteredCode)) {
    setFlash('error', 'Please enter a valid 6-digit code.');
    redirect(SITE_URL . '/forgot-password.php');
}

// Check database for code validity
$stmt = $pdo->prepare("SELECT id, expires_at, used FROM password_reset_tokens WHERE token = ? AND verification_code = ?");
$stmt->execute([$token, $enteredCode]);
$resetRecord = $stmt->fetch();

if (!$resetRecord) {
    setFlash('error', 'Invalid verification code. Please try again.');
    redirect(SITE_URL . '/forgot-password.php');
}

if ($resetRecord['used']) {
    setFlash('error', 'This code has already been used.');
    redirect(SITE_URL . '/forgot-password.php');
}

if (strtotime($resetRecord['expires_at']) < time()) {
    setFlash('error', 'This verification code has expired. Please request a new one.');
    // Clear session
    unset($_SESSION['reset_token'], $_SESSION['reset_email_display'], $_SESSION['reset_code_sent'], $_SESSION['reset_code_fallback']);
    redirect(SITE_URL . '/forgot-password.php');
}

// Code is valid! Clear intermediate session vars and proceed to reset-password page
unset($_SESSION['reset_email_display'], $_SESSION['reset_code_sent'], $_SESSION['reset_code_fallback']);
setFlash('success', 'Code verified! Please set your new password.');
redirect(SITE_URL . '/reset-password.php?token=' . urlencode($token));
