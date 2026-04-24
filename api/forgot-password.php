<?php
/**
 * Forgot Password API Handler - Urban Glow Salon
 * Generates a 6-digit verification code and emails it to the user.
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

$email = trim($_POST['email'] ?? '');

// Validate email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    redirect(SITE_URL . '/forgot-password.php');
}

// Look up the user
$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Don't reveal whether the email exists (security best practice)
    setFlash('success', 'If an account with that email exists, a verification code has been sent.');
    redirect(SITE_URL . '/forgot-password.php');
}

// Invalidate any existing unused tokens for this user
$stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = TRUE WHERE user_id = ? AND used = FALSE");
$stmt->execute([$user['id']]);

// Generate a 6-digit verification code
$verificationCode = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

// Also generate a secure token for the URL (prevents brute force)
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Save to database
$stmt = $pdo->prepare("INSERT INTO password_reset_tokens (user_id, token, verification_code, expires_at) VALUES (?, ?, ?, ?)");
$stmt->execute([$user['id'], $token, $verificationCode, $expiresAt]);

// Send the verification code via SMTP
require_once CORE_PATH . '/mailer.php';
$emailSent = sendPasswordResetCodeEmail($user['email'], $user['full_name'], $verificationCode);

// Store in session for the verification step
$_SESSION['reset_token'] = $token;
$_SESSION['reset_email_display'] = $user['email'];
$_SESSION['reset_code_sent'] = true;

if ($emailSent) {
    setFlash('success', 'A 6-digit verification code has been sent to your email!');
} else {
    // Fallback: show the code on screen for demo/local testing
    $_SESSION['reset_code_fallback'] = $verificationCode;
    setFlash('success', 'Verification code generated. Check your email or use the code shown below.');
}
redirect(SITE_URL . '/forgot-password.php');
