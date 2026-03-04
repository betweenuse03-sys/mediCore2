-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Stored Function: fn_patient_age
-- ============================================================================
-- Purpose: Calculate patient age from date of birth
-- Parameters: p_dob (DATE) - Patient's date of birth
-- Returns: INT - Age in years
-- Usage: SELECT fn_patient_age('1990-05-15') AS age;
-- ============================================================================

USE hospital_db;

-- Drop function if it exists
DROP FUNCTION IF EXISTS fn_patient_age;

-- Change delimiter to allow semicolons in function body
DELIMITER $$

CREATE FUNCTION fn_patient_age(p_dob DATE)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_age INT;
    
    -- Calculate age using TIMESTAMPDIFF
    SET v_age = TIMESTAMPDIFF(YEAR, p_dob, CURDATE());
    
    -- Return the calculated age
    RETURN v_age;
END$$

DELIMITER ;

-- ============================================================================
-- Test the Function
-- ============================================================================

-- Test with specific date
SELECT fn_patient_age('1990-05-15') AS test_age;

-- Test with patient table data
SELECT 
    patient_id,
    name,
    dob,
    fn_patient_age(dob) AS age,
    CASE 
        WHEN fn_patient_age(dob) < 18 THEN 'Minor'
        WHEN fn_patient_age(dob) < 60 THEN 'Adult'
        ELSE 'Senior'
    END AS age_category
FROM patient
ORDER BY fn_patient_age(dob) DESC
LIMIT 10;

-- Verify function creation
SHOW FUNCTION STATUS WHERE Db = 'hospital_db' AND Name = 'fn_patient_age';

SELECT 'Function fn_patient_age created successfully!' AS status;

-- ============================================================================
-- Function Details
-- ============================================================================
-- Name: fn_patient_age
-- Type: Deterministic function (same input always produces same output)
-- Reads SQL Data: Yes (accesses CURDATE())
-- Purpose: Simplify age calculations throughout the system
-- Benefits:
--   1. Consistent age calculation across all queries
--   2. Eliminates redundant TIMESTAMPDIFF calculations
--   3. Easy to maintain and update if business rules change
--   4. Can be used in SELECT, WHERE, and ORDER BY clauses
-- ============================================================================