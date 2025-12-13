<?php
// User Types
define('USER_TYPE_COMPANY', 'company');
define('USER_TYPE_PASSENGER', 'passenger');

// Flight Status
define('FLIGHT_STATUS_PENDING', 'pending');
define('FLIGHT_STATUS_COMPLETED', 'completed');
define('FLIGHT_STATUS_CANCELLED', 'cancelled');

// Booking Status
define('BOOKING_STATUS_PENDING', 'pending');
define('BOOKING_STATUS_CONFIRMED', 'confirmed');
define('BOOKING_STATUS_CANCELLED', 'cancelled');

// Payment Methods
define('PAYMENT_ACCOUNT', 'account');
define('PAYMENT_CASH', 'cash');

// File Upload Types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']);
define('ALLOWED_PASSPORT_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']);

// Response Codes
define('RESPONSE_SUCCESS', 200);
define('RESPONSE_CREATED', 201);
define('RESPONSE_BAD_REQUEST', 400);
define('RESPONSE_UNAUTHORIZED', 401);
define('RESPONSE_FORBIDDEN', 403);
define('RESPONSE_NOT_FOUND', 404);
define('RESPONSE_CONFLICT', 409);
define('RESPONSE_SERVER_ERROR', 500);

// Message Types
define('MESSAGE_TYPE_FLIGHT_INQUIRY', 'flight_inquiry');
define('MESSAGE_TYPE_BOOKING', 'booking');
define('MESSAGE_TYPE_GENERAL', 'general');