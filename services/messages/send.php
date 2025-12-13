<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
        'message' => 'required|min:1|max:2000',
        'message_type' => 'in:' . MESSAGE_TYPE_FLIGHT_INQUIRY . ',' . MESSAGE_TYPE_BOOKING . ',' . MESSAGE_TYPE_GENERAL
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', [
            'errors' => $validator->getErrors()
        ], RESPONSE_BAD_REQUEST);
    }
    
    $senderId = Auth::getUserId();
    $receiverId = intval($data['receiver_id']);
    $message = Validator::sanitize($data['message']);
    $messageType = $data['message_type'] ?? MESSAGE_TYPE_GENERAL;
    $flightId = isset($data['flight_id']) ? intval($data['flight_id']) : null;
    
    // Check if receiver exists
    $db = getDB();
    $stmt = $db->prepare("SELECT id, user_type FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    $receiver = $stmt->fetch();
    
    if (!$receiver) {
        jsonResponse(false, 'Receiver not found', null, RESPONSE_NOT_FOUND);
    }
    
    // Validate sender and receiver are different user types
    $senderType = Auth::getUserType();
    if ($senderType === $receiver['user_type']) {
        jsonResponse(false, 'Messages can only be sent between companies and passengers', null, RESPONSE_BAD_REQUEST);
    }
    
    // If flight_id provided, validate it exists
    if ($flightId) {
        $stmt = $db->prepare("SELECT id FROM flights WHERE id = ?");
        $stmt->execute([$flightId]);
        if (!$stmt->fetch()) {
            jsonResponse(false, 'Flight not found', null, RESPONSE_NOT_FOUND);
        }
    }
    
    // Insert message
    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, flight_id, message, message_type)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $senderId,
        $receiverId,
        $flightId,
        $message,
        $messageType
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