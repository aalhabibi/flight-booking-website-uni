<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    $data = getRequestData();
    
    // Validate input
    $validator = new Validator();
    $rules = [
        'email' => 'required|email',
        'password' => 'required'
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', [
            'errors' => $validator->getErrors()
        ], RESPONSE_BAD_REQUEST);
    }
    
    $email = Validator::sanitize($data['email']);
    $password = $data['password'];
    
    $db = getDB();
    
    // Get user by email
    $stmt = $db->prepare("
        SELECT id, user_type, email, username, password_hash, name, is_active
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(false, 'Invalid email or password', null, RESPONSE_UNAUTHORIZED);
    }
    
    // Check if account is active
    if (!$user['is_active']) {
        jsonResponse(false, 'Account is deactivated', null, RESPONSE_FORBIDDEN);
    }
    
    // Verify password
    if (!Auth::verifyPassword($password, $user['password_hash'])) {
        jsonResponse(false, 'Invalid email or password', null, RESPONSE_UNAUTHORIZED);
    }
    
    // Login user
    Auth::login($user['id'], $user['user_type'], $user['email'], $user['name']);
    
    // Get additional user info
    $userInfo = getUserInfo($user['id']);
    
    // Prepare response data based on user type
    $responseData = [
        'user_id' => $user['id'],
        'user_type' => $user['user_type'],
        'email' => $user['email'],
        'username' => $user['username'],
        'name' => $user['name'],
        'tel' => $userInfo['tel'],
        'account_balance' => $userInfo['account_balance']
    ];
    
    if ($user['user_type'] === USER_TYPE_COMPANY) {
        $responseData['bio'] = $userInfo['bio'];
        $responseData['address'] = $userInfo['address'];
        $responseData['location'] = $userInfo['location'];
        $responseData['logo_path'] = $userInfo['logo_path'];
    } else {
        $responseData['photo_path'] = $userInfo['photo_path'];
        $responseData['passport_img_path'] = $userInfo['passport_img_path'];
    }
    
    jsonResponse(true, 'Login successful', $responseData);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    jsonResponse(false, 'An error occurred during login', null, RESPONSE_SERVER_ERROR);
}