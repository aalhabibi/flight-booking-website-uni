<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    Auth::requireUserType(USER_TYPE_COMPANY);
    
    $data = getRequestData();
    
    // Validate flight data
    $validator = new Validator();
    $rules = [
        'flight_name' => 'required|min:3|max:255',
        'flight_code' => 'required|min:3|max:50|unique:flights,flight_code',
        'max_passengers' => 'required|integer|positive',
        'fees' => 'required|numeric|positive'
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', [
            'errors' => $validator->getErrors()
        ], RESPONSE_BAD_REQUEST);
    }
    
    // Validate itinerary
    if (!isset($data['itinerary']) || !is_array($data['itinerary']) || empty($data['itinerary'])) {
        jsonResponse(false, 'Itinerary is required and must contain at least one city', null, RESPONSE_BAD_REQUEST);
    }
    
    if (count($data['itinerary']) < 2) {
        jsonResponse(false, 'Itinerary must contain at least 2 stops (departure and arrival)', null, RESPONSE_BAD_REQUEST);
    }
    
    $totalStops = count($data['itinerary']);
    
    // Validate each itinerary item
    foreach ($data['itinerary'] as $index => $stop) {
        $isFirstStop = ($index === 0);
        $isLastStop = ($index === $totalStops - 1);
        $isLayover = !$isFirstStop && !$isLastStop;
        
        // Base validation - city is always required
        $itineraryRules = [
            'city' => 'required|min:2|max:255'
        ];
        
        // Conditional datetime validation based on position
        if ($isFirstStop) {
            // First stop (Departure) - only end_datetime (departure time) required
            $itineraryRules['end_datetime'] = 'required|datetime';
        } else if ($isLastStop) {
            // Last stop (Arrival) - only start_datetime (arrival time) required
            $itineraryRules['start_datetime'] = 'required|datetime';
        } else {
            
            // Layover stops - both times required
            $itineraryRules['start_datetime'] = 'required|datetime';
            $itineraryRules['end_datetime'] = 'required|datetime';
        }
        
        if (!$validator->validate($stop, $itineraryRules)) {
            $stopLabel = $isFirstStop ? 'Departure city' : ($isLastStop ? 'Arrival city' : "Layover stop " . $index);
            jsonResponse(false, "{$stopLabel} validation failed", [
                'errors' => $validator->getErrors()
            ], RESPONSE_BAD_REQUEST);
        }
        
        // For layover stops, validate that arrival is before departure
        if ($isLayover) {
            if (!Validator::validateDatetimeRange($stop['start_datetime'], $stop['end_datetime'])) {
                jsonResponse(false, "Invalid datetime range for layover stop {$index} - departure must be after arrival", null, RESPONSE_BAD_REQUEST);
            }
        }
        
        // Validate sequence (each stop should start after previous ends)
        if ($index > 0) {
            $prevStop = $data['itinerary'][$index - 1];
            $prevDeparture = $prevStop['end_datetime'] ?? null;
            $currArrival = $stop['start_datetime'] ?? null;
            
            if ($prevDeparture && $currArrival) {
                if (strtotime($currArrival) < strtotime($prevDeparture)) {
                    $stopLabel = $isLastStop ? 'Arrival city' : "Layover stop " . $index;
                    jsonResponse(false, "{$stopLabel} arrival time must be after previous departure time", null, RESPONSE_BAD_REQUEST);
                }
            }
        }
    }
    
    $companyId = Auth::getUserId();
    $flightName = Validator::sanitize($data['flight_name']);
    $flightCode = strtoupper(Validator::sanitize($data['flight_code']));
    $maxPassengers = intval($data['max_passengers']);
    $fees = floatval($data['fees']);
    
    $db = getDB();
    $db->beginTransaction();
    
    try {
        // Insert flight
        $stmt = $db->prepare("
            INSERT INTO flights (company_id, flight_name, flight_code, max_passengers, fees, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $companyId,
            $flightName,
            $flightCode,
            $maxPassengers,
            $fees,
            FLIGHT_STATUS_PENDING
        ]);
        
        $flightId = $db->lastInsertId();
        
        // Insert itinerary
        $stmt = $db->prepare("
            INSERT INTO flight_itinerary (flight_id, city, sequence_order, start_datetime, end_datetime)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $totalStopsForInsert = count($data['itinerary']);
        
        foreach ($data['itinerary'] as $index => $stop) {
            $city = Validator::sanitize($stop['city']);
            $isFirstStop = ($index === 0);
            $isLastStop = ($index === $totalStopsForInsert - 1);
            
            $startDatetime = $stop['start_datetime'] ?? null;
            $endDatetime = $stop['end_datetime'] ?? null;
            
            // This is for storage only - validation already happened above
            if ($isFirstStop && $startDatetime === null) {
                $startDatetime = $endDatetime; // Use departure time for both
            }
            if ($isLastStop && $endDatetime === null) {
                $endDatetime = $startDatetime; // Use arrival time for both
            }
            
            $stmt->execute([
                $flightId,
                $city,
                $index + 1,
                $startDatetime,
                $endDatetime
            ]);
        }
        
        $db->commit();
        
        jsonResponse(true, 'Flight added successfully', [
            'flight_id' => $flightId,
            'flight_code' => $flightCode
        ], RESPONSE_CREATED);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Add flight error: " . $e->getMessage());
    jsonResponse(false, 'Failed to add flight: ' . $e->getMessage(), null, RESPONSE_SERVER_ERROR);
}