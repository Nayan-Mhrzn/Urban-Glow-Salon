<?php
/**
 * Get Available Time Slots API - Urban Glow Salon
 */
require_once dirname(__DIR__) . '/includes/config.php';

header('Content-Type: application/json');

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['slots' => []]);
    exit;
}

$slots = getTimeSlots($date, $pdo);
echo json_encode(['slots' => $slots]);
