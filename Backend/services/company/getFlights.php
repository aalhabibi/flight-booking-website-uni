<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
    
    // Get all flights for this company (no join to avoid duplicates)
    $stmt = $db->prepare("
        SELECT 
            f.id,
            f.flight_name,
            f.flight_code,
            f.max_passengers,
            f.registered_passengers,
            f.fees,
            f.status,
            f.created_at,
            f.updated_at,
            (SELECT COUNT(*) FROM bookings WHERE flight_id = f.id AND booking_status = 'confirmed') as confirmed_bookings
        FROM flights f
        WHERE f.company_id = ?
        ORDER BY f.created_at DESC
    ");
    
    $stmt->execute([$companyId]);
    $flights = $stmt->fetchAll();

    if (empty($flights)) {
        jsonResponse(true, 'Flights retrieved successfully', [
            'flights' => [],
            'total' => 0
        ]);
    }

    // Fetch all bookings for these flights in one query
    $flightIds = array_column($flights, 'id');
    $placeholders = implode(',', array_fill(0, count($flightIds), '?'));
    $bookingsByFlight = [];

    if (!empty($flightIds)) {
        $stmt = $db->prepare("
            SELECT 
                b.id AS booking_id,
                b.flight_id,
                b.booking_status,
                b.payment_method,
                b.amount_paid,
                b.booking_date,
                b.confirmation_date,
                u.id AS passenger_id,
                u.name,
                u.email,
                u.tel
            FROM bookings b
            JOIN users u ON b.passenger_id = u.id
            WHERE b.flight_id IN ($placeholders)
            ORDER BY b.booking_date DESC
        ");
        $stmt->execute($flightIds);
        $bookings = $stmt->fetchAll();

        foreach ($bookings as $booking) {
            $flightId = $booking['flight_id'];
            if (!isset($bookingsByFlight[$flightId])) {
                $bookingsByFlight[$flightId] = [];
            }
            $bookingsByFlight[$flightId][] = $booking;
        }
    }
    
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

        // Attach bookings and stats
        $flightBookings = $bookingsByFlight[$flight['id']] ?? [];
        $flight['bookings'] = $flightBookings;

        $confirmedCount = 0;
        foreach ($flightBookings as $booking) {
            if ($booking['booking_status'] === 'confirmed') {
                $confirmedCount++;
            }
        }

        // Keep registered_passengers as a count for UI compatibility
        $flight['registered_passengers'] = $confirmedCount;
        $flight['statistics'] = [
            'total_bookings' => count($flightBookings),
            'confirmed_count' => $confirmedCount,
            'available_seats' => $flight['max_passengers'] - $confirmedCount,
            'revenue' => $flight['fees'] * $confirmedCount
        ];
    }
    
    jsonResponse(true, 'Flights retrieved successfully', [
        'flights' => $flights,
        'total' => count($flights)
    ]);
    
} catch (Exception $e) {
    error_log("Get flights error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve flights', null, RESPONSE_SERVER_ERROR);
}