<?php
/**
 * Get Scored Slots API — Adaptive Slot Recommendation
 * Urban Glow Salon
 *
 * GET params: date, service_id
 * Returns JSON: { slots, recommended, fully_booked, high_risk }
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/slot_scorer.php';

header('Content-Type: application/json');

// ── Validate session ──
$customerId = null;
if (isLoggedIn()) {
    $customerId = (int) $_SESSION['user_id'];
}

// ── Validate inputs ──
$date = $_GET['date'] ?? '';
$serviceId = (int) ($_GET['service_id'] ?? 0);

if (empty($date) || !$serviceId) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Missing required parameters: date and service_id',
        'slots' => [],
        'recommended' => [],
        'fully_booked' => false,
        'high_risk' => false,
    ]);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid date format',
        'slots' => [],
        'recommended' => [],
        'fully_booked' => false,
        'high_risk' => false,
    ]);
    exit;
}

// ── Run scoring engine ──
$result = scoreSlots($pdo, $customerId, $serviceId, $date);

// ── Extract high_risk from any slot (applies globally to this customer) ──
$highRisk = false;
if (!empty($result['slots'])) {
    $highRisk = $result['slots'][0]['high_risk'] ?? false;
}

// ── Log scores to slot_score_logs ──
if ($customerId && !empty($result['slots'])) {
    $logStmt = $pdo->prepare(
        "INSERT INTO slot_score_logs
            (customer_id, service_id, slot_date, slot_time, history_score, affinity_score, gap_fill_score, demand_score, no_show_penalty, final_score, is_recommended)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $recTimes = array_column($result['recommended'], 'time');

    foreach ($result['slots'] as $slot) {
        try {
            $logStmt->execute([
                $customerId,
                $serviceId,
                $date,
                $slot['time'],
                $slot['history_score'],
                $slot['affinity_score'],
                $slot['gap_fill_score'],
                $slot['demand_score'],
                $slot['no_show_penalty'],
                $slot['final_score'],
                in_array($slot['time'], $recTimes) ? 1 : 0,
            ]);
        } catch (PDOException $e) {
            // Log failure shouldn't break the API response
            error_log('slot_score_logs insert failed: ' . $e->getMessage());
        }
    }
}

// ── Clean internal fields before sending to client ──
$cleanSlot = function (array $slot): array {
    return [
        'time'           => $slot['time'],
        'display'        => $slot['display'],
        'available'      => $slot['available'],
        'final_score'    => $slot['final_score'],
        'is_recommended' => $slot['is_recommended'],
    ];
};

$output = [
    'slots'       => array_map($cleanSlot, $result['slots']),
    'recommended' => array_map($cleanSlot, $result['recommended']),
    'fully_booked' => $result['fully_booked'],
    'high_risk'   => $highRisk,
];

echo json_encode($output);
