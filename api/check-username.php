<?php
/**
 * Check if username exists (AJAX endpoint)
 */
require_once '../app/Config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$username = trim($_POST['username'] ?? '');

if (empty($username)) {
    echo json_encode(['error' => 'Username is required', 'exists' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$exists = $stmt->fetch() ? true : false;

echo json_encode(['exists' => $exists]);
exit;
