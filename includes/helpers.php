<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

// JSON Response Helper
function jsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

// Get request data (JSON or POST)
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
    
    return $_POST;
}

// File Upload Helper
function uploadFile($file, $directory, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    $validation = Validator::validateFile($file, $allowedTypes);
    
    if (!$validation['valid']) {
        return [
            'success' => false,
            'message' => implode(', ', $validation['errors'])
        ];
    }
    
    $uploadPath = UPLOAD_DIR . $directory;
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadPath . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $directory . '/' . $filename
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to upload file'
    ];
}

// Check balance and update
function checkBalance($userId, $amount) {
    $db = getDB();
    $stmt = $db->prepare("SELECT account_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $balance = $stmt->fetchColumn();
    
    return $balance >= $amount;
}

function updateBalance($userId, $amount, $operation = 'add') {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT account_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentBalance = $stmt->fetchColumn();
    
    if ($operation === 'add') {
        $newBalance = $currentBalance + $amount;
    } else {
        $newBalance = $currentBalance - $amount;
        if ($newBalance < 0) {
            return false;
        }
    }
    
    $stmt = $db->prepare("UPDATE users SET account_balance = ? WHERE id = ?");
    return $stmt->execute([$newBalance, $userId]);
}

function recordTransaction($userId, $bookingId, $type, $amount, $description = '') {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT account_balance FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $balanceBefore = $stmt->fetchColumn();
    
    if (in_array($type, ['deposit', 'refund'])) {
        $balanceAfter = $balanceBefore + $amount;
    } else {
        $balanceAfter = $balanceBefore - $amount;
    }
    
    $stmt = $db->prepare("
        INSERT INTO transactions (user_id, booking_id, transaction_type, amount, 
                                balance_before, balance_after, description)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $bookingId,
        $type,
        $amount,
        $balanceBefore,
        $balanceAfter,
        $description
    ]);
}

function getUserInfo($userId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.*, 
               c.bio, c.address, c.location, c.logo_path,
               p.photo_path, p.passport_img_path
        FROM users u
        LEFT JOIN companies c ON u.id = c.user_id
        LEFT JOIN passengers p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function checkOwnership($userId, $resourceId, $resourceType) {
    $db = getDB();
    
    if ($resourceType === 'flight') {
        $stmt = $db->prepare("SELECT company_id FROM flights WHERE id = ?");
        $stmt->execute([$resourceId]);
        $ownerId = $stmt->fetchColumn();
        return $ownerId == $userId;
    }
    
    return false;
}