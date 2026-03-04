-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Database Creation Script
-- ============================================================================
-- Purpose: Initialize the hospital_db database with proper settings
-- Author: HMS Development Team
-- Date: Week 8 - Mid-Semester
-- ============================================================================

-- Drop existing database if it exists (use with caution in production)
DROP DATABASE IF EXISTS hospital_db;

-- Create new database with UTF-8 encoding
CREATE DATABASE hospital_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Verify database creation
SHOW DATABASES LIKE 'hospital_db';

-- Switch to the newly created database
USE hospital_db;

-- Display current database
SELECT DATABASE() AS current_database;

-- Set time zone (optional, adjust as needed)
SET time_zone = '+06:00';  -- Bangladesh time

-- Display success message
SELECT 'Database hospital_db created successfully!' AS status;

-- Show database configuration
SELECT 
    @@character_set_database AS charset,
    @@collation_database AS collation;

-- ============================================================================
-- End of Database Creation Script
-- ============================================================================