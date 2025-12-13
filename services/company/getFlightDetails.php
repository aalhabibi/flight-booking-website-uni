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
    Auth::requireUserType(USER_TYPE_COMPANY);
    
    if (!isset($_GET['flight_id'])) {
        jsonResponse(false, 'Flight ID is required', null, RESPONSE_BAD_REQUEST);
    }
    
    $flightId = intval($_GET['flight_id']);
    $companyId = Auth::getUserId();
    
    // Verify ownership
    if (!checkOwnership($companyId, $flightId, 'flight')) {
        jsonResponse(false, 'Access denied', null, RESPONSE_FORBIDDEN);
    }
    
    $db = getDB();
    
    // Get flight details
    $stmt = $db->prepare("
        SELECT 
            f.*,
            u.name as company_name,
            u.email as company_email,
            u.tel as company_tel
        FROM flights f
        JOIN users u ON f.company_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$flightId]);
    $flight = $stmt->fetch();
    
    if (!$flight) {
        jsonResponse(false, 'Flight not found', null, RESPONSE_NOT_FOUND);
    }
    
    // Get itinerary
    $stmt = $db->prepare("
        SELECT city, sequence_order, start_datetime, end_datetime
        FROM flight_itinerary
        WHERE flight_id = ?
        ORDER BY sequence_order ASC
    ");
    $stmt->execute([$flightId]);
    $flight['itinerary'] = $stmt->fetchAll();
    
    // Get pending passengers
    $stmt = $db->prepare("
        SELECT 
            b.id as booking_id,
            b.booking_status,
            b.payment_method,
            b.amount_paid,
            b.booking_date,
            u.id as passenger_id,
            u.name,
            u.email,
            u.tel,
            p.photo_path,
            p.passport_img_path
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        JOIN passengers p ON u.id = p.user_id
        WHERE b.flight_id = ? AND b.booking_status = 'pending'
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$flightId]);
    $flight['pending_passengers'] = $stmt->fetchAll();
    
    // Get registered (confirmed) passengers
    $stmt = $db->prepare("
        SELECT 
            b.id as booking_id,
            b.booking_status,
            b.payment_method,
            b.amount_paid,
            b.booking_date,
            b.confirmation_date,
            u.id as passenger_id,
            u.name,
            u.email,
            u.tel,
            p.photo_path,
            p.passport_img_path
        FROM bookings b
        JOIN users u ON b.passenger_id = u.id
        JOIN passengers p ON u.id = p.user_id
        WHERE b.flight_id = ? AND b.booking_status = 'confirmed'
        ORDER BY b.confirmation_date DESC
    ");
    $stmt->execute([$flightId]);
    $flight['registered_passengers'] = $stmt->fetchAll();
    
    // Calculate statistics
    $flight['statistics'] = [
        'total_bookings' => count($flight['pending_passengers']) + count($flight['registered_passengers']),
        'pending_count' => count($flight['pending_passengers']),
        'confirmed_count' => count($flight['registered_passengers']),
        'available_seats' => $flight['max_passengers'] - count($flight['registered_passengers']),
        'revenue' => $flight['fees'] * count($flight['registered_passengers'])
    ];
    
    jsonResponse(true, 'Flight details retrieved successfully', $flight);
    
} catch (Exception $e) {
    error_log("Get flight details error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve flight details', null, RESPONSE_SERVER_ERROR);
}