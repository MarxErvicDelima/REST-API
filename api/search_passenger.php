<?php
require 'db.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($query)) {
    sendJsonResponse(400, ['status' => 'error', 'error' => 'Search query is required']);
}

try {
    // Search for bookings by trip code only
    $sql = "
        SELECT 
            t.id as ticket_id,
            p.id as passenger_id,
            p.name as passenger_name,
            p.email as passenger_email,
            p.phone as passenger_phone,
            r.origin,
            r.destination,
            b.bus_number,
            b.bus_type,
            s.departure_time,
            s.arrival_time,
            s.fare,
            t.seat_number,
            t.trip_code,
            t.booking_time
        FROM tickets t
        JOIN passengers p ON t.passenger_id = p.id
        JOIN schedules s ON t.schedule_id = s.id
        JOIN bus_routes br ON s.bus_route_id = br.id
        JOIN buses b ON br.bus_id = b.id
        JOIN routes r ON br.route_id = r.id
        WHERE t.trip_code = :trip_code
        ORDER BY t.booking_time DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['trip_code' => $query]);
    $bookings = $stmt->fetchAll();
    
    if (count($bookings) === 0) {
        sendJsonResponse(404, ['status' => 'error', 'error' => 'No booking found for this trip code']);
    } else {
        sendJsonResponse(200, [
            'status' => 'success',
            'data' => $bookings
        ]);
    }
} catch (Exception $e) {
    sendJsonResponse(500, ['status' => 'error', 'error' => $e->getMessage()]);
}
?>
