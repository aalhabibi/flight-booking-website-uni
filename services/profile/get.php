<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    
    $userId = Auth::getUserId();
    $userInfo = getUserInfo($userId);
    
    if (!$userInfo) {
        jsonResponse(false, 'User not found', null, RESPONSE_NOT_FOUND);
    }
    
    // Remove sensitive data
    unset($userInfo['password_hash']);
    
    jsonResponse(true, 'Profile retrieved successfully', $userInfo);
    
} catch (Exception $e) {
    error_log("Get profile error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve profile', null, RESPONSE_SERVER_ERROR);
}