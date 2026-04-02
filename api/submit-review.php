<?php
/**
 * Submit Review API - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/customer/reviews.php');
}

requireLogin();

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect(SITE_URL . '/customer/reviews.php');
}

$review_type = $_POST['review_type'] ?? 'Service';
$rating = (int)($_POST['rating'] ?? 5);
$comment = trim($_POST['comment'] ?? '');

if (empty($comment)) {
    setFlash('error', 'Please write a review.');
    redirect(SITE_URL . '/customer/reviews.php');
}

if ($rating < 1 || $rating > 5) $rating = 5;

$stmt = $pdo->prepare("INSERT INTO reviews (user_id, review_type, reference_id, rating, comment) VALUES (?, ?, 0, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $review_type, $rating, $comment]);

setFlash('success', 'Thank you for your review!');
redirect(SITE_URL . '/customer/reviews.php');
