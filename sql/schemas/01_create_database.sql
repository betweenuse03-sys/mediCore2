-- ============================================================================
-- MediCore HMS - Create Database
-- Week 8 + Week 13 Combined Final Submission
-- ============================================================================

DROP DATABASE IF EXISTS medicore_db;

CREATE DATABASE medicore_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE medicore_db;

SET time_zone = '+06:00';  -- Bangladesh Standard Time

SELECT DATABASE() AS current_database;
SELECT @@character_set_database AS charset, @@collation_database AS collation;
SELECT 'Database medicore_db created successfully!' AS status;
