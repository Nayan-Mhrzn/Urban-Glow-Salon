<?php
/**
 * Get Scored Slots API — Adaptive Slot Recommendation
 * Urban Glow Salon
 *
 * GET params: date, service_id
 * Returns JSON: { slots, recommended, fully_booked, high_risk }
 */
require_once dirname(__DIR__) . '/app/Config/config.php';
require_once dirname(__DIR__) . '/core/slot_scorer.php';

header('Content-Type: application/json');

// ── Validate session ──
$customerId = null;
if (isLoggedIn()) {
    $customerId = (int) $_SESSION['user_id'];
}

// ── Validate inputs ──
$date = $_GET['date'] ?? '';
$serviceId = (int) ($_GET['service_id'] ?? 0);
$specialistId = !empty($_GET['specialist_id']) ? (int)$_GET['specialist_id'] : null;

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

// ── Get Specialists ──
$staffStmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.profile_image 
    FROM users u 
    JOIN staff_services ss ON u.id = ss.staff_id 
    WHERE ss.service_id = ? AND u.role = 'STAFF'
");
$staffStmt->execute([$serviceId]);
$serviceStaff = $staffStmt->fetchAll();
$staffMap = [];
foreach($serviceStaff as $st) {
    if (!$specialistId || $st['id'] == $specialistId) {
        $staffMap[] = $st;
    }
}

// ── Filter slots if a specific specialist is chosen ──
// Find which slots the requested specialist is ALREADY booked for
$busySlots = [];
if ($specialistId) {
    $busyStmt = $pdo->prepare("SELECT booking_time FROM bookings WHERE booking_date = ? AND staff_id = ? AND status != 'Cancelled'");
    $busyStmt->execute([$date, $specialistId]);
    $busySlots = $busyStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Filter the raw slots arrays
    $result['slots'] = array_values(array_filter($result['slots'], function($s) use ($busySlots) {
        return !in_array($s['time'], $busySlots);
    }));
    $result['recommended'] = array_values(array_filter($result['recommended'], function($s) use ($busySlots) {
        return !in_array($s['time'], $busySlots);
    }));
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

// ── Clean internal fields and inject specialist ──
$cleanSlot = function (array $slot) use ($staffMap): array {
    $sp = !empty($staffMap) ? $staffMap[array_rand($staffMap)] : null; // assign random valid specialist
    
    return [
        'time'           => $slot['time'],
        'display'        => $slot['display'],
        'available'      => $slot['available'],
        'final_score'    => $slot['final_score'],
        'is_recommended' => $slot['is_recommended'],
        'specialist'     => $sp
    ];
};

$output = [
    'slots'       => array_map($cleanSlot, $result['slots']),
    'recommended' => array_map($cleanSlot, $result['recommended']),
    'fully_booked' => $result['fully_booked'],
    'high_risk'   => $highRisk,
];

echo json_encode($output);

