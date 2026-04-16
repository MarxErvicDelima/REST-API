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
        
        sendJsonResponse([
            "status" => "success",
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ], 200);
        break;

    case 'POST':
        // Create a new schedule
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['bus_route_id'], $data['departure_time'], $data['arrival_time'], $data['fare'])) {
            sendJsonResponse(["error" => "Missing parameters: bus_route_id, departure_time, arrival_time, fare"], 400);
            return;
        }
        
        try {
            // Validate bus_route exists
            $checkStmt = $pdo->prepare("SELECT id FROM bus_routes WHERE id = ?");
            $checkStmt->execute([$data['bus_route_id']]);
            
            if ($checkStmt->rowCount() === 0) {
                sendJsonResponse(["error" => "Invalid bus_route_id"], 400);
                return;
            }
            
            // Validate times
            if (strtotime($data['arrival_time']) <= strtotime($data['departure_time'])) {
                sendJsonResponse(["error" => "Arrival time must be after departure time"], 400);
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
            
            sendJsonResponse([
                "status" => "success",
                "message" => "Schedule created successfully",
                "id" => $scheduleId
            ], 201);
        } catch (PDOException $e) {
            sendJsonResponse(["error" => "Database error: " . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        // Delete a schedule
        $scheduleId = $_GET['id'] ?? null;
        if (!$scheduleId) {
            sendJsonResponse(["error" => "Missing schedule ID"], 400);
            return;
        }
        
        try {
            // Check if schedule has bookings
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM tickets WHERE schedule_id = ?");
            $checkStmt->execute([$scheduleId]);
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                sendJsonResponse(["error" => "Cannot delete schedule with existing bookings"], 400);
                return;
            }
            
            // Delete the schedule
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$scheduleId]);
            
            sendJsonResponse([
                "status" => "success",
                "message" => "Schedule deleted successfully"
            ], 200);
        } catch (PDOException $e) {
            sendJsonResponse(["error" => "Database error: " . $e->getMessage()], 500);
        }
        break;
        
    default:
        sendJsonResponse(["error" => "Method not allowed"], 405);
}
?>
