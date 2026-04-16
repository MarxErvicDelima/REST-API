<?php
/**
 * Migration: Add departure_time column to routes table
 * 
 * This script safely adds the departure_time column to the routes table
 * if it doesn't already exist. It's safe to run multiple times.
 * 
 * Usage: Execute this file once to apply the migration
 */

require_once 'db.php';

try {
    // Check if the column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM routes LIKE 'departure_time'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo json_encode([
            "status" => "info",
            "message" => "departure_time column already exists in routes table"
        ]);
    } else {
        // Add the column with a default value
        $pdo->exec("ALTER TABLE routes ADD COLUMN departure_time TIME DEFAULT '06:00:00' COMMENT 'Standard departure time for this route' AFTER distance_km");
        
        echo json_encode([
            "status" => "success",
            "message" => "departure_time column successfully added to routes table",
            "details" => "All existing routes now have a default departure time of 06:00:00"
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Migration failed",
        "error" => $e->getMessage()
    ]);
}
?>
