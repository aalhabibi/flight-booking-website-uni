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
    Auth::requireUserType(USER_TYPE_PASSENGER);
    
    $data = getRequestData();
    
    $validator = new Validator();
    $rules = [
        'flight_id' => 'required|integer|positive',
        'payment_method' => 'required|in:' . PAYMENT_ACCOUNT . ',' . PAYMENT_CASH
    ];
    
    if (!$validator->validate($data, $rules)) {
        jsonResponse(false, 'Validation failed', ['errors' => $validator->getErrors()], 400);
    }
    
    $flightId = intval($data['flight_id']);
    $paymentMethod = $data['payment_method'];
    $passengerId = Auth::getUserId();
    
    $db = getDB();
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare("
            SELECT id, company_id, flight_code, max_passengers, 
                   registered_passengers, fees, status
            FROM flights WHERE id = ?
        ");
        $stmt->execute([$flightId]);
        $flight = $stmt->fetch();
        
        if (!$flight) {
            jsonResponse(false, 'Flight not found', null, 404);
        }
        
        if ($flight['status'] !== FLIGHT_STATUS_PENDING) {
            jsonResponse(false, 'Flight is not available', null, 400);
        }
        
        if ($flight['registered_passengers'] >= $flight['max_passengers']) {
            jsonResponse(false, 'No seats available', null, 400);
        }
        
        $stmt = $db->prepare("SELECT id FROM bookings WHERE flight_id = ? AND passenger_id = ?");
        $stmt->execute([$flightId, $passengerId]);
        if ($stmt->fetch()) {
            jsonResponse(false, 'Already booked this flight', null, 409);
        }
        
        $amountPaid = $flight['fees'];
        $bookingStatus = BOOKING_STATUS_CONFIRMED;
        
        if ($paymentMethod === PAYMENT_ACCOUNT) {
            if (!checkBalance($passengerId, $amountPaid)) {
                jsonResponse(false, 'Insufficient balance', null, 400);
            }
            
            updateBalance($passengerId, $amountPaid, 'subtract');
            updateBalance($flight['company_id'], $amountPaid, 'add');
        }
        
        $stmt = $db->prepare("
            INSERT INTO bookings (flight_id, passenger_id, booking_status, payment_method, amount_paid)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$flightId, $passengerId, $bookingStatus, $paymentMethod, $amountPaid]);
        $bookingId = $db->lastInsertId();
        
        if ($paymentMethod === PAYMENT_ACCOUNT) {
            recordTransaction($passengerId, $bookingId, 'payment', $amountPaid, "Flight {$flight['flight_code']}");
            recordTransaction($flight['company_id'], $bookingId, 'deposit', $amountPaid, "Flight {$flight['flight_code']}");
        }
        
        $stmt = $db->prepare("UPDATE flights SET registered_passengers = registered_passengers + 1 WHERE id = ?");
        $stmt->execute([$flightId]);
        
        $stmt = $db->prepare("UPDATE bookings SET confirmation_date = NOW() WHERE id = ?");
        $stmt->execute([$bookingId]);
        
        $db->commit();
        
        jsonResponse(true, 'Flight booked successfully', [
            'booking_id' => $bookingId,
            'booking_status' => $bookingStatus,
            'payment_method' => $paymentMethod,
            'message' => 'Booking confirmed'
        ], 201);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Take flight error: " . $e->getMessage());
    jsonResponse(false, 'Booking failed', null, 500);
}