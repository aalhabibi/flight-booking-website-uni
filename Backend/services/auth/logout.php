<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    Auth::logout();
    
    jsonResponse(true, 'Logout successful');
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred during logout', null, RESPONSE_SERVER_ERROR);
}