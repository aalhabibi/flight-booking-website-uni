<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

class Auth {
    
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            http_response_code(RESPONSE_UNAUTHORIZED);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required'
            ]);
            exit;
        }
    }
    
    public static function requireUserType($type) {
        self::requireLogin();
        if ($_SESSION['user_type'] !== $type) {
            http_response_code(RESPONSE_FORBIDDEN);
            echo json_encode([
                'success' => false,
                'message' => 'Access denied. Insufficient permissions.'
            ]);
            exit;
        }
    }
    
    public static function login($userId, $userType, $email, $name) {
        self::startSession();
        
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['email'] = $email;
        $_SESSION['name'] = $name;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Update last login in database
        try {
            $db = getDB();
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
    }
    
    public static function logout() {
        self::startSession();
        
        // Clear all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
    }
    
    public static function getUserId() {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getUserType() {
        self::startSession();
        return $_SESSION['user_type'] ?? null;
    }
    
    public static function getEmail() {
        self::startSession();
        return $_SESSION['email'] ?? null;
    }
    
    public static function getName() {
        self::startSession();
        return $_SESSION['name'] ?? null;
    }
    
    public static function checkSessionTimeout() {
        self::startSession();
        
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            
            if ($inactive > SESSION_LIFETIME) {
                self::logout();
                return false;
            }
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}