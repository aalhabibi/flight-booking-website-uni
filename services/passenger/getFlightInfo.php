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
    Auth::requireUserType(USER_TYPE_PASSENGER);
    
    if (!isset($_GET['flight_id'])) {
        jsonResponse(false, 'Flight ID is required', null, RESPONSE_BAD_REQUEST);
    }
    
    $flightId = intval($_GET['flight_id']);
    $passengerId = Auth::getUserId();
    
    $db = getDB();
    
    // Get flight details with company info
    $stmt = $db->prepare("
        SELECT 
            f.*,
            u.id as company_id,
            u.name as company_name,
            u.email as company_email,
            u.tel as company_tel,
            c.bio as company_bio,
            c.address as company_address,
            c.location as company_location,
            c.logo_path as company_logo,
            (f.max_passengers - f.registered_passengers) as available_seats
        FROM flights f
        JOIN users u ON f.company_id = u.id
        JOIN companies c ON u.id = c.user_id
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
    
    // Format itinerary
    $cities = array_column($flight['itinerary'], 'city');
    $flight['itinerary_string'] = implode(' → ', $cities);
    
    if (!empty($flight['itinerary'])) {
        $flight['departure_city'] = $cities[0];
        $flight['arrival_city'] = $cities[count($cities) - 1];
        $flight['departure_time'] = $flight['itinerary'][0]['start_datetime'];
        $flight['arrival_time'] = $flight['itinerary'][count($flight['itinerary']) - 1]['end_datetime'];
        
        // Calculate duration
        $start = strtotime($flight['departure_time']);
        $end = strtotime($flight['arrival_time']);
        $duration = $end - $start;
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $flight['duration'] = "{$hours}h {$minutes}m";
    }
    
    // Check if passenger already booked
    $stmt = $db->prepare("
        SELECT id, booking_status, payment_method, amount_paid, booking_date
        FROM bookings 
        WHERE flight_id = ? AND passenger_id = ?
    ");
    $stmt->execute([$flightId, $passengerId]);
    $booking = $stmt->fetch();
    
    $flight['already_booked'] = $booking ? true : false;
    $flight['booking_info'] = $booking;
    
    // Get passenger count
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_bookings
        FROM bookings
        WHERE flight_id = ? AND booking_status = 'confirmed'
    ");
    $stmt->execute([$flightId]);
    $bookingStats = $stmt->fetch();
    
    $flight['total_confirmed_passengers'] = $bookingStats['total_bookings'];
    $flight['is_full'] = $flight['available_seats'] <= 0;
    $flight['is_available'] = $flight['status'] === FLIGHT_STATUS_PENDING && !$flight['is_full'];
    
    // Get passenger's account balance for payment check
    $stmt = $db->prepare("SELECT account_balance FROM users WHERE id = ?");
    $stmt->execute([$passengerId]);
    $balance = $stmt->fetchColumn();
    $flight['can_afford'] = $balance >= $flight['fees'];
    $flight['passenger_balance'] = $balance;
    
    jsonResponse(true, 'Flight info retrieved successfully', $flight);
    
} catch (Exception $e) {
    error_log("Get flight info error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve flight info', null, RESPONSE_SERVER_ERROR);
}