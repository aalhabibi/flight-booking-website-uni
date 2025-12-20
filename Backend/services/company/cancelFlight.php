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
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../config/constants.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', null, 405);
}

try {
    Auth::requireLogin();
    Auth::requireUserType(USER_TYPE_COMPANY);
    
    $data = getRequestData();
    
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
    $db->beginTransaction();
    
    try {
        // Get flight details
        $stmt = $db->prepare("SELECT company_id, status, fees FROM flights WHERE id = ?");
        $stmt->execute([$flightId]);
        $flight = $stmt->fetch();
        
        if (!$flight) {
            jsonResponse(false, 'Flight not found', null, RESPONSE_NOT_FOUND);
        }
        
        if ($flight['status'] === FLIGHT_STATUS_CANCELLED) {
            jsonResponse(false, 'Flight is already cancelled', null, RESPONSE_BAD_REQUEST);
        }
        
        // Get all confirmed bookings for this flight
        $stmt = $db->prepare("
            SELECT b.id, b.passenger_id, b.amount_paid, b.payment_method
            FROM bookings b
            WHERE b.flight_id = ? AND b.booking_status = 'confirmed'
        ");
        $stmt->execute([$flightId]);
        $bookings = $stmt->fetchAll();
        
        $refundedCount = 0;
        
        // Refund each passenger
        foreach ($bookings as $booking) {
            // Only refund if paid from account
            if ($booking['payment_method'] === PAYMENT_ACCOUNT) {
                // Add money back to passenger account
                if (updateBalance($booking['passenger_id'], $booking['amount_paid'], 'add')) {
                    // Subtract money from company account
                    updateBalance($flight['company_id'], $booking['amount_paid'], 'subtract');
                    
                    // Record refund transaction
                    recordTransaction(
                        $booking['passenger_id'],
                        $booking['id'],
                        'refund',
                        $booking['amount_paid'],
                        "Refund for cancelled flight ID: {$flightId}"
                    );
                    $refundedCount++;
                }
            }
            
            // Update booking status to cancelled
            $stmt = $db->prepare("
                UPDATE bookings 
                SET booking_status = 'cancelled', cancellation_date = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$booking['id']]);
        }
        
        // Update flight status to cancelled
        $stmt = $db->prepare("
            UPDATE flights 
            SET status = 'cancelled', updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$flightId]);
        
        // Update passenger count
        $stmt = $db->prepare("
            UPDATE flights 
            SET registered_passengers = 0
            WHERE id = ?
        ");
        $stmt->execute([$flightId]);
        
        $db->commit();
        
        jsonResponse(true, 'Flight cancelled successfully', [
            'flight_id' => $flightId,
            'bookings_cancelled' => count($bookings),
            'refunds_processed' => $refundedCount
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Cancel flight error: " . $e->getMessage());
    jsonResponse(false, 'Failed to cancel flight: ' . $e->getMessage(), null, RESPONSE_SERVER_ERROR);
}