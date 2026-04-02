<?php
/**
 * API - Update User Profile Picture
 */
require_once '../config/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['profile_image'])) {
    http_response_code(400);
    // If post_max_size is exceeded, $_FILES is totally empty.
    $msg = empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0 ? 'File exceeds maximum allowed upload size (post_max_size)' : 'No file upload data found';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$file = $_FILES['profile_image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errorMsg = 'Upload Error: ';
    switch ($file['error']) {
        case UPLOAD_ERR_INI_SIZE:   $errorMsg .= 'File is larger than PHP limit (upload_max_filesize)'; break;
        case UPLOAD_ERR_FORM_SIZE:  $errorMsg .= 'File exceeds form MAX_FILE_SIZE limit'; break;
        case UPLOAD_ERR_PARTIAL:    $errorMsg .= 'File was only partially uploaded'; break;
        case UPLOAD_ERR_NO_FILE:    $errorMsg .= 'No image was actually uploaded'; break;
        case UPLOAD_ERR_NO_TMP_DIR: $errorMsg .= 'Missing temporary folder in server config'; break;
        case UPLOAD_ERR_CANT_WRITE: $errorMsg .= 'Failed to write file to disk'; break;
        case UPLOAD_ERR_EXTENSION:  $errorMsg .= 'A PHP extension stopped the file upload'; break;
        default:                    $errorMsg .= 'Unknown upload error code: ' . $file['error']; break;
    }
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

$file = $_FILES['profile_image'];
$userId = $_SESSION['user_id'];

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WEBP allowed']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
$uploadDir = dirname(__DIR__) . '/images/profiles/';
$destPath = $uploadDir . $filename;

try {
    // Get old image to delete it later
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldImage = $stmt->fetchColumn();

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Update database
        $updateStmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $updateStmt->execute([$filename, $userId]);
        
        // Remove old image if it exists and wasn't a default one not prefixed by 'profile_'
        if ($oldImage && file_exists($uploadDir . $oldImage) && strpos($oldImage, 'profile_') === 0) {
            unlink($uploadDir . $oldImage);
        }

        $_SESSION['profile_image'] = $filename;

        echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully', 'filename' => $filename]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file on server']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
