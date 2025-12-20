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

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    $data = getRequestData();
    
    // Validate basic registration data
    $validator = new Validator();
    $rules = [
        'email' => 'required|email|unique:users,email',
        'username' => 'required|min:3|max:50|unique:users,username',
        'password' => 'required|min:' . PASSWORD_MIN_LENGTH,
        'name' => 'required|min:2|max:255',
        'tel' => 'required|phone',
        'user_type' => 'required|in:' . USER_TYPE_COMPANY . ',' . USER_TYPE_PASSENGER
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', [
            'errors' => $validator->getErrors()
        ], RESPONSE_BAD_REQUEST);
    }
    
    // Sanitize input
    $email = Validator::sanitize($data['email']);
    $username = Validator::sanitize($data['username']);
    $name = Validator::sanitize($data['name']);
    $tel = Validator::sanitize($data['tel']);
    $userType = $data['user_type'];
    $passwordHash = Auth::hashPassword($data['password']);
    
    $db = getDB();
    $db->beginTransaction();
    
    try {
        // Insert user
        $stmt = $db->prepare("
            INSERT INTO users (user_type, email, username, password_hash, name, tel, account_balance)
            VALUES (?, ?, ?, ?, ?, ?, 0.00)
        ");
        
        $stmt->execute([$userType, $email, $username, $passwordHash, $name, $tel]);
        $userId = $db->lastInsertId();
        
        // Insert type-specific data
        if ($userType === USER_TYPE_COMPANY) {
            // Validate company-specific fields
            $companyRules = [
                'bio' => 'max:1000',
                'address' => 'max:500',
                'location' => 'max:255'
            ];
            
            if (!$validator->validate($data, $companyRules)) {
                throw new Exception($validator->getFirstError());
            }
            
            $bio = Validator::sanitize($data['bio'] ?? '');
            $address = Validator::sanitize($data['address'] ?? '');
            $location = Validator::sanitize($data['location'] ?? '');
            
            // Handle logo upload
            $logoPath = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['logo'], 'logos', ALLOWED_IMAGE_TYPES);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $logoPath = $uploadResult['path'];
            }
            
            $stmt = $db->prepare("
                INSERT INTO companies (user_id, bio, address, location, logo_path)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $bio, $address, $location, $logoPath]);
            
        } else {
            // Passenger specific
            $photoPath = null;
            $passportPath = null;
            
            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['photo'], 'photos', ALLOWED_IMAGE_TYPES);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $photoPath = $uploadResult['path'];
            }
            
            // Handle passport upload
            if (isset($_FILES['passport_img']) && $_FILES['passport_img']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['passport_img'], 'passports', ALLOWED_PASSPORT_TYPES);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                $passportPath = $uploadResult['path'];
            }
            
            $stmt = $db->prepare("
                INSERT INTO passengers (user_id, photo_path, passport_img_path)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $photoPath, $passportPath]);
        }
        
        $db->commit();
        
        // Auto-login after registration
        Auth::login($userId, $userType, $email, $name);
        
        jsonResponse(true, 'Registration successful', [
            'user_id' => $userId,
            'user_type' => $userType,
            'email' => $email,
            'name' => $name
        ], RESPONSE_CREATED);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    jsonResponse(false, $e->getMessage(), null, RESPONSE_SERVER_ERROR);
}