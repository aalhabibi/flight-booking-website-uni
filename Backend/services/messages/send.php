<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost/flight-booking-website-uni');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    
    $data = getRequestData();
    
    // Validate input
    $validator = new Validator();
    $rules = [
        'receiver_id' => 'required|integer|positive',
        'message' => 'required|min:1|max:2000'
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', [
            'errors' => $validator->getErrors()
        ], RESPONSE_BAD_REQUEST);
    }
    
    $senderId = Auth::getUserId();
    $receiverId = intval($data['receiver_id']);
    $message = Validator::sanitize($data['message']);
    
    // Check if receiver exists
    $db = getDB();
    $stmt = $db->prepare("SELECT id, user_type FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    $receiver = $stmt->fetch();
    
    if (!$receiver) {
        jsonResponse(false, 'Receiver not found', null, RESPONSE_NOT_FOUND);
    }
    
    // Prevent self-messaging and enforce cross-type (company <-> passenger only)
    if ($receiverId === $senderId) {
        jsonResponse(false, 'You cannot message yourself', null, RESPONSE_BAD_REQUEST);
    }

    $senderType = Auth::getUserType();
    if ($senderType === $receiver['user_type']) {
        jsonResponse(false, 'Messages can only be sent between companies and passengers', null, RESPONSE_BAD_REQUEST);
    }
    
    // Insert message
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, message)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $senderId,
        $receiverId,
        $message
    ]);
    
    $messageId = $db->lastInsertId();
    
    jsonResponse(true, 'Message sent successfully', [
        'message_id' => $messageId,
        'sent_at' => date('Y-m-d H:i:s')
    ], RESPONSE_CREATED);
    
} catch (Exception $e) {
    error_log("Send message error: " . $e->getMessage());
    jsonResponse(false, 'Failed to send message', null, RESPONSE_SERVER_ERROR);
}