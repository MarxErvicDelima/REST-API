<?php
// /adet/api/assign_bus_to_route.php
// Manage bus-route assignments (junction table operations)

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all bus-route assignments
        $routeId = isset($_GET['route_id']) ? $_GET['route_id'] : null;
        
        if ($routeId) {
            // Get buses assigned to a specific route
            $stmt = $pdo->prepare("
                SELECT 
                    br.id as assignment_id,
                    br.bus_id,
                    br.route_id,
                    br.capacity,
                    br.is_active,
                    b.bus_number,
                    b.bus_type,
                    b.capacity as bus_capacity,
                    r.origin,
                    r.destination,
                    r.distance_km,
                    r.departure_time
                FROM bus_routes br
                JOIN buses b ON br.bus_id = b.id
                JOIN routes r ON br.route_id = r.id
                WHERE br.route_id = ?
                ORDER BY b.bus_number
            ");
            $stmt->execute([$routeId]);
        } else {
            // Get all assignments
            $stmt = $pdo->query("
                SELECT 
                    br.id as assignment_id,
                    br.bus_id,
                    br.route_id,
                    br.capacity,
                    br.is_active,
                    b.bus_number,
                    b.bus_type,
                    r.origin,
                    r.destination
                FROM bus_routes br
                JOIN buses b ON br.bus_id = b.id
                JOIN routes r ON br.route_id = r.id
                ORDER BY r.origin, r.destination, b.bus_number
            ");
        }
        
        sendJsonResponse([
            "status" => "success",
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ], 200);
        break;

    case 'POST':
        // Assign a bus to a route
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['bus_id'], $data['route_id'], $data['capacity'])) {
            sendJsonResponse(["error" => "Missing parameters: bus_id, route_id, capacity"], 400);
            return;
        }
        
        try {
            // Check if assignment already exists
            $checkStmt = $pdo->prepare("
                SELECT id FROM bus_routes 
                WHERE bus_id = ? AND route_id = ?
            ");
            $checkStmt->execute([$data['bus_id'], $data['route_id']]);
            
            if ($checkStmt->rowCount() > 0) {
                sendJsonResponse(["error" => "This bus is already assigned to this route"], 400);
                return;
            }
            
            // Create the assignment
            $stmt = $pdo->prepare("
                INSERT INTO bus_routes (bus_id, route_id, capacity, is_active)
                VALUES (?, ?, ?, TRUE)
            ");
            $stmt->execute([$data['bus_id'], $data['route_id'], $data['capacity']]);
            
            $assignmentId = $pdo->lastInsertId();
            
            sendJsonResponse([
                "status" => "success",
                "message" => "Bus assigned to route successfully",
                "id" => $assignmentId
            ], 201);
        } catch (PDOException $e) {
            sendJsonResponse(["error" => "Database error: " . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        // Update bus-route assignment
        $assignmentId = $_GET['id'] ?? null;
        if (!$assignmentId) {
            sendJsonResponse(["error" => "Missing assignment ID"], 400);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['capacity'])) {
                $fields[] = "capacity = ?";
                $params[] = $data['capacity'];
            }
            if (isset($data['is_active'])) {
                $fields[] = "is_active = ?";
                $params[] = $data['is_active'] ? 1 : 0;
            }
            
            if (empty($fields)) {
                sendJsonResponse(["error" => "No fields to update"], 400);
                return;
            }
            
            $params[] = $assignmentId;
            $query = "UPDATE bus_routes SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            sendJsonResponse([
                "status" => "success",
                "message" => "Assignment updated"
            ], 200);
        } catch (PDOException $e) {
            sendJsonResponse(["error" => "Database error: " . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        // Remove bus-route assignment
        $assignmentId = $_GET['id'] ?? null;
        if (!$assignmentId) {
            sendJsonResponse(["error" => "Missing assignment ID"], 400);
            return;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM bus_routes WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            sendJsonResponse([
                "status" => "success",
                "message" => "Assignment removed"
            ], 200);
        } catch (PDOException $e) {
            sendJsonResponse(["error" => "Database error: " . $e->getMessage()], 500);
        }
        break;
        
    default:
        sendJsonResponse(["error" => "Method not allowed"], 405);
}
?>
