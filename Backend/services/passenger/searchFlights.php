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
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    Auth::requireUserType(USER_TYPE_PASSENGER);
    
    $from = isset($_GET['from']) ? Validator::sanitize($_GET['from']) : null;
    $to = isset($_GET['to']) ? Validator::sanitize($_GET['to']) : null;
    
    $db = getDB();
    
    // Build query based on search parameters
    if ($from && $to) {
        // Ensure from and to are different cities
        if (strtolower($from) === strtolower($to)) {
            jsonResponse(false, 'Departure and arrival cities cannot be the same', null, RESPONSE_BAD_REQUEST);
        }
        
        // Search for flights where "from" city sequence is less than "to" city sequence (with partial matching)
        $query = "
            SELECT DISTINCT
                f.id,
                f.flight_name,
                f.flight_code,
                f.max_passengers,
                f.registered_passengers,
                f.fees,
                f.status,
                f.created_at,
                u.name as company_name,
                (f.max_passengers - f.registered_passengers) as available_seats
            FROM flights f
            JOIN users u ON f.company_id = u.id
            WHERE f.status = 'pending'
            AND (f.max_passengers - f.registered_passengers) > 0
            AND f.id IN (
                SELECT fi1.flight_id
                FROM flight_itinerary fi1
                JOIN flight_itinerary fi2 ON fi1.flight_id = fi2.flight_id
                WHERE LOWER(fi1.city) LIKE LOWER(CONCAT('%', ?, '%'))
                AND LOWER(fi2.city) LIKE LOWER(CONCAT('%', ?, '%'))
                AND fi1.sequence_order < fi2.sequence_order
            )
            ORDER BY f.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$from, $to]);
        
    } elseif ($from || $to) {
        if ($from) {
            // Search for flights where "from" city is NOT the last city (can depart from it)
            $query = "
                SELECT DISTINCT
                    f.id,
                    f.flight_name,
                    f.flight_code,
                    f.max_passengers,
                    f.registered_passengers,
                    f.fees,
                    f.status,
                    f.created_at,
                    u.name as company_name,
                    (f.max_passengers - f.registered_passengers) as available_seats
                FROM flights f
                JOIN users u ON f.company_id = u.id
                WHERE f.status = 'pending'
                AND (f.max_passengers - f.registered_passengers) > 0
                AND f.id IN (
                    SELECT fi.flight_id
                    FROM flight_itinerary fi
                    WHERE LOWER(fi.city) LIKE LOWER(CONCAT('%', ?, '%'))
                    AND fi.sequence_order < (
                        SELECT MAX(sequence_order) 
                        FROM flight_itinerary 
                        WHERE flight_id = fi.flight_id
                    )
                )
                ORDER BY f.created_at DESC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$from]);
        } else {
            // Search for flights where "to" city is NOT the first city (can arrive to it)
            $query = "
                SELECT DISTINCT
                    f.id,
                    f.flight_name,
                    f.flight_code,
                    f.max_passengers,
                    f.registered_passengers,
                    f.fees,
                    f.status,
                    f.created_at,
                    u.name as company_name,
                    (f.max_passengers - f.registered_passengers) as available_seats
                FROM flights f
                JOIN users u ON f.company_id = u.id
                WHERE f.status = 'pending'
                AND (f.max_passengers - f.registered_passengers) > 0
                AND f.id IN (
                    SELECT fi.flight_id
                    FROM flight_itinerary fi
                    WHERE LOWER(fi.city) LIKE LOWER(CONCAT('%', ?, '%'))
                    AND fi.sequence_order > 1
                )
                ORDER BY f.created_at DESC
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$to]);
        }
        
    } else {
        // Return all available flights
        $query = "
            SELECT 
                f.id,
                f.flight_name,
                f.flight_code,
                f.max_passengers,
                f.registered_passengers,
                f.fees,
                f.status,
                f.created_at,
                u.name as company_name,
                (f.max_passengers - f.registered_passengers) as available_seats
            FROM flights f
            JOIN users u ON f.company_id = u.id
            WHERE f.status = 'pending'
            AND (f.max_passengers - f.registered_passengers) > 0
            ORDER BY f.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
    }
    
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
        
        // Format itinerary string
        $cities = array_column($flight['itinerary'], 'city');
        $flight['itinerary_string'] = implode(' → ', $cities);
        
        // Get first and last city
        $flight['departure_city'] = $cities[0] ?? '';
        $flight['arrival_city'] = $cities[count($cities) - 1] ?? '';
        
        // Get start and end times
        if (!empty($flight['itinerary'])) {
            $flight['departure_time'] = $flight['itinerary'][0]['start_datetime'];
            $flight['arrival_time'] = $flight['itinerary'][count($flight['itinerary']) - 1]['end_datetime'];
        }
        
        // Check if passenger already booked this flight
        $passengerId = Auth::getUserId();
        $stmt = $db->prepare("
            SELECT booking_status 
            FROM bookings 
            WHERE flight_id = ? AND passenger_id = ?
        ");
        $stmt->execute([$flight['id'], $passengerId]);
        $booking = $stmt->fetch();
        
        $flight['already_booked'] = $booking ? true : false;
        $flight['booking_status'] = $booking ? $booking['booking_status'] : null;
    }
    
    jsonResponse(true, 'Flights retrieved successfully', [
        'flights' => $flights,
        'total' => count($flights),
        'search_params' => [
            'from' => $from,
            'to' => $to
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Search flights error: " . $e->getMessage());
    jsonResponse(false, 'Failed to search flights', null, RESPONSE_SERVER_ERROR);
}