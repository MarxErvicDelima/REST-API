<?php
require 'db.php';

try {
    // Define the routes that should exist
    $requiredRoutes = [
        // Primary Manila connections (bidirectional)
        ['Manila', 'Tabaco', 450.5],
        ['Tabaco', 'Manila', 450.5],
        ['Manila', 'Legazpi', 400.0],
        ['Legazpi', 'Manila', 400.0],
        ['Manila', 'Daet', 350.0],
        ['Daet', 'Manila', 350.0],
        ['Manila', 'Naga', 380.0],
        ['Naga', 'Manila', 380.0],
        // Additional routes for expanded coverage
        ['Manila', 'Camalig', 420.0],
        ['Camalig', 'Manila', 420.0],
        ['Tabaco', 'Legazpi', 55.0],
        ['Legazpi', 'Tabaco', 55.0],
        ['Legazpi', 'Naga', 65.0],
        ['Naga', 'Legazpi', 65.0],
        ['Daet', 'Naga', 45.0],
        ['Naga', 'Daet', 45.0],
        ['Tabaco', 'Naga', 120.0],
        ['Naga', 'Tabaco', 120.0]
    ];

    // Check if routes exist
    foreach ($requiredRoutes as $route) {
        $origin = $route[0];
        $destination = $route[1];
        $distance = $route[2];
        
        $checkStmt = $pdo->prepare("SELECT id FROM routes WHERE origin = ? AND destination = ?");
        $checkStmt->execute([$origin, $destination]);
        $result = $checkStmt->fetch();
        
        if (!$result) {
            // Insert the route
            $insertStmt = $pdo->prepare("INSERT INTO routes (origin, destination, distance_km) VALUES (?, ?, ?)");
            $insertStmt->execute([$origin, $destination, $distance]);
        }
    }

    sendJsonResponse(200, [
        'status' => 'success',
        'message' => 'Routes setup complete with 18 comprehensive routes',
        'total_routes' => count($requiredRoutes),
        'routes' => $requiredRoutes
    ]);

} catch (Exception $e) {
    sendJsonResponse(500, ['status' => 'error', 'error' => $e->getMessage()]);
}
?>
