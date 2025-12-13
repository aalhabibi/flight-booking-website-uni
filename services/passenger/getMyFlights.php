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
    
    $passengerId = Auth::getUserId();
    $db = getDB();
    
    // Get all bookings for this passenger
    $stmt = $db->prepare("
        SELECT 
            b.id as booking_id,
            b.booking_status,
            b.payment_method,
            b.amount_paid,
            b.booking_date,
            b.confirmation_date,
            b.cancellation_date,
            f.id as flight_id,
            f.flight_name,
            f.flight_code,
            f.fees,
            f.status as flight_status,
            u.name as company_name,
            u.email as company_email,
            u.tel as company_tel,
            c.logo_path as company_logo
        FROM bookings b
        JOIN flights f ON b.flight_id = f.id
        JOIN users u ON f.company_id = u.id
        JOIN companies c ON u.id = c.user_id
        WHERE b.passenger_id = ?
        ORDER BY b.booking_date DESC
    ");
    
    $stmt->execute([$passengerId]);
    $bookings = $stmt->fetchAll();
    
    $currentFlights = [];
    $completedFlights = [];
    $cancelledFlights = [];
    
    foreach ($bookings as $booking) {
        // Get itinerary
        $stmt = $db->prepare("
            SELECT city, sequence_order, start_datetime, end_datetime
            FROM flight_itinerary
            WHERE flight_id = ?
            ORDER BY sequence_order ASC
        ");
        $stmt->execute([$booking['flight_id']]);
        $booking['itinerary'] = $stmt->fetchAll();
        
        // Format itinerary
        $cities = array_column($booking['itinerary'], 'city');
        $booking['itinerary_string'] = implode(' → ', $cities);
        
        if (!empty($booking['itinerary'])) {
            $booking['departure_city'] = $cities[0];
            $booking['arrival_city'] = $cities[count($cities) - 1];
            $booking['departure_time'] = $booking['itinerary'][0]['start_datetime'];
            $booking['arrival_time'] = $booking['itinerary'][count($booking['itinerary']) - 1]['end_datetime'];
            
            // Check if flight is completed (arrival time has passed)
            $isCompleted = strtotime($booking['arrival_time']) < time();
        } else {
            $isCompleted = false;
        }
        
        // Categorize flights
        if ($booking['booking_status'] === BOOKING_STATUS_CANCELLED || 
            $booking['flight_status'] === FLIGHT_STATUS_CANCELLED) {
            $cancelledFlights[] = $booking;
        } elseif ($isCompleted || $booking['flight_status'] === FLIGHT_STATUS_COMPLETED) {
            $completedFlights[] = $booking;
        } else {
            $currentFlights[] = $booking;
        }
    }
    
    jsonResponse(true, 'Flights retrieved successfully', [
        'current_flights' => $currentFlights,
        'completed_flights' => $completedFlights,
        'cancelled_flights' => $cancelledFlights,
        'totals' => [
            'current' => count($currentFlights),
            'completed' => count($completedFlights),
            'cancelled' => count($cancelledFlights),
            'all' => count($bookings)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get my flights error: " . $e->getMessage());
    jsonResponse(false, 'Failed to retrieve flights', null, RESPONSE_SERVER_ERROR);
}