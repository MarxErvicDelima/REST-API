<?php
/**
 * ========================================================
 * UNIFIED ROUTE & SCHEDULE MANAGEMENT API
 * ========================================================
 * Endpoint: manage_routes_consolidated.php
 * Purpose: Handle creation, reading, updating of routes and schedules
 *          in a single-entry consolidated architecture
 * 
 * Supported Actions:
 * - GET:  Retrieve routes and schedules
 * - POST: Create new route with schedule
 * - PUT:  Update route and schedule
 * - DELETE: Delete route and associated schedule
 * ========================================================
 */

header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Get action from query parameter
$action = isset($_GET['action']) ? sanitize($_GET['action']) : 'read';

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        case 'PUT':
            handlePutRequest($action);
            break;
        case 'DELETE':
            handleDeleteRequest($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ========================================================
// GET REQUEST HANDLERS
// ========================================================
function handleGetRequest($action) {
    global $conn;
    
    switch ($action) {
        case 'read':
            getAllRoutes();
            break;
        case 'read_one':
            getRouteById($_GET['id'] ?? null);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}

function getAllRoutes() {
    global $conn;
    
    try {
        $query = "SELECT * FROM scheduled_trips ORDER BY departure_time ASC";
        $result = $conn->query($query);
        
        if ($result) {
            $routes = [];
            while ($row = $result->fetch_assoc()) {
                $routes[] = $row;
            }
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $routes]);
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function getRouteById($id) {
    global $conn;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Route ID is required']);
        return;
    }
    
    try {
        $query = "SELECT * FROM scheduled_trips WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $route = $result->fetch_assoc();
            http_response_code(200);
            echo json_encode(['success' => true, 'data' => $route]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Route not found']);
        }
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ========================================================
// POST REQUEST HANDLERS (CREATE)
// ========================================================
function handlePostRequest($action) {
    global $conn;
    
    switch ($action) {
        case 'create':
            createRoute();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}

function createRoute() {
    global $conn;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['origin', 'destination', 'distance_km', 'bus_code', 'bus_type', 
                 'capacity', 'departure_date', 'departure_time', 'arrival_time', 'fare'];
    
    foreach ($required as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Missing or empty field: $field"]);
            return;
        }
    }
    
    try {
        // Prepare data
        $origin = sanitize($input['origin']);
        $destination = sanitize($input['destination']);
        $distance_km = floatval($input['distance_km']);
        $bus_code = sanitize($input['bus_code']);
        $bus_type = sanitize($input['bus_type']);
        $capacity = intval($input['capacity']);
        
        // Combine date and time
        $departure_datetime = $input['departure_date'] . ' ' . $input['departure_time'];
        $arrival_datetime = $input['departure_date'] . ' ' . $input['arrival_time'];
        
        $fare = floatval($input['fare']);
        
        // Insert into scheduled_trips
        $query = "INSERT INTO scheduled_trips 
                  (origin, destination, distance_km, bus_code, bus_type, capacity, 
                   departure_time, arrival_time, fare)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare error: " . $conn->error);
        }
        
        $stmt->bind_param("ssdssidsd", 
            $origin, $destination, $distance_km, $bus_code, $bus_type, 
            $capacity, $departure_datetime, $arrival_datetime, $fare
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Execute error: " . $stmt->error);
        }
        
        $trip_id = $stmt->insert_id;
        $stmt->close();
        
        // Create schedule entry with available seats = capacity
        $schedule_query = "INSERT INTO schedules (scheduled_trip_id, available_seats)
                          VALUES (?, ?)";
        $schedule_stmt = $conn->prepare($schedule_query);
        if (!$schedule_stmt) {
            throw new Exception("Schedule prepare error: " . $conn->error);
        }
        
        $schedule_stmt->bind_param("ii", $trip_id, $capacity);
        if (!$schedule_stmt->execute()) {
            throw new Exception("Schedule execute error: " . $schedule_stmt->error);
        }
        $schedule_stmt->close();
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Route and schedule created successfully',
            'data' => [
                'trip_id' => $trip_id,
                'origin' => $origin,
                'destination' => $destination,
                'bus_code' => $bus_code
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ========================================================
// PUT REQUEST HANDLERS (UPDATE)
// ========================================================
function handlePutRequest($action) {
    global $conn;
    
    switch ($action) {
        case 'update':
            updateRoute();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}

function updateRoute() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Route ID is required']);
        return;
    }
    
    try {
        $id = intval($input['id']);
        $updates = [];
        $types = "";
        $values = [];
        
        // Build dynamic update query
        $allowed_fields = ['origin', 'destination', 'distance_km', 'bus_code', 
                          'bus_type', 'capacity', 'departure_time', 'arrival_time', 'fare'];
        
        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                if ($field === 'distance_km' || $field === 'fare') {
                    $types .= "d";
                    $values[] = floatval($input[$field]);
                } elseif ($field === 'capacity') {
                    $types .= "i";
                    $values[] = intval($input[$field]);
                } else {
                    $types .= "s";
                    $values[] = sanitize($input[$field]);
                }
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No fields to update']);
            return;
        }
        
        $types .= "i";
        $values[] = $id;
        
        $query = "UPDATE scheduled_trips SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare error: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            throw new Exception("Execute error: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected > 0) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Route updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Route not found']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ========================================================
// DELETE REQUEST HANDLERS
// ========================================================
function handleDeleteRequest($action) {
    global $conn;
    
    switch ($action) {
        case 'delete':
            deleteRoute();
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}

function deleteRoute() {
    global $conn;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Route ID is required']);
        return;
    }
    
    try {
        $id = intval($input['id']);
        
        // Delete associated schedule and tickets (cascade)
        $query = "DELETE FROM scheduled_trips WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Delete error: " . $stmt->error);
        }
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected > 0) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Route deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Route not found']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ========================================================
// UTILITY FUNCTIONS
// ========================================================
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

?>
