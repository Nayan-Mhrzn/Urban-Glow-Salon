<?php
/**
 * Book Service API - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/book-appointment.php');
}

requireLogin();

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect(SITE_URL . '/book-appointment.php');
}

$service_id = (int)($_POST['service_id'] ?? 0);
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';
$notes = trim($_POST['notes'] ?? '');

// Validate
if (!$service_id || empty($booking_date) || empty($booking_time)) {
    setFlash('error', 'Please fill in all required fields.');
    redirect(SITE_URL . '/book-appointment.php');
}

// Check if slot is still available
$stmt = $pdo->prepare("SELECT id FROM bookings WHERE booking_date = ? AND booking_time = ? AND status != 'Cancelled'");
$stmt->execute([$booking_date, $booking_time]);
if ($stmt->fetch()) {
    setFlash('error', 'This time slot is no longer available. Please choose another.');
    redirect(SITE_URL . '/book-appointment.php');
}

// Create booking
$dayOfWeek = (int) date('N', strtotime($booking_date)); // 1=Mon..7=Sun
$stmt = $pdo->prepare("INSERT INTO bookings (user_id, service_id, booking_date, booking_time, notes, day_of_week) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$_SESSION['user_id'], $service_id, $booking_date, $booking_time, $notes, $dayOfWeek]);

// Log "selected" interaction for scoring engine feedback
try {
    $logStmt = $pdo->prepare(
        "INSERT INTO slot_interaction_logs (customer_id, service_id, slot_date, slot_time, action)
         VALUES (?, ?, ?, ?, 'selected')"
    );
    $logStmt->execute([$_SESSION['user_id'], $service_id, $booking_date, $booking_time]);
} catch (PDOException $e) {
    error_log('slot_interaction_logs insert on booking failed: ' . $e->getMessage());
}

// Get created booking ID
$bookingId = $pdo->lastInsertId();

setFlash('success', 'Appointment booked successfully! We look forward to seeing you.');
redirect(SITE_URL . '/booking-success.php?id=' . $bookingId);
