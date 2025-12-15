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
    $userId = Auth::getUserId();
    $userType = Auth::getUserType();
    
    // Validate basic fields
    $validator = new Validator();
    $rules = [
        'name' => 'min:2|max:255',
        'tel' => 'phone'
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', [
            'errors' => $validator->getErrors()
        ], RESPONSE_BAD_REQUEST);
    }
    
    $db = getDB();
    $db->beginTransaction();
    
    try {
        $updates = [];
        $params = [];
        
        // Update basic user fields
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = Validator::sanitize($data['name']);
        }
        
        if (isset($data['tel'])) {
            $updates[] = "tel = ?";
            $params[] = Validator::sanitize($data['tel']);
        }
        
        // Update password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                jsonResponse(false, 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters', null, RESPONSE_BAD_REQUEST);
            }
            $updates[] = "password_hash = ?";
            $params[] = Auth::hashPassword($data['password']);
        }
        
        // Update account balance (passengers only) - ADD to existing balance
        if ($userType === USER_TYPE_PASSENGER && isset($data['account_balance'])) {
            if (!is_numeric($data['account_balance']) || $data['account_balance'] < 0) {
                jsonResponse(false, 'Account balance must be a positive number', null, RESPONSE_BAD_REQUEST);
            }
            $updates[] = "account_balance = account_balance + ?";
            $params[] = floatval($data['account_balance']);
        }
        
        // Update users table
        if (!empty($updates)) {
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Update type-specific fields
        if ($userType === USER_TYPE_COMPANY) {
            $companyUpdates = [];
            $companyParams = [];
            
            if (isset($data['bio'])) {
                $companyUpdates[] = "bio = ?";
                $companyParams[] = Validator::sanitize($data['bio']);
            }
            
            if (isset($data['address'])) {
                $companyUpdates[] = "address = ?";
                $companyParams[] = Validator::sanitize($data['address']);
            }
            
            if (isset($data['location'])) {
                $companyUpdates[] = "location = ?";
                $companyParams[] = Validator::sanitize($data['location']);
            }
            
            // Handle logo upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['logo'], 'logos', ALLOWED_IMAGE_TYPES);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                
                // Delete old logo
                $stmt = $db->prepare("SELECT logo_path FROM companies WHERE user_id = ?");
                $stmt->execute([$userId]);
                $oldLogo = $stmt->fetchColumn();
                if ($oldLogo) {
                    deleteFile($oldLogo);
                }
                
                $companyUpdates[] = "logo_path = ?";
                $companyParams[] = $uploadResult['path'];
            }
            
            if (!empty($companyUpdates)) {
                $companyParams[] = $userId;
                $sql = "UPDATE companies SET " . implode(', ', $companyUpdates) . " WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($companyParams);
            }
            
        } else {
            // Passenger
            $passengerUpdates = [];
            $passengerParams = [];
            
            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['photo'], 'photos', ALLOWED_IMAGE_TYPES);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                
                // Delete old photo
                $stmt = $db->prepare("SELECT photo_path FROM passengers WHERE user_id = ?");
                $stmt->execute([$userId]);
                $oldPhoto = $stmt->fetchColumn();
                if ($oldPhoto) {
                    deleteFile($oldPhoto);
                }
                
                $passengerUpdates[] = "photo_path = ?";
                $passengerParams[] = $uploadResult['path'];
            }
            
            // Handle passport upload
            if (isset($_FILES['passport_img']) && $_FILES['passport_img']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = uploadFile($_FILES['passport_img'], 'passports', ALLOWED_PASSPORT_TYPES);
                if (!$uploadResult['success']) {
                    throw new Exception($uploadResult['message']);
                }
                
                // Delete old passport
                $stmt = $db->prepare("SELECT passport_img_path FROM passengers WHERE user_id = ?");
                $stmt->execute([$userId]);
                $oldPassport = $stmt->fetchColumn();
                if ($oldPassport) {
                    deleteFile($oldPassport);
                }
                
                $passengerUpdates[] = "passport_img_path = ?";
                $passengerParams[] = $uploadResult['path'];
            }
            
            if (!empty($passengerUpdates)) {
                $passengerParams[] = $userId;
                $sql = "UPDATE passengers SET " . implode(', ', $passengerUpdates) . " WHERE user_id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute($passengerParams);
            }
        }
        
        $db->commit();
        
        // Get updated user info
        $userInfo = getUserInfo($userId);
        
        jsonResponse(true, 'Profile updated successfully', $userInfo);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Update profile error: " . $e->getMessage());
    jsonResponse(false, 'Failed to update profile: ' . $e->getMessage(), null, RESPONSE_SERVER_ERROR);
}