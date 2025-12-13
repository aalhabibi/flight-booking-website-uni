<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/validation.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    Auth::requireUserType(USER_TYPE_COMPANY);
    
    $data = getRequestData();
    
    // Validate flight ID
    if (!isset($data['flight_id'])) {
        jsonResponse(false, 'Flight ID is required', null, RESPONSE_BAD_REQUEST);
    }
    
    $flightId = intval($data['flight_id']);
    $companyId = Auth::getUserId();
    
    // Verify ownership
    if (!checkOwnership($companyId, $flightId, 'flight')) {
        jsonResponse(false, 'Access denied', null, RESPONSE_FORBIDDEN);
    }
    
    $db = getDB();
    
    // Get current flight status
    $stmt = $db->prepare("SELECT status, registered_passengers FROM flights WHERE id = ?");
    $stmt->execute([$flightId]);
    $currentFlight = $stmt->fetch();
    
    if (!$currentFlight) {
        jsonResponse(false, 'Flight not found', null, RESPONSE_NOT_FOUND);
    }
    
    // Don't allow editing completed or cancelled flights
    if ($currentFlight['status'] !== FLIGHT_STATUS_PENDING) {
        jsonResponse(false, 'Cannot edit completed or cancelled flights', null, RESPONSE_BAD_REQUEST);
    }
    
    // Validate update data
    $validator = new Validator();
    $rules = [
        'flight_name' => 'min:3|max:255',
        'max_passengers' => 'integer|positive',
        'fees' => 'numeric|positive'
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', [
            'errors' => $validator->getErrors()
        ], RESPONSE_BAD_REQUEST);
    }
    
    $db->beginTransaction();
    
    try {
        $updates = [];
        $params = [];
        
        // Update flight basic info
        if (isset($data['flight_name'])) {
            $updates[] = "flight_name = ?";
            $params[] = Validator::sanitize($data['flight_name']);
        }
        
        if (isset($data['max_passengers'])) {
            $newMax = intval($data['max_passengers']);
            // Don't allow reducing below current registered passengers
            if ($newMax < $currentFlight['registered_passengers']) {
                jsonResponse(false, 'Cannot reduce max passengers below current registrations', null, RESPONSE_BAD_REQUEST);
            }
            $updates[] = "max_passengers = ?";
            $params[] = $newMax;
        }
        
        if (isset($data['fees'])) {
            $updates[] = "fees = ?";
            $params[] = floatval($data['fees']);
        }
        
        // Update flights table
        if (!empty($updates)) {
            $updates[] = "updated_at = NOW()";
            $params[] = $flightId;
            $sql = "UPDATE flights SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
        
        // Update itinerary if provided
        if (isset($data['itinerary']) && is_array($data['itinerary']) && !empty($data['itinerary'])) {
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
                
                // Validate sequence
                if ($index > 0) {
                    $prevStop = $data['itinerary'][$index - 1];
                    if (strtotime($stop['start_datetime']) < strtotime($prevStop['end_datetime'])) {
                        jsonResponse(false, "Itinerary item {$index} starts before previous stop ends", null, RESPONSE_BAD_REQUEST);
                    }
                }
            }
            
            // Delete old itinerary
            $stmt = $db->prepare("DELETE FROM flight_itinerary WHERE flight_id = ?");
            $stmt->execute([$flightId]);
            
            // Insert new itinerary
            $stmt = $db->prepare("
                INSERT INTO flight_itinerary (flight_id, city, sequence_order, start_datetime, end_datetime)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            foreach ($data['itinerary'] as $index => $stop) {
                $city = Validator::sanitize($stop['city']);
                $stmt->execute([
                    $flightId,
                    $city,
                    $index + 1,
                    $stop['start_datetime'],
                    $stop['end_datetime']
                ]);
            }
        }
        
        $db->commit();
        
        // Get updated flight details
        $stmt = $db->prepare("SELECT * FROM flights WHERE id = ?");
        $stmt->execute([$flightId]);
        $updatedFlight = $stmt->fetch();
        
        // Get updated itinerary
        $stmt = $db->prepare("
            SELECT city, sequence_order, start_datetime, end_datetime
            FROM flight_itinerary
            WHERE flight_id = ?
            ORDER BY sequence_order ASC
        ");
        $stmt->execute([$flightId]);
        $updatedFlight['itinerary'] = $stmt->fetchAll();
        
        jsonResponse(true, 'Flight updated successfully', $updatedFlight);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Update flight error: " . $e->getMessage());
    jsonResponse(false, 'Failed to update flight: ' . $e->getMessage(), null, RESPONSE_SERVER_ERROR);
}