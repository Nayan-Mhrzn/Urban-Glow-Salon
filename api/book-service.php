<?php
/**
 * Book Service API - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/app/Config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(SITE_URL . '/booking/services.php');
}

requireLogin();

$referer = $_SERVER['HTTP_REFERER'] ?? (SITE_URL . '/booking/services.php');

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid request.');
    redirect($referer);
}

$service_id = (int)($_POST['service_id'] ?? 0);
$booking_date = $_POST['booking_date'] ?? '';
$booking_time = $_POST['booking_time'] ?? '';
$notes = trim($_POST['notes'] ?? '');
$staff_id = !empty($_POST['staff_id']) ? (int)$_POST['staff_id'] : null;

// Validate
if (!$service_id || empty($booking_date) || empty($booking_time)) {
    setFlash('error', 'Please fill in all required fields.');
    redirect($referer);
}

// Step 1: Find valid available staff
$stmt = $pdo->prepare("
    SELECT u.id 
    FROM users u
    JOIN staff_services ss ON u.id = ss.staff_id
    WHERE ss.service_id = ? 
      AND u.role = 'STAFF'
      AND u.id NOT IN (
          SELECT staff_id FROM bookings 
          WHERE booking_date = ? 
            AND booking_time = ? 
            AND status != 'Cancelled'
            AND staff_id IS NOT NULL
      )
");
$stmt->execute([$service_id, $booking_date, $booking_time]);
$availableStaff = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Step 2: Validate slot availability
if (empty($availableStaff)) {
    setFlash('error', 'This time slot is no longer available. Please choose another.');
    redirect($referer);
}

// Step 3: Auto-assign staff if not explicitly provided
if (empty($staff_id)) {
    // Basic Affinity Algorithm: Has this user booked any of these available staff before?
    $histStmt = $pdo->prepare("
        SELECT staff_id, COUNT(*) as cnt 
        FROM bookings 
        WHERE user_id = ? 
          AND status IN ('Completed', 'Confirmed', 'Pending') 
          AND staff_id IS NOT NULL
        GROUP BY staff_id 
        ORDER BY cnt DESC
    ");
    $histStmt->execute([$_SESSION['user_id']]);
    $history = $histStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $bestStaffId = null;
    foreach ($history as $sid => $count) {
        if (in_array($sid, $availableStaff)) {
            $bestStaffId = $sid;
            break;
        }
    }
    
    // If no affinity match, randomly assign an available staff member to fill the booking
    $staff_id = $bestStaffId ?: $availableStaff[array_rand($availableStaff)];
} else {
    // Validate the explicitly picked staff is still free
    if (!in_array($staff_id, $availableStaff)) {
        setFlash('error', 'The assigned specialist is no longer available at this time. Please choose another slot.');
        redirect($referer);
    }
}

// Create booking (auto-confirmed since staff was strictly validated)
$dayOfWeek = (int) date('N', strtotime($booking_date)); // 1=Mon..7=Sun
$stmt = $pdo->prepare("INSERT INTO bookings (user_id, service_id, booking_date, booking_time, notes, day_of_week, staff_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Confirmed')");
$stmt->execute([$_SESSION['user_id'], $service_id, $booking_date, $booking_time, $notes, $dayOfWeek, $staff_id]);

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

// Send booking confirmation email
require_once dirname(__DIR__) . '/core/mailer.php';
$userInfo = getCurrentUser($pdo);
if ($userInfo && !empty($userInfo['email'])) {
    // Fetch service details
    $svcStmt = $pdo->prepare("SELECT name, price FROM services WHERE id = ?");
    $svcStmt->execute([$service_id]);
    $serviceInfo = $svcStmt->fetch();

    // Fetch staff name
    $staffStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $staffStmt->execute([$staff_id]);
    $staffInfo = $staffStmt->fetch();

    $emailResult = sendBookingConfirmationEmail(
        $userInfo['email'],
        $userInfo['full_name'],
        $bookingId,
        $serviceInfo['name'] ?? 'Salon Service',
        $booking_date,
        $booking_time,
        $staffInfo['full_name'] ?? 'Auto-assigned',
        $serviceInfo['price'] ?? 0
    );

    if (!$emailResult) {
        error_log("Booking email failed for booking #{$bookingId}, user: " . $userInfo['email']);
    }
} else {
    error_log("Booking email skipped: no user info for session user_id=" . ($_SESSION['user_id'] ?? 'none'));
}

setFlash('success', 'Appointment booked successfully! We look forward to seeing you.');
redirect(SITE_URL . '/booking/booking-success.php?id=' . $bookingId);

