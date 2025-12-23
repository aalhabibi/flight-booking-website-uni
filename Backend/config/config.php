<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'flight_booking_uni');
define('DB_USER', 'root');
define('DB_PASS', '123456');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration

define('APP_NAME', 'Flight Booking System');
define('BASE_URL', 'http://localhost:8000/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/'); // URL path for frontend access
define('MAX_FILE_SIZE', 10485760); // 10MB

// Security
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);

// Timezone
date_default_timezone_set('UTC');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');