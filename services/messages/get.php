<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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
                m.flight_id,
                m.message,
                m.message_type,
                m.is_read,
                m.sent_at,
                sender.name as sender_name,
                sender.user_type as sender_type,
                receiver.name as receiver_name,
                receiver.user_type as receiver_type,
                f.flight_code,
                f.flight_name
            FROM messages m
            JOIN users sender ON m.sender_id = sender.id
            JOIN users receiver ON m.receiver_id = receiver.id
            LEFT JOIN flights f ON m.flight_id = f.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.sent_at ASC
        ");
        
        $stmt->execute([$userId, $withUserId, $withUserId, $userId]);
        $messages = $stmt->fetchAll();
        
        // Mark received messages as read
        $stmt = $db->prepare("
            UPDATE messages 
            SET is_read = TRUE 
            WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$userId, $withUserId]);
        
        jsonResponse(true, 'Conversation retrieved successfully', [
            'messages' => $messages,
            'total' => count($messages)
        ]);
        
    } else {
        // Get all conversations (grouped by user)
        $stmt = $db->prepare("
            SELECT DISTINCT
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id
                    ELSE m.sender_id
                END as other_user_id,
                u.name as other_user_name,
                u.user_type as other_user_type,
                u.email as other_user_email,
                (SELECT message FROM messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id)
                    OR (sender_id = other_user_id AND receiver_id = ?)
                 ORDER BY sent_at DESC LIMIT 1) as last_message,
                (SELECT sent_at FROM messages 
                 WHERE (sender_id = ? AND receiver_id = other_user_id)
                    OR (sender_id = other_user_id AND receiver_id = ?)
                 ORDER BY sent_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages 
                 WHERE receiver_id = ? AND sender_id = other_user_id AND is_read = FALSE) as unread_count
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
            $userId, $userId, $userId, $userId
        ]);
        $conversations = $stmt->fetchAll();
        
        // Get company logo or passenger photo for each conversation
        foreach ($conversations as &$conv) {
            if ($conv['other_user_type'] === USER_TYPE_COMPANY) {
                $stmt = $db->prepare("SELECT logo_path FROM companies WHERE user_id = ?");
                $stmt->execute([$conv['other_user_id']]);
                $result = $stmt->fetch();
                $conv['other_user_image'] = $result ? $result['logo_path'] : null;
            } else {
                $stmt = $db->prepare("SELECT photo_path FROM passengers WHERE user_id = ?");
                $stmt->execute([$conv['other_user_id']]);
                $result = $stmt->fetch();
                $conv['other_user_image'] = $result ? $result['photo_path'] : null;
            }
        }
        
        // Get total unread count
        $stmt = $db->prepare("
            SELECT COUNT(*) as total_unread
            FROM messages
            WHERE receiver_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$userId]);
        $unreadData = $stmt->fetch();
        
        jsonResponse(true, 'Conversations retrieved successfully', [
            'conversations' => $conversations,
            'total_conversations' => count($conversations),
            'total_unread' => $unreadData['total_unread']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Get messages error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve messages', null, RESPONSE_SERVER_ERROR);
}