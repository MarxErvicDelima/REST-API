-- ========================================================
-- ADET TRANSIT SYSTEM - Database Schema
-- ========================================================
-- For ByetHost: b33_41637866_TRANSITGO
-- For Local: transit_system
--
-- IMPORTANT: This schema creates all necessary tables
-- Execute this file in phpMyAdmin or via MySQL client
-- 
-- ⚠️  FOR BYETHOST USERS:
-- Before importing this file:
-- 1. Log into phpMyAdmin
-- 2. Select your database: b33_41637866_TRANSITGO
-- 3. Then import this file
-- 
-- Do NOT try to import with "Create new database" option
-- ========================================================

-- For ByetHost: Database is pre-created (b33_41637866_TRANSITGO)
-- For Local Development: Uncomment these lines
CREATE DATABASE IF NOT EXISTS transit_system;
USE transit_system;

-- ========================================================
-- DROP EXISTING TABLES (in correct order)
-- IMPORTANT: Drop dependent tables FIRST, parent tables LAST
-- ========================================================
SET FOREIGN_KEY_CHECKS = 0;  -- Temporarily disable foreign key checks

DROP TABLE IF EXISTS tickets;       -- Depends on schedules, passengers
DROP TABLE IF EXISTS schedules;     -- Depends on bus_routes
DROP TABLE IF EXISTS bus_routes;    -- Depends on buses, routes
DROP TABLE IF EXISTS passengers;    -- Independent
DROP TABLE IF EXISTS admin_users;   -- Independent
DROP TABLE IF EXISTS buses;         -- Parent table
DROP TABLE IF EXISTS routes;        -- Parent table

SET FOREIGN_KEY_CHECKS = 1;  -- Re-enable foreign key checks

-- ========================================================
-- TABLE 1: Buses
-- Stores information about buses in the fleet
-- ========================================================
CREATE TABLE buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_number VARCHAR(20) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    bus_type ENUM('Economy', 'Aircon', 'Sleeper', 'Coach', 'Minibus', 'Van', 'Luxury', 'Express') DEFAULT 'Economy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- TABLE 2: Routes
-- Stores route information (origin to destination)
-- ========================================================
CREATE TABLE routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km DECIMAL(10,2),
    departure_time TIME DEFAULT '06:00:00' COMMENT 'Standard departure time for this route',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_route (origin, destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- TABLE 2B: Admin Users
-- Stores admin credentials for dashboard authentication
-- ========================================================
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed password (60 chars)',
    full_name VARCHAR(255),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- TABLE 3: Bus Routes (Junction Table)
-- Links buses to routes with custom capacity per route
-- Enables M:N relationship between buses and routes
-- ========================================================
CREATE TABLE bus_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT NOT NULL,
    route_id INT NOT NULL,
    capacity INT NOT NULL COMMENT 'Capacity override for this specific route',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bus_route (bus_id, route_id),
    INDEX idx_bus_id (bus_id),
    INDEX idx_route_id (route_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- TABLE 4: Schedules
-- Stores departure schedules for each bus-route assignment
-- CRITICAL: Must have bus_route_id column
-- ========================================================
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_route_id INT NOT NULL COMMENT 'Foreign key to bus_routes table',
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    fare DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_route_id) REFERENCES bus_routes(id) ON DELETE CASCADE,
    INDEX idx_bus_route_id (bus_route_id),
    INDEX idx_departure (departure_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- TABLE 5: Passengers
-- Stores passenger information (no authentication required)
-- ========================================================
CREATE TABLE passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- TABLE 6: Tickets
-- Stores ticket bookings
-- UNIQUE constraint prevents double-booking
-- ========================================================
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    schedule_id INT NOT NULL,
    seat_number INT NOT NULL,
    trip_code VARCHAR(20) UNIQUE NOT NULL COMMENT 'Unique code for passenger to search/verify booking',
    booking_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES passengers(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat (schedule_id, seat_number) COMMENT 'Prevents double-booking',
    INDEX idx_passenger_id (passenger_id),
    INDEX idx_schedule_id (schedule_id),
    INDEX idx_trip_code (trip_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================
-- SEED DATA
-- ========================================================

-- Insert test buses
INSERT INTO buses (bus_number, capacity, bus_type) VALUES 
('BUS-101', 40, 'Aircon'),
('BUS-102', 45, 'Economy'),
('BUS-103', 30, 'Sleeper'),
('BUS-104', 50, 'Aircon'),
('BUS-105', 35, 'Economy'),
('BUS-106', 48, 'Aircon'),
('BUS-107', 32, 'Sleeper'),
('BUS-108', 42, 'Economy');

-- Insert test routes (Extended routes for comprehensive coverage)
INSERT INTO routes (origin, destination, distance_km) VALUES 
-- Primary Manila connections (bidirectional)
('Manila', 'Tabaco', 450.5),
('Tabaco', 'Manila', 450.5),
('Manila', 'Legazpi', 400.0),
('Legazpi', 'Manila', 400.0),
('Manila', 'Daet', 350.0),
('Daet', 'Manila', 350.0),
('Manila', 'Naga', 380.0),
('Naga', 'Manila', 380.0),
-- Additional routes for expanded coverage
('Manila', 'Camalig', 420.0),
('Camalig', 'Manila', 420.0),
('Tabaco', 'Legazpi', 55.0),
('Legazpi', 'Tabaco', 55.0),
('Legazpi', 'Naga', 65.0),
('Naga', 'Legazpi', 65.0),
('Daet', 'Naga', 45.0),
('Naga', 'Daet', 45.0),
('Tabaco', 'Naga', 120.0),
('Naga', 'Tabaco', 120.0);

-- Insert default admin users (password: admin123)
-- Note: Passwords stored as plain text per user requirement
INSERT INTO admin_users (username, email, password_hash, full_name, status) VALUES 
('admin', 'admin@transitgo.com', 'admin123', 'System Administrator', 'active'),
('manager', 'manager@transitgo.com', 'admin123', 'Transit Manager', 'active');

-- Assign buses to routes (junction table)
-- All 8 buses assigned to all 18 routes for comprehensive coverage
INSERT INTO bus_routes (bus_id, route_id, capacity, is_active) VALUES 
-- Routes 1-2: Manila-Tabaco & Tabaco-Manila
(1, 1, 40, TRUE),      -- BUS-101 on Manila-Tabaco
(1, 2, 40, TRUE),      -- BUS-101 on Tabaco-Manila
(6, 1, 48, TRUE),      -- BUS-106 on Manila-Tabaco
(6, 2, 48, TRUE),      -- BUS-106 on Tabaco-Manila
-- Routes 3-4: Manila-Legazpi & Legazpi-Manila
(2, 3, 45, TRUE),      -- BUS-102 on Manila-Legazpi
(2, 4, 45, TRUE),      -- BUS-102 on Legazpi-Manila
(7, 3, 32, TRUE),      -- BUS-107 on Manila-Legazpi
(7, 4, 32, TRUE),      -- BUS-107 on Legazpi-Manila
-- Routes 5-6: Manila-Daet & Daet-Manila
(3, 5, 30, TRUE),      -- BUS-103 on Manila-Daet
(3, 6, 30, TRUE),      -- BUS-103 on Daet-Manila
(8, 5, 42, TRUE),      -- BUS-108 on Manila-Daet
(8, 6, 42, TRUE),      -- BUS-108 on Daet-Manila
-- Routes 7-8: Manila-Naga & Naga-Manila
(4, 7, 50, TRUE),      -- BUS-104 on Manila-Naga
(4, 8, 50, TRUE),      -- BUS-104 on Naga-Manila
(5, 7, 35, TRUE),      -- BUS-105 on Manila-Naga
(5, 8, 35, TRUE),      -- BUS-105 on Naga-Manila
-- Routes 9-10: Manila-Camalig & Camalig-Manila (New)
(6, 9, 48, TRUE),      -- BUS-106 on Manila-Camalig
(6, 10, 48, TRUE),     -- BUS-106 on Camalig-Manila
(1, 9, 40, TRUE),      -- BUS-101 on Manila-Camalig
(1, 10, 40, TRUE),     -- BUS-101 on Camalig-Manila
-- Routes 11-12: Tabaco-Legazpi & Legazpi-Tabaco (New)
(7, 11, 32, TRUE),     -- BUS-107 on Tabaco-Legazpi
(7, 12, 32, TRUE),     -- BUS-107 on Legazpi-Tabaco
(2, 11, 45, TRUE),     -- BUS-102 on Tabaco-Legazpi
(2, 12, 45, TRUE),     -- BUS-102 on Legazpi-Tabaco
-- Routes 13-14: Legazpi-Naga & Naga-Legazpi (New)
(8, 13, 42, TRUE),     -- BUS-108 on Legazpi-Naga
(8, 14, 42, TRUE),     -- BUS-108 on Naga-Legazpi
(3, 13, 30, TRUE),     -- BUS-103 on Legazpi-Naga
(3, 14, 30, TRUE),     -- BUS-103 on Naga-Legazpi
-- Routes 15-16: Daet-Naga & Naga-Daet (New)
(4, 15, 50, TRUE),     -- BUS-104 on Daet-Naga
(4, 16, 50, TRUE),     -- BUS-104 on Naga-Daet
(5, 15, 35, TRUE),     -- BUS-105 on Daet-Naga
(5, 16, 35, TRUE),     -- BUS-105 on Naga-Daet
-- Routes 17-18: Tabaco-Naga & Naga-Tabaco (New)
(1, 17, 40, TRUE),     -- BUS-101 on Tabaco-Naga
(1, 18, 40, TRUE),     -- BUS-101 on Naga-Tabaco
(6, 17, 48, TRUE),     -- BUS-106 on Tabaco-Naga
(6, 18, 48, TRUE);     -- BUS-106 on Naga-Tabaco

-- Create test schedules (comprehensive coverage for all routes)
INSERT INTO schedules (bus_route_id, departure_time, arrival_time, fare) VALUES 
-- Manila to Tabaco routes (bus_route_id 1, 3)
(1, '2026-05-01 08:00:00', '2026-05-01 18:00:00', 1200.00),
(1, '2026-05-01 14:00:00', '2026-05-02 00:00:00', 1200.00),
(3, '2026-05-02 08:00:00', '2026-05-02 18:00:00', 1200.00),
-- Tabaco to Manila routes (bus_route_id 2, 4)
(2, '2026-05-01 09:00:00', '2026-05-01 19:00:00', 1200.00),
(2, '2026-05-01 16:00:00', '2026-05-02 02:00:00', 1200.00),
(4, '2026-05-02 09:00:00', '2026-05-02 19:00:00', 1200.00),
-- Manila to Legazpi routes (bus_route_id 5, 7)
(5, '2026-05-01 09:30:00', '2026-05-01 18:30:00', 1000.00),
(5, '2026-05-01 15:30:00', '2026-05-02 00:30:00', 1000.00),
(7, '2026-05-02 09:30:00', '2026-05-02 18:30:00', 1000.00),
-- Legazpi to Manila routes (bus_route_id 6, 8)
(6, '2026-05-01 10:00:00', '2026-05-01 19:00:00', 1000.00),
(6, '2026-05-02 10:00:00', '2026-05-02 19:00:00', 1000.00),
(8, '2026-05-01 10:30:00', '2026-05-01 19:30:00', 1000.00),
-- Manila to Daet routes (bus_route_id 9, 11)
(9, '2026-05-01 07:00:00', '2026-05-01 15:00:00', 900.00),
(9, '2026-05-01 13:00:00', '2026-05-01 21:00:00', 900.00),
(11, '2026-05-02 07:00:00', '2026-05-02 15:00:00', 900.00),
-- Daet to Manila routes (bus_route_id 10, 12)
(10, '2026-05-01 08:00:00', '2026-05-01 16:00:00', 900.00),
(10, '2026-05-02 08:00:00', '2026-05-02 16:00:00', 900.00),
(12, '2026-05-01 08:30:00', '2026-05-01 16:30:00', 900.00),
-- Manila to Naga routes (bus_route_id 13, 15)
(13, '2026-05-01 10:00:00', '2026-05-01 17:00:00', 950.00),
(13, '2026-05-01 18:00:00', '2026-05-02 01:00:00', 950.00),
(15, '2026-05-02 10:00:00', '2026-05-02 17:00:00', 950.00),
-- Naga to Manila routes (bus_route_id 14, 16)
(14, '2026-05-01 11:00:00', '2026-05-01 18:00:00', 950.00),
(14, '2026-05-02 11:00:00', '2026-05-02 18:00:00', 950.00),
(16, '2026-05-01 11:30:00', '2026-05-01 18:30:00', 950.00),
-- Manila to Camalig routes (bus_route_id 17, 19)
(17, '2026-05-01 08:30:00', '2026-05-01 17:30:00', 1100.00),
(17, '2026-05-01 14:30:00', '2026-05-01 23:30:00', 1100.00),
(19, '2026-05-02 08:30:00', '2026-05-02 17:30:00', 1100.00),
-- Camalig to Manila routes (bus_route_id 18, 20)
(18, '2026-05-01 09:30:00', '2026-05-01 18:30:00', 1100.00),
(18, '2026-05-02 09:30:00', '2026-05-02 18:30:00', 1100.00),
(20, '2026-05-01 10:00:00', '2026-05-01 19:00:00', 1100.00),
-- Tabaco to Legazpi routes (bus_route_id 21, 23)
(21, '2026-05-01 10:00:00', '2026-05-01 11:00:00', 400.00),
(21, '2026-05-01 15:00:00', '2026-05-01 16:00:00', 400.00),
(23, '2026-05-02 10:00:00', '2026-05-02 11:00:00', 400.00),
-- Legazpi to Tabaco routes (bus_route_id 22, 24)
(22, '2026-05-01 12:00:00', '2026-05-01 13:00:00', 400.00),
(22, '2026-05-02 12:00:00', '2026-05-02 13:00:00', 400.00),
(24, '2026-05-01 13:00:00', '2026-05-01 14:00:00', 400.00),
-- Legazpi to Naga routes (bus_route_id 25, 27)
(25, '2026-05-01 11:00:00', '2026-05-01 12:05:00', 450.00),
(25, '2026-05-01 16:00:00', '2026-05-01 17:05:00', 450.00),
(27, '2026-05-02 11:00:00', '2026-05-02 12:05:00', 450.00),
-- Naga to Legazpi routes (bus_route_id 26, 28)
(26, '2026-05-01 14:00:00', '2026-05-01 15:05:00', 450.00),
(26, '2026-05-02 14:00:00', '2026-05-02 15:05:00', 450.00),
(28, '2026-05-01 15:00:00', '2026-05-01 16:05:00', 450.00),
-- Daet to Naga routes (bus_route_id 29, 31)
(29, '2026-05-01 12:00:00', '2026-05-01 13:00:00', 350.00),
(29, '2026-05-01 17:00:00', '2026-05-01 18:00:00', 350.00),
(31, '2026-05-02 12:00:00', '2026-05-02 13:00:00', 350.00),
-- Naga to Daet routes (bus_route_id 30, 32)
(30, '2026-05-01 15:00:00', '2026-05-01 16:00:00', 350.00),
(30, '2026-05-02 15:00:00', '2026-05-02 16:00:00', 350.00),
(32, '2026-05-01 16:00:00', '2026-05-01 17:00:00', 350.00),
-- Tabaco to Naga routes (bus_route_id 33, 35)
(33, '2026-05-01 13:00:00', '2026-05-01 15:00:00', 550.00),
(33, '2026-05-01 18:00:00', '2026-05-01 20:00:00', 550.00),
(35, '2026-05-02 13:00:00', '2026-05-02 15:00:00', 550.00),
-- Naga to Tabaco routes (bus_route_id 34, 36)
(34, '2026-05-01 16:00:00', '2026-05-01 18:00:00', 550.00),
(34, '2026-05-02 16:00:00', '2026-05-02 18:00:00', 550.00),
(36, '2026-05-01 17:00:00', '2026-05-01 19:00:00', 550.00);

-- ========================================================
-- VERIFICATION CHECKS
-- ========================================================
-- Run these queries to verify setup:
-- 
-- 1. Check all tables created:
--    SELECT table_name FROM information_schema.tables 
--    WHERE table_schema = 'transit_system';
--
-- 2. Check schedules table structure (must have bus_route_id):
--    DESCRIBE schedules;
--
-- 3. Count records in each table:
--    SELECT 'buses' as table_name, COUNT(*) as count FROM buses
--    UNION ALL
--    SELECT 'routes', COUNT(*) FROM routes
--    UNION ALL
--    SELECT 'bus_routes', COUNT(*) FROM bus_routes
--    UNION ALL
--    SELECT 'schedules', COUNT(*) FROM schedules
--    UNION ALL
--    SELECT 'passengers', COUNT(*) FROM passengers
--    UNION ALL
--    SELECT 'tickets', COUNT(*) FROM tickets;
--
-- 4. Test schedule query (should return results):
--    SELECT s.id, s.departure_time, b.bus_number, r.origin, r.destination
--    FROM schedules s
--    JOIN bus_routes br ON s.bus_route_id = br.id
--    JOIN buses b ON br.bus_id = b.id
--    JOIN routes r ON br.route_id = r.id
--    LIMIT 3;
--
-- ========================================================
