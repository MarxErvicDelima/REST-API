-- ========================================================
-- ADET TRANSIT SYSTEM - Unified Database Schema
-- ========================================================
-- Compatible with: Web Portal & C++ Client
-- For ByetHost: b33_41637866_TRANSITGO
-- For Local: transit_system
--
-- IMPORTANT: This is the master schema file for all clients
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
-- This ensures no foreign key constraint violations
-- ========================================================
SET FOREIGN_KEY_CHECKS = 0;  -- Disable ALL foreign key checks

-- Drop tables in correct dependency order
-- CHILDREN first (tables with FKs pointing outward)
DROP TABLE IF EXISTS tickets;           -- Child of schedules, passengers
DROP TABLE IF EXISTS schedules;         -- Child of scheduled_trips

-- LEGACY TABLES (from old architecture - must be explicitly dropped)
DROP TABLE IF EXISTS bus_routes;        -- Legacy junction table (had FKs to buses, routes)
DROP TABLE IF EXISTS buses;             -- Legacy parent table

-- PARENT TABLES (independent or only referenced by children we dropped)
DROP TABLE IF EXISTS scheduled_trips;   -- New consolidated table
DROP TABLE IF EXISTS routes;            -- Legacy parent table
DROP TABLE IF EXISTS passengers;        -- Independent table
DROP TABLE IF EXISTS admin_users;       -- Independent table

-- NOW re-enable foreign key checks for new table creation
SET FOREIGN_KEY_CHECKS = 1;  -- Re-enable foreign key checks

-- ========================================================
-- TABLE 1: Admin Users
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE 2: Scheduled Trips (Consolidated)
-- UNIFIED SINGLE-ENTRY ARCHITECTURE:
-- Merges routes, buses, and schedules into one table
-- Each row represents a complete route schedule with bus assignment
-- ========================================================
CREATE TABLE scheduled_trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km DECIMAL(10,2),
    bus_code VARCHAR(20) NOT NULL UNIQUE,
    bus_type ENUM('Elite', 'Royal', 'Sleeper', 'Economy', 'Coach', 'Aircon', 'Minibus', 'Van', 'Luxury', 'Express') DEFAULT 'Economy',
    capacity INT NOT NULL,
    departure_time DATETIME NOT NULL,
    fare DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_trip (origin, destination, departure_time),
    INDEX idx_origin (origin),
    INDEX idx_destination (destination),
    INDEX idx_departure (departure_time),
    INDEX idx_bus_code (bus_code),
    INDEX idx_bus_type (bus_type),
    INDEX idx_capacity (capacity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE 3: Passengers
-- Stores passenger information (email-based identification)
-- ========================================================
CREATE TABLE passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE 4: Schedules (Extended - now references scheduled_trips)
-- Stores ticket availability and booking info per trip
-- ========================================================
CREATE TABLE schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scheduled_trip_id INT NOT NULL COMMENT 'Foreign key to scheduled_trips table',
    available_seats INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scheduled_trip_id) REFERENCES scheduled_trips(id) ON DELETE CASCADE,
    INDEX idx_scheduled_trip_id (scheduled_trip_id),
    UNIQUE KEY unique_schedule (scheduled_trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- TABLE 5: Tickets
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- SEED DATA - Admin Users
-- ========================================================
INSERT INTO admin_users (username, email, password_hash, full_name, status) VALUES 
('admin', 'admin@transitgo.com', 'admin123', 'System Administrator', 'active'),
('manager', 'manager@transitgo.com', 'admin123', 'Transit Manager', 'active'),
('marx', 'ervicmarx51504@gmail.com', 'admin123', 'managerial', 'active'),
('arbie', 'ancheta@gmail.com', 'admin123', 'managerial', 'active');

-- ========================================================
-- SEED DATA - Scheduled Trips (Consolidated Routes + Buses)
-- Single entry per trip with all route & bus info
-- UNIQUE BUS CODES: Each bus (BUS-101 to BUS-144) assigned to one trip
-- ========================================================
INSERT INTO scheduled_trips (origin, destination, distance_km, bus_code, bus_type, capacity, departure_time, fare) VALUES 
-- Manila to Tabaco
('Manila', 'Tabaco', '450.50', 'BUS-101', 'Elite', 40, '2026-05-01 08:00:00', 1200.00),
('Manila', 'Tabaco', '450.50', 'BUS-102', 'Royal', 48, '2026-05-01 14:00:00', 1200.00),
-- Tabaco to Manila
('Tabaco', 'Manila', '450.50', 'BUS-103', 'Elite', 40, '2026-05-01 09:00:00', 1200.00),
('Tabaco', 'Manila', '450.50', 'BUS-104', 'Royal', 48, '2026-05-01 16:00:00', 1200.00),
-- Manila to Legazpi
('Manila', 'Legazpi', '400.00', 'BUS-105', 'Economy', 45, '2026-05-01 09:30:00', 1000.00),
('Manila', 'Legazpi', '400.00', 'BUS-106', 'Sleeper', 32, '2026-05-01 15:30:00', 1000.00),
-- Legazpi to Manila
('Legazpi', 'Manila', '400.00', 'BUS-107', 'Economy', 45, '2026-05-01 10:00:00', 1000.00),
('Legazpi', 'Manila', '400.00', 'BUS-108', 'Sleeper', 32, '2026-05-02 10:00:00', 1000.00),
-- Manila to Daet
('Manila', 'Daet', '350.00', 'BUS-109', 'Sleeper', 30, '2026-05-01 07:00:00', 900.00),
('Manila', 'Daet', '350.00', 'BUS-110', 'Coach', 42, '2026-05-01 13:00:00', 900.00),
-- Daet to Manila
('Daet', 'Manila', '350.00', 'BUS-111', 'Sleeper', 30, '2026-05-01 08:00:00', 900.00),
('Daet', 'Manila', '350.00', 'BUS-112', 'Coach', 42, '2026-05-02 08:00:00', 900.00),
-- Manila to Naga
('Manila', 'Naga', '380.00', 'BUS-113', 'Aircon', 50, '2026-05-01 10:00:00', 950.00),
('Manila', 'Naga', '380.00', 'BUS-114', 'Economy', 35, '2026-05-01 18:00:00', 950.00),
-- Naga to Manila
('Naga', 'Manila', '380.00', 'BUS-115', 'Aircon', 50, '2026-05-01 11:00:00', 950.00),
('Naga', 'Manila', '380.00', 'BUS-116', 'Economy', 35, '2026-05-02 11:00:00', 950.00),
-- Tabaco to Legazpi
('Tabaco', 'Legazpi', '55.00', 'BUS-117', 'Sleeper', 32, '2026-05-01 10:00:00', 400.00),
('Tabaco', 'Legazpi', '55.00', 'BUS-118', 'Economy', 45, '2026-05-01 15:00:00', 400.00),
-- Legazpi to Tabaco
('Legazpi', 'Tabaco', '55.00', 'BUS-119', 'Sleeper', 32, '2026-05-01 12:00:00', 400.00),
('Legazpi', 'Tabaco', '55.00', 'BUS-120', 'Economy', 45, '2026-05-02 12:00:00', 400.00),
-- Legazpi to Naga
('Legazpi', 'Naga', '65.00', 'BUS-121', 'Coach', 42, '2026-05-01 11:00:00', 450.00),
('Legazpi', 'Naga', '65.00', 'BUS-122', 'Sleeper', 30, '2026-05-01 16:00:00', 450.00),
-- Naga to Legazpi
('Naga', 'Legazpi', '65.00', 'BUS-123', 'Coach', 42, '2026-05-01 14:00:00', 450.00),
('Naga', 'Legazpi', '65.00', 'BUS-124', 'Sleeper', 30, '2026-05-02 14:00:00', 450.00),
-- Daet to Naga
('Daet', 'Naga', '45.00', 'BUS-125', 'Aircon', 50, '2026-05-01 12:00:00', 350.00),
('Daet', 'Naga', '45.00', 'BUS-126', 'Economy', 35, '2026-05-01 17:00:00', 350.00),
-- Naga to Daet
('Naga', 'Daet', '45.00', 'BUS-127', 'Aircon', 50, '2026-05-01 15:00:00', 350.00),
('Naga', 'Daet', '45.00', 'BUS-128', 'Economy', 35, '2026-05-02 15:00:00', 350.00),

-- Additional routes for extended network testing
-- Tabaco to Naga (express route)
('Tabaco', 'Naga', '120.00', 'BUS-129', 'Elite', 40, '2026-05-02 06:00:00', 650.00),
('Tabaco', 'Naga', '120.00', 'BUS-130', 'Sleeper', 32, '2026-05-02 19:00:00', 650.00),
-- Naga to Tabaco (return)
('Naga', 'Tabaco', '120.00', 'BUS-131', 'Elite', 40, '2026-05-02 09:00:00', 650.00),
('Naga', 'Tabaco', '120.00', 'BUS-132', 'Sleeper', 32, '2026-05-03 08:00:00', 650.00),

-- Daet to Legazpi (connector route)
('Daet', 'Legazpi', '85.00', 'BUS-133', 'Economy', 45, '2026-05-02 07:30:00', 500.00),
('Daet', 'Legazpi', '85.00', 'BUS-134', 'Coach', 42, '2026-05-02 14:30:00', 500.00),
-- Legazpi to Daet (return)
('Legazpi', 'Daet', '85.00', 'BUS-135', 'Economy', 45, '2026-05-02 10:00:00', 500.00),
('Legazpi', 'Daet', '85.00', 'BUS-136', 'Coach', 42, '2026-05-02 17:00:00', 500.00),

-- Tabaco to Daet (long connector)
('Tabaco', 'Daet', '175.00', 'BUS-137', 'Royal', 48, '2026-05-02 05:00:00', 850.00),
('Tabaco', 'Daet', '175.00', 'BUS-138', 'Sleeper', 30, '2026-05-02 20:00:00', 850.00),
-- Daet to Tabaco (return)
('Daet', 'Tabaco', '175.00', 'BUS-139', 'Royal', 48, '2026-05-02 09:30:00', 850.00),
('Daet', 'Tabaco', '175.00', 'BUS-140', 'Sleeper', 30, '2026-05-03 00:30:00', 850.00),

-- Legazpi to Naga (short express)
('Legazpi', 'Naga', '65.00', 'BUS-141', 'Aircon', 50, '2026-05-02 08:00:00', 480.00),
('Legazpi', 'Naga', '65.00', 'BUS-142', 'Royal', 48, '2026-05-02 15:00:00', 480.00),
-- Naga to Legazpi (return express)
('Naga', 'Legazpi', '65.00', 'BUS-143', 'Aircon', 50, '2026-05-02 09:30:00', 480.00),
('Naga', 'Legazpi', '65.00', 'BUS-144', 'Royal', 48, '2026-05-02 16:30:00', 480.00);

-- ========================================================
-- SEED DATA - Schedules (Create entries for each trip)
-- ========================================================
INSERT INTO schedules (scheduled_trip_id, available_seats) 
SELECT id, capacity FROM scheduled_trips;

-- ========================================================
-- EXISTING PASSENGER DATA (from live system)
-- ========================================================
INSERT INTO passengers (name, email, phone) VALUES 
('MARX ERVIC CHANLIONGCO DELIMA', 'ervicmarx51504@gmail.com', '09161771157'),
('Kenneth Borjal', 'ken@email.com', '12345678910'),
('Toni Fowler', 'toni@gmail.com', '0934567890');

-- ========================================================
-- EXISTING TICKET DATA (from live system)
-- ========================================================
INSERT INTO tickets (passenger_id, schedule_id, seat_number, trip_code) VALUES 
(2, 1, 15, 'OE55');

-- ========================================================
-- QUICK QUERY EXAMPLES
-- ========================================================
-- 1. Get all scheduled trips with complete info:
--    SELECT * FROM scheduled_trips ORDER BY departure_time;
--
-- 2. Query by route:
--    SELECT * FROM scheduled_trips WHERE origin='Manila' AND destination='Tabaco' ORDER BY departure_time;
--
-- 3. Get trip with ticket availability:
--    SELECT st.*, s.available_seats FROM scheduled_trips st
--    LEFT JOIN schedules s ON st.id = s.scheduled_trip_id
--    WHERE st.origin='Manila' AND st.destination='Tabaco';
--
-- 4. Verify passenger booking:
--    SELECT p.name, p.email, st.origin, st.destination, st.bus_code, 
--           t.seat_number, t.trip_code, st.departure_time
--    FROM tickets t
--    JOIN passengers p ON t.passenger_id = p.id
--    JOIN schedules s ON t.schedule_id = s.id
--    JOIN scheduled_trips st ON s.scheduled_trip_id = st.id;

-- ========================================================
-- COMPATIBILITY NOTES
-- ========================================================
-- This schema implements SINGLE-ENTRY ARCHITECTURE:
-- ✅ All route + bus + schedule data in ONE table (scheduled_trips)
-- ✅ Eliminates M:N relationships and junction tables
-- ✅ Direct queryability: capacity and bus_type per route ID
-- ✅ Simplified for Web Portal and C++ Client
-- ✅ Maintains referential integrity with schedules and tickets
-- ========================================================
