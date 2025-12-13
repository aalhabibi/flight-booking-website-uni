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
    
    $companyId = Auth::getUserId();
    $db = getDB();
    
    // Get all flights for this company
    $stmt = $db->prepare("
        SELECT 
            f.id,
            f.flight_name,
            f.flight_code,
            f.max_passengers,
            f.registered_passengers,
            f.pending_passengers,
            f.fees,
            f.status,
            f.created_at,
            f.updated_at,
            (SELECT COUNT(*) FROM bookings WHERE flight_id = f.id AND booking_status = 'confirmed') as confirmed_bookings,
            (SELECT COUNT(*) FROM bookings WHERE flight_id = f.id AND booking_status = 'pending') as pending_bookings
        FROM flights f
        WHERE f.company_id = ?
        ORDER BY f.created_at DESC
    ");
    
    $stmt->execute([$companyId]);
    $flights = $stmt->fetchAll();
    
    // Get itinerary for each flight
    foreach ($flights as &$flight) {
        $stmt = $db->prepare("
            SELECT city, sequence_order, start_datetime, end_datetime
            FROM flight_itinerary
            WHERE flight_id = ?
            ORDER BY sequence_order ASC
        ");
        $stmt->execute([$flight['id']]);
        $flight['itinerary'] = $stmt->fetchAll();
        
        // Add formatted itinerary string
        $cities = array_column($flight['itinerary'], 'city');
        $flight['itinerary_string'] = implode(' → ', $cities);
    }
    
    jsonResponse(true, 'Flights retrieved successfully', [
        'flights' => $flights,
        'total' => count($flights)
    ]);
    
} catch (Exception $e) {
    error_log("Get flights error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve flights', null, RESPONSE_SERVER_ERROR);
}