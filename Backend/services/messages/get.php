<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    
    $userId = Auth::getUserId();
    $db = getDB();
    
    // Check if specific conversation requested
    if (isset($_GET['with_user_id'])) {
        $withUserId = intval($_GET['with_user_id']);
        
        // Get conversation between two users
        $stmt = $db->prepare("
            SELECT 
                m.id,
                m.sender_id,
                m.receiver_id,
                m.message,
                m.sent_at
            FROM messages m
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.sent_at ASC
        ");
        
        $stmt->execute([$userId, $withUserId, $withUserId, $userId]);
        $messages = $stmt->fetchAll();
        
        jsonResponse(true, 'Conversation retrieved successfully', [
            'messages' => $messages,
            'total' => count($messages)
        ]);
        
    } else {
        // Get all conversations (list of users with last message)
        $stmt = $db->prepare("
            SELECT DISTINCT
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.name as other_user_name,
                u.user_type as other_user_type,
                (SELECT message FROM messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id)
                    OR (sender_id = other_user_id AND receiver_id = ?)
                 ORDER BY sent_at DESC LIMIT 1) as last_message,
                (SELECT sent_at FROM messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id)
                    OR (sender_id = other_user_id AND receiver_id = ?)
                 ORDER BY sent_at DESC LIMIT 1) as last_message_time
            FROM messages m
            JOIN users u ON (
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END = u.id
            )
            WHERE m.sender_id = ? OR m.receiver_id = ?
            ORDER BY last_message_time DESC
        ");
        
        $stmt->execute([
            $userId, $userId, $userId, $userId, $userId, 
            $userId, $userId, $userId
        ]);
        $conversations = $stmt->fetchAll();
        
        jsonResponse(true, 'Conversations retrieved successfully', [
            'conversations' => $conversations,
            'total_conversations' => count($conversations)
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get messages error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve messages', null, RESPONSE_SERVER_ERROR);
}