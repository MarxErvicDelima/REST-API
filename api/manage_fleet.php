<?php
// /adet/api/manage_fleet.php
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT * FROM buses ORDER BY id DESC");
        sendJsonResponse(200, ["status" => "success", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        if(!isset($data['bus_number'], $data['capacity'], $data['bus_type'])) {
            sendJsonResponse(400, ["error" => "Missing parameters"]);
            return;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO buses (bus_number, capacity, bus_type) VALUES (?, ?, ?)");
            $stmt->execute([$data['bus_number'], $data['capacity'], $data['bus_type']]);
            sendJsonResponse(201, ["status" => "success", "id" => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                sendJsonResponse(400, ["error" => "This bus number already exists"]);
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
            
            if (isset($data['bus_number'])) {
                $fields[] = "bus_number = ?";
                $params[] = $data['bus_number'];
            }
            if (isset($data['capacity'])) {
                $fields[] = "capacity = ?";
                $params[] = $data['capacity'];
            }
            if (isset($data['bus_type'])) {
                $fields[] = "bus_type = ?";
                $params[] = $data['bus_type'];
            }
            
            if (empty($fields)) {
                sendJsonResponse(400, ["error" => "No fields to update"]);
                return;
            }
            
            $params[] = $id;
            $query = "UPDATE buses SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            sendJsonResponse(200, ["status" => "success", "message" => "Bus updated"]);
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
            $stmt = $pdo->prepare("DELETE FROM buses WHERE id = ?");
            $stmt->execute([$id]);
            sendJsonResponse(200, ["status" => "success", "message" => "Bus deleted"]);
        } catch (PDOException $e) {
            sendJsonResponse(500, ["error" => "Database error: " . $e->getMessage()]);
        }
        break;
        
    default:
        sendJsonResponse(405, ["error" => "Method not allowed"]);
}
?>
