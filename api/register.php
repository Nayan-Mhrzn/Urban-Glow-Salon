<?php
/**
 * Register API Handler - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/login.php');
}

// Verify CSRF
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request. Please try again.');
    redirect(SITE_URL . '/login.php');
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate required fields
if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
    setFlash('error', 'Please fill in all required fields.');
    redirect(SITE_URL . '/login.php');
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
    redirect(SITE_URL . '/login.php');
}

// Validate password length
if (strlen($password) < 6) {
    setFlash('error', 'Password must be at least 6 characters.');
    redirect(SITE_URL . '/login.php');
}

// Confirm password match
if ($password !== $confirm_password) {
    setFlash('error', 'Passwords do not match.');
    redirect(SITE_URL . '/login.php');
}

// Check if username exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    setFlash('error', 'Username already taken. Please choose another.');
    redirect(SITE_URL . '/login.php');
}

// Check if email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    setFlash('error', 'Email already registered. Please login instead.');
    redirect(SITE_URL . '/login.php');
}

// Hash password and insert
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'CUSTOMER')");
$stmt->execute([$username, $email, $hashedPassword, $full_name, $phone]);

// Auto-login after registration
$user = [
    'id' => $pdo->lastInsertId(),
    'username' => $username,
    'role' => 'CUSTOMER',
    'full_name' => $full_name
];
loginUser($user);

setFlash('success', 'Account created successfully! Welcome, ' . $full_name . '!');
redirect(SITE_URL . '/index.php');
