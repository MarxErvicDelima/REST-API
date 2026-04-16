<?php
// /adet/api/bus_routes.php
// API for managing bus-route associations

require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Get all bus routes with bus and route info
        if(isset($_GET['bus_id'])) {
            // Get routes for a specific bus
            $bus_id = (int)$_GET['bus_id'];
            $sql = "SELECT br.id, br.bus_id, br.route_id, br.capacity, 
                           b.bus_number, b.bus_type, 
                           r.origin, r.destination, r.distance_km
                    FROM bus_routes br
                    JOIN buses b ON br.bus_id = b.id
                    JOIN routes r ON br.route_id = r.id
                    WHERE br.bus_id = :bus_id AND br.is_active = TRUE";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['bus_id' => $bus_id]);
            sendJsonResponse(200, ["status" => "success", "data" => $stmt->fetchAll()]);
        } else {
            // Get all active bus routes
            $sql = "SELECT br.id, br.bus_id, br.route_id, br.capacity, br.created_at,
                           b.bus_number, b.bus_type, b.capacity as bus_capacity,
                           r.origin, r.destination, r.distance_km
                    FROM bus_routes br
                    JOIN buses b ON br.bus_id = b.id
                    JOIN routes r ON br.route_id = r.id
                    WHERE br.is_active = TRUE
                    ORDER BY b.bus_number";
            $stmt = $pdo->query($sql);
            sendJsonResponse(200, ["status" => "success", "data" => $stmt->fetchAll()]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(!isset($data['bus_id'], $data['route_id'])) {
            sendJsonResponse(400, ["error" => "Missing required parameters: bus_id, route_id"]);
        }

        // Verify bus exists
        $busStmt = $pdo->prepare("SELECT capacity FROM buses WHERE id = ?");
        $busStmt->execute([$data['bus_id']]);
        $bus = $busStmt->fetch();
        if(!$bus) {
            sendJsonResponse(404, ["error" => "Bus not found"]);
        }

        // Verify route exists
        $routeStmt = $pdo->prepare("SELECT id FROM routes WHERE id = ?");
        $routeStmt->execute([$data['route_id']]);
        $route = $routeStmt->fetch();
        if(!$route) {
            sendJsonResponse(404, ["error" => "Route not found"]);
        }

        // Insert or update bus-route association
        $capacity = $data['capacity'] ?? $bus['capacity'];
        try {
            $stmt = $pdo->prepare("INSERT INTO bus_routes (bus_id, route_id, capacity) VALUES (?, ?, ?) 
                                  ON DUPLICATE KEY UPDATE capacity = ?, is_active = TRUE");
            $stmt->execute([$data['bus_id'], $data['route_id'], $capacity, $capacity]);
            sendJsonResponse(201, ["status" => "success", "id" => $pdo->lastInsertId()]);
        } catch(PDOException $e) {
            sendJsonResponse(400, ["error" => "This bus is already assigned to this route"]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if(!$id) sendJsonResponse(400, ["error" => "Missing ID"]);
        
        // Soft delete - mark as inactive
        $stmt = $pdo->prepare("UPDATE bus_routes SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$id]);
        sendJsonResponse(200, ["status" => "success"]);
        break;

    default:
        sendJsonResponse(405, ["error" => "Method not allowed"]);
}
?>
