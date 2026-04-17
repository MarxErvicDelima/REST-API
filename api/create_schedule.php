<?php
// /adet/api/create_schedule.php
// Create and manage schedules for bus-route assignments

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get schedules for a bus_route assignment
        $busRouteId = isset($_GET['bus_route_id']) ? $_GET['bus_route_id'] : null;
        
        if ($busRouteId) {
            $stmt = $pdo->prepare("
                SELECT 
                    s.id,
                    s.bus_route_id,
                    s.departure_time,
                    s.arrival_time,
                    s.fare,
                    br.bus_id,
                    br.route_id,
                    b.bus_number,
                    b.bus_type,
                    r.origin,
                    r.destination,
                    (SELECT COUNT(*) FROM tickets t WHERE t.schedule_id = s.id) as booked_seats,
                    br.capacity
                FROM schedules s
                JOIN bus_routes br ON s.bus_route_id = br.id
                JOIN buses b ON br.bus_id = b.id
                JOIN routes r ON br.route_id = r.id
                WHERE s.bus_route_id = ?
                ORDER BY s.departure_time ASC
            ");
            $stmt->execute([$busRouteId]);
        } else {
            // Get all schedules
            $stmt = $pdo->query("
                SELECT 
                    s.id,
                    s.bus_route_id,
                    s.departure_time,
                    s.arrival_time,
                    s.fare,
                    b.bus_number,
                    r.origin,
                    r.destination,
                    (SELECT COUNT(*) FROM tickets t WHERE t.schedule_id = s.id) as booked_seats,
                    br.capacity
                FROM schedules s
                JOIN bus_routes br ON s.bus_route_id = br.id
                JOIN buses b ON br.bus_id = b.id
                JOIN routes r ON br.route_id = r.id
                ORDER BY s.departure_time ASC
            ");
        }
        
        sendJsonResponse(200, [
            "status" => "success",
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
        break;

    case 'POST':
        // Create a new schedule
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['bus_route_id'], $data['departure_time'], $data['arrival_time'], $data['fare'])) {
            sendJsonResponse(400, ["error" => "Missing parameters: bus_route_id, departure_time, arrival_time, fare"]);
            return;
        }
        
        try {
            // Validate bus_route exists
            $checkStmt = $pdo->prepare("SELECT id FROM bus_routes WHERE id = ?");
            $checkStmt->execute([$data['bus_route_id']]);
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse(400, ["error" => "Invalid bus_route_id"]);
                return;
            }
            
            // Validate times
            if (strtotime($data['arrival_time']) <= strtotime($data['departure_time'])) {
                sendJsonResponse(400, ["error" => "Arrival time must be after departure time"]);
                return;
            }
            
            // Create the schedule
            $stmt = $pdo->prepare("
                INSERT INTO schedules (bus_route_id, departure_time, arrival_time, fare)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['bus_route_id'],
                $data['departure_time'],
                $data['arrival_time'],
                $data['fare']
            ]);
            
            $scheduleId = $pdo->lastInsertId();
            
            sendJsonResponse(201, [
                "status" => "success",
                "message" => "Schedule created successfully",
                "id" => $scheduleId
            ]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        // Delete a schedule
        $scheduleId = $_GET['id'] ?? null;
        if (!$scheduleId) {
            sendJsonResponse(400, ["error" => "Missing schedule ID"]);
            return;
        }
        
        try {
            // Check if schedule has bookings
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE schedule_id = ?");
            $checkStmt->execute([$scheduleId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                sendJsonResponse(400, ["error" => "Cannot delete schedule with existing bookings"]);
                return;
            }
            
            // Delete the schedule
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$scheduleId]);
            
            sendJsonResponse(200, [
                "status" => "success",
                "message" => "Schedule deleted successfully"
            ]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
        }
        break;
        
    default:
        sendJsonResponse(405, ["error" => "Method not allowed"]);
}
?>
