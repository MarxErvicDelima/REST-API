<?php
/**
 * ========================================================
 * UNIFIED ROUTE & SCHEDULE MANAGEMENT API
 * ========================================================
 * Endpoint: manage_routes_consolidated.php
 * Purpose: Handle creation, reading, updating of routes and schedules
 *          in a single-entry consolidated architecture
 * 
 * Supported Actions (GET):
 * - read: Retrieve all routes
 * - read_one: Retrieve specific route by ID
 * 
 * Supported Actions (POST):
 * - create: Create new route with schedule
 * 
 * Supported Actions (DELETE):
 * - delete: Delete route (via query parameter: ?id=X)
 * ========================================================
 */

header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? 'read';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if ($action === 'read') {
                getAllRoutes();
            } elseif ($action === 'read_one') {
                getRouteById($_GET['id'] ?? null);
            } else {
                sendJsonResponse(400, ['error' => 'Unknown GET action']);
            }
            break;

        case 'POST':
            if ($action === 'create') {
                createRoute();
            } else {
                sendJsonResponse(400, ['error' => 'Unknown POST action']);
            }
            break;

        case 'PUT':
            if ($action === 'update') {
                updateRoute($_GET['id'] ?? null);
            } else {
                sendJsonResponse(400, ['error' => 'Unknown PUT action']);
            }
            break;

        case 'DELETE':
            if ($action === 'delete') {
                deleteRoute($_GET['id'] ?? null);
            } else {
                sendJsonResponse(400, ['error' => 'Unknown DELETE action']);
            }
            break;

        default:
            sendJsonResponse(405, ['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    sendJsonResponse(500, ['error' => $e->getMessage()]);
}

/**
 * GET all routes
 */
function getAllRoutes() {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, origin, destination, distance_km, bus_code, bus_type, 
                   capacity, departure_time, fare, created_at
            FROM scheduled_trips 
            ORDER BY departure_time ASC
        ");
        $stmt->execute();
        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse(200, ['success' => true, 'data' => $routes]);
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => $e->getMessage()]);
    }
}

/**
 * GET single route by ID
 */
function getRouteById($id) {
    global $pdo;
    
    if (!$id) {
        sendJsonResponse(400, ['error' => 'Route ID is required']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, origin, destination, distance_km, bus_code, bus_type, 
                   capacity, departure_time, fare, created_at
            FROM scheduled_trips 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $route = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($route) {
            sendJsonResponse(200, ['success' => true, 'data' => $route]);
        } else {
            sendJsonResponse(404, ['error' => 'Route not found']);
        }
    } catch (Exception $e) {
        sendJsonResponse(500, ['error' => $e->getMessage()]);
    }
}

/**
 * POST create new route
 */
function createRoute() {
    global $pdo;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields (arrival_time removed)
    $required = ['origin', 'destination', 'distance_km', 'bus_code', 'bus_type', 
                 'capacity', 'departure_time', 'fare'];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            sendJsonResponse(400, ['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert route into scheduled_trips (arrival_time removed)
        $stmt = $pdo->prepare("
            INSERT INTO scheduled_trips 
            (origin, destination, distance_km, bus_code, bus_type, capacity, 
             departure_time, fare, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $input['origin'],
            $input['destination'],
            $input['distance_km'],
            $input['bus_code'],
            $input['bus_type'],
            $input['capacity'],
            $input['departure_time'],
            $input['fare']
        ]);
        
        $trip_id = $pdo->lastInsertId();
        
        // Create schedule entry with available_seats = capacity
        $scheduleStmt = $pdo->prepare("
            INSERT INTO schedules (scheduled_trip_id, available_seats, created_at)
            VALUES (?, ?, NOW())
        ");
        $scheduleStmt->execute([$trip_id, $input['capacity']]);
        
        $pdo->commit();
        
        sendJsonResponse(201, [
            'success' => true,
            'message' => 'Route created successfully',
            'trip_id' => $trip_id
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(500, ['error' => $e->getMessage()]);
    }
}

/**
 * UPDATE route by ID
 */
function updateRoute($id) {
    global $pdo;
    
    if (!$id) {
        sendJsonResponse(400, ['error' => 'Route ID is required']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['origin', 'destination', 'distance_km', 'bus_code', 'bus_type', 
                 'capacity', 'departure_time', 'fare'];
    
    foreach ($required as $field) {
        if (empty($input[$field])) {
            sendJsonResponse(400, ['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if route exists
        $checkStmt = $pdo->prepare("SELECT id FROM scheduled_trips WHERE id = ?");
        $checkStmt->execute([$id]);
        $existing = $checkStmt->fetch();
        
        if (!$existing) {
            $pdo->rollBack();
            sendJsonResponse(404, ['error' => 'Route not found']);
            return;
        }
        
        // Update the route - note: bus_code is unique but we're updating the same route so it's OK
        $stmt = $pdo->prepare("
            UPDATE scheduled_trips 
            SET origin = ?, 
                destination = ?, 
                distance_km = ?, 
                bus_code = ?, 
                bus_type = ?, 
                capacity = ?,
                departure_time = ?, 
                fare = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['origin'],
            $input['destination'],
            $input['distance_km'],
            $input['bus_code'],
            $input['bus_type'],
            $input['capacity'],
            $input['departure_time'],
            $input['fare'],
            $id
        ]);
        
        $pdo->commit();
        
        sendJsonResponse(200, [
            'success' => true,
            'message' => 'Route updated successfully',
            'trip_id' => $id
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(500, ['error' => $e->getMessage()]);
    }
}

/**
 * DELETE route by ID
 */
function deleteRoute($id) {
    global $pdo;
    
    if (!$id) {
        sendJsonResponse(400, ['error' => 'Route ID is required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Get route first to verify it exists
        $selectStmt = $pdo->prepare("SELECT id FROM scheduled_trips WHERE id = ?");
        $selectStmt->execute([$id]);
        $route = $selectStmt->fetch();
        
        if (!$route) {
            $pdo->rollBack();
            sendJsonResponse(404, ['error' => 'Route not found']);
            return;
        }
        
        // Delete tickets first (foreign key cascade)
        $deleteTickets = $pdo->prepare("
            DELETE FROM tickets 
            WHERE schedule_id IN (
                SELECT id FROM schedules WHERE scheduled_trip_id = ?
            )
        ");
        $deleteTickets->execute([$id]);
        
        // Delete schedules
        $deleteSchedules = $pdo->prepare("DELETE FROM schedules WHERE scheduled_trip_id = ?");
        $deleteSchedules->execute([$id]);
        
        // Delete route
        $deleteRoute = $pdo->prepare("DELETE FROM scheduled_trips WHERE id = ?");
        $deleteRoute->execute([$id]);
        
        $pdo->commit();
        
        sendJsonResponse(200, ['success' => true, 'message' => 'Route deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(500, ['error' => $e->getMessage()]);
    }
}

/**
 * Helper function to send JSON responses
 */
function sendJsonResponse($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data);
    exit;
}

?>
