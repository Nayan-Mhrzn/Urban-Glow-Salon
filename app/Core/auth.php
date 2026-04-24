<?php
/**
 * Authentication Helpers - Urban Glow Salon
 */

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN';
}

/**
 * Check if user is staff
 */
function isStaff() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'STAFF';
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'Access denied. Admin privileges required.');
        redirect(SITE_URL . '/index.php');
    }
}

/**
 * Require staff access (allows both STAFF and ADMIN)
 */
function requireStaff() {
    requireLogin();
    if (!isAdmin() && !isStaff()) {
        setFlash('error', 'Access denied. Staff privileges required.');
        redirect(SITE_URL . '/index.php');
    }
}

/**
 * Get current user data
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, phone, role, profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Login user - set session variables
 */
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? null;
}

/**
 * Logout user - destroy session
 */
function logoutUser() {
    session_unset();
    session_destroy();
}
