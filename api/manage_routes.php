<?php
// /adet/api/manage_routes.php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT id, origin, destination, distance_km, departure_time, created_at FROM routes ORDER BY origin ASC");
        sendJsonResponse(200, ["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if(!isset($data['origin'], $data['destination'], $data['distance_km'])) {
            sendJsonResponse(400, ["error" => "Missing parameters"]);
            return;
        }
        
        $departure_time = isset($data['departure_time']) && !empty($data['departure_time']) ? $data['departure_time'] : '06:00:00';
        
        try {
            $stmt = $pdo->prepare("INSERT INTO routes (origin, destination, distance_km, departure_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['origin'], $data['destination'], $data['distance_km'], $departure_time]);
            sendJsonResponse(201, ["status" => "success", "id" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                sendJsonResponse(400, ["error" => "This route already exists"]);
            } else {
                sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
            }
        }
        break;

    case 'PUT':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            sendJsonResponse(400, ["error" => "Missing ID"]);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            $fields = [];
            $params = [];
            
            if (isset($data['origin'])) {
                $fields[] = "origin = ?";
                $params[] = $data['origin'];
            }
            if (isset($data['destination'])) {
                $fields[] = "destination = ?";
                $params[] = $data['destination'];
            }
            if (isset($data['distance_km'])) {
                $fields[] = "distance_km = ?";
                $params[] = $data['distance_km'];
            }
            if (isset($data['departure_time'])) {
                $fields[] = "departure_time = ?";
                $params[] = $data['departure_time'];
            }
            
            if (empty($fields)) {
                sendJsonResponse(400, ["error" => "No fields to update"]);
                return;
            }
            
            $params[] = $id;
            $query = "UPDATE routes SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            sendJsonResponse(200, ["status" => "success", "message" => "Route updated"]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if(!$id) {
            sendJsonResponse(400, ["error" => "Missing ID"]);
            return;
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM routes WHERE id = ?");
            $stmt->execute([$id]);
            sendJsonResponse(200, ["status" => "success", "message" => "Route deleted"]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
        }
        break;
        
    default:
        sendJsonResponse(405, ["error" => "Method not allowed"]);
}
?>
