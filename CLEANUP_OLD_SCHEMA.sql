-- ========================================================
-- CLEANUP SCRIPT for Existing Databases
-- ========================================================
-- Use this if you already have a database with old schema
-- and need to clean it up before running schema_unified.sql
--
-- INSTRUCTIONS:
-- 1. Copy all SQL from this file
-- 2. Go to phpMyAdmin → SQL tab OR MySQL command line
-- 3. Paste and execute
-- 4. Then run schema_unified.sql to create new tables
--
-- CAUTION: This will DELETE all data!
-- Back up your database first!
-- ========================================================

-- Start fresh: disable all constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Drop ALL tables in correct dependency order
-- Drop child tables FIRST (those with foreign keys)
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS schedules;

-- Drop old junction/link tables BEFORE their parents
DROP TABLE IF EXISTS bus_routes;

-- Drop parent tables AFTER children are gone
DROP TABLE IF EXISTS buses;
DROP TABLE IF EXISTS routes;
DROP TABLE IF EXISTS passengers;
DROP TABLE IF EXISTS admin_users;

-- Drop any other legacy tables that might exist
DROP TABLE IF EXISTS scheduled_trips;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS bookings;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify all tables are gone
SELECT COUNT(*) as remaining_tables 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE();

-- Expected result: 0 remaining tables
-- If you see 0, the database is clean and ready for schema_unified.sql
