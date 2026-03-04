-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: ROOM and BED
-- ============================================================================

USE medicore_db;

-- ============================================================================
-- Insert Rooms
-- ============================================================================

INSERT INTO room (room_number, ward, room_type, floor_no, daily_rate) VALUES
-- General Ward (Lower rates)
('G101', 'General Ward A', 'GENERAL', 1, 1500.00),
('G102', 'General Ward A', 'GENERAL', 1, 1500.00),
('G103', 'General Ward A', 'GENERAL', 1, 1500.00),
('G201', 'General Ward B', 'GENERAL', 2, 1500.00),
('G202', 'General Ward B', 'GENERAL', 2, 1500.00),

-- Semi-Private Rooms
('SP301', 'Semi-Private Wing', 'SEMI_PRIVATE', 3, 3000.00),
('SP302', 'Semi-Private Wing', 'SEMI_PRIVATE', 3, 3000.00),
('SP303', 'Semi-Private Wing', 'SEMI_PRIVATE', 3, 3000.00),

-- Private Rooms (Higher rates)
('P401', 'Private Wing', 'PRIVATE', 4, 5000.00),
('P402', 'Private Wing', 'PRIVATE', 4, 5000.00),
('P403', 'Private Wing', 'PRIVATE', 4, 5000.00),
('P404', 'Private Wing', 'PRIVATE', 4, 5000.00),

-- ICU (Highest rates)
('ICU501', 'Intensive Care Unit', 'ICU', 5, 10000.00),
('ICU502', 'Intensive Care Unit', 'ICU', 5, 10000.00),
('ICU503', 'Intensive Care Unit', 'ICU', 5, 10000.00),

-- Emergency
('ER001', 'Emergency Department', 'EMERGENCY', 0, 2000.00),
('ER002', 'Emergency Department', 'EMERGENCY', 0, 2000.00),
('ER003', 'Emergency Department', 'EMERGENCY', 0, 2000.00);

-- Verify room insertion
SELECT * FROM room ORDER BY room_type, room_number;

-- ============================================================================
-- Insert Beds
-- ============================================================================

-- General Ward rooms (4 beds each)
INSERT INTO bed (room_id, bed_number, status) VALUES
(1, 'A', 'AVAILABLE'),
(1, 'B', 'OCCUPIED'),
(1, 'C', 'AVAILABLE'),
(1, 'D', 'AVAILABLE'),

(2, 'A', 'AVAILABLE'),
(2, 'B', 'AVAILABLE'),
(2, 'C', 'OCCUPIED'),
(2, 'D', 'AVAILABLE'),

(3, 'A', 'OCCUPIED'),
(3, 'B', 'AVAILABLE'),
(3, 'C', 'AVAILABLE'),
(3, 'D', 'MAINTENANCE'),

(4, 'A', 'AVAILABLE'),
(4, 'B', 'AVAILABLE'),
(4, 'C', 'AVAILABLE'),
(4, 'D', 'AVAILABLE'),

(5, 'A', 'OCCUPIED'),
(5, 'B', 'AVAILABLE'),
(5, 'C', 'AVAILABLE'),
(5, 'D', 'AVAILABLE');

-- Semi-private rooms (2 beds each)
INSERT INTO bed (room_id, bed_number, status) VALUES
(6, 'A', 'AVAILABLE'),
(6, 'B', 'AVAILABLE'),

(7, 'A', 'OCCUPIED'),
(7, 'B', 'AVAILABLE'),

(8, 'A', 'AVAILABLE'),
(8, 'B', 'AVAILABLE');

-- Private rooms (1 bed each)
INSERT INTO bed (room_id, bed_number, status) VALUES
(9, 'A', 'AVAILABLE'),
(10, 'A', 'OCCUPIED'),
(11, 'A', 'AVAILABLE'),
(12, 'A', 'RESERVED');

-- ICU beds (1 bed each, critical equipment)
INSERT INTO bed (room_id, bed_number, status) VALUES
(13, 'A', 'OCCUPIED'),
(14, 'A', 'OCCUPIED'),
(15, 'A', 'AVAILABLE');

-- Emergency beds
INSERT INTO bed (room_id, bed_number, status) VALUES
(16, 'A', 'AVAILABLE'),
(17, 'A', 'AVAILABLE'),
(18, 'A', 'OCCUPIED');

-- Verify bed insertion
SELECT 
    r.room_number,
    r.room_type,
    r.ward,
    b.bed_number,
    b.status,
    r.daily_rate
FROM bed b
JOIN room r ON b.room_id = r.room_id
ORDER BY r.room_type, r.room_number, b.bed_number;

-- Summary statistics
SELECT 
    r.room_type,
    COUNT(DISTINCT r.room_id) AS total_rooms,
    COUNT(b.bed_id) AS total_beds,
    SUM(CASE WHEN b.status = 'AVAILABLE' THEN 1 ELSE 0 END) AS available_beds,
    SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied_beds,
    ROUND(100.0 * SUM(CASE WHEN b.status = 'OCCUPIED' THEN 1 ELSE 0 END) / COUNT(b.bed_id), 2) AS occupancy_rate
FROM room r
LEFT JOIN bed b ON r.room_id = b.room_id
GROUP BY r.room_type
ORDER BY r.room_type;

SELECT CONCAT('Inserted ', COUNT(*), ' rooms') AS status FROM room;
SELECT CONCAT('Inserted ', COUNT(*), ' beds') AS status FROM bed;