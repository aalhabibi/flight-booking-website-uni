<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
    
    // Validate each itinerary item
    foreach ($data['itinerary'] as $index => $stop) {
        $itineraryRules = [
            'city' => 'required|min:2|max:255',
            'start_datetime' => 'required|datetime',
            'end_datetime' => 'required|datetime'
        ];
        
        if (!$validator->validate($stop, $itineraryRules)) {
            jsonResponse(false, "Itinerary item {$index} validation failed", [
                'errors' => $validator->getErrors()
            ], RESPONSE_BAD_REQUEST);
        }
        
        // Validate datetime range
        if (!validateDatetimeRange($stop['start_datetime'], $stop['end_datetime'])) {
            jsonResponse(false, "Invalid datetime range for itinerary item {$index}", null, RESPONSE_BAD_REQUEST);
        }
        
        // Validate sequence (each stop should start after previous ends)
        if ($index > 0) {
            $prevStop = $data['itinerary'][$index - 1];
            if (strtotime($stop['start_datetime']) < strtotime($prevStop['end_datetime'])) {
                jsonResponse(false, "Itinerary item {$index} starts before previous stop ends", null, RESPONSE_BAD_REQUEST);
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
        
        foreach ($data['itinerary'] as $index => $stop) {
            $city = Validator::sanitize($stop['city']);
            $startDatetime = $stop['start_datetime'];
            $endDatetime = $stop['end_datetime'];
            
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