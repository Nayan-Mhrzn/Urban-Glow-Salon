<?php
/**
 * Log Slot Interaction API — Adaptive Slot Recommendation
 * Urban Glow Salon
 *
 * POST body (JSON): { slot_time, date, action, service_id }
 * Actions: "shown", "selected", "skipped"
 */
require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

// ── Must be POST ──
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ── Must be logged in ──
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$customerId = (int) $_SESSION['user_id'];

// ── Parse JSON body ──
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$slotTime  = $input['slot_time'] ?? '';
$date      = $input['date'] ?? '';
$action    = $input['action'] ?? '';
$serviceId = (int) ($input['service_id'] ?? 0);

// ── Validate ──
$validActions = ['shown', 'selected', 'skipped'];

if (empty($slotTime) || empty($date) || !in_array($action, $validActions) || !$serviceId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid parameters: slot_time, date, action, service_id']);
    exit;
}

if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $slotTime)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid slot_time format']);
    exit;
}

// Normalize to H:i:s
if (strlen($slotTime) === 5) {
    $slotTime .= ':00';
}

// ── Insert interaction log ──
try {
    $stmt = $pdo->prepare(
        "INSERT INTO slot_interaction_logs (customer_id, service_id, slot_date, slot_time, action)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$customerId, $serviceId, $date, $slotTime, $action]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('slot_interaction_logs insert failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to log interaction']);
}

