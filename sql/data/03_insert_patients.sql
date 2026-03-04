-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: PATIENT
-- ============================================================================

USE medicore_db;

-- Insert patients with diverse demographics
INSERT INTO patient (name, dob, gender, blood_group, address, phone, email, emergency_contact, emergency_name) VALUES
('Mohammad Ali', '1985-03-15', 'M', 'A+', 'House 12, Road 5, Dhanmondi, Dhaka', '+880-1911-111111', 'mohammad.ali@email.com', '+880-1711-111111', 'Fatima Ali'),
('Ayesha Rahman', '1990-07-22', 'F', 'B+', 'Flat 3B, Gulshan-2, Dhaka', '+880-1922-222222', 'ayesha.rahman@email.com', '+880-1722-222222', 'Karim Rahman'),
('Jahangir Hossain', '1978-11-30', 'M', 'O+', 'Village: Sreepur, Gazipur', '+880-1933-333333', 'jahangir.h@email.com', '+880-1733-333333', 'Nasima Hossain'),
('Sumaiya Begum', '2015-01-10', 'F', 'AB+', 'House 45, Mirpur-10, Dhaka', '+880-1944-444444', 'sumaiya.parent@email.com', '+880-1744-444444', 'Rafiq Begum'),
('Abdul Karim', '1965-05-18', 'M', 'A-', 'Holding 234, Uttara Sector-7, Dhaka', '+880-1955-555555', 'abdul.karim@email.com', '+880-1755-555555', 'Halima Karim'),
('Tahmina Akter', '1995-09-25', 'F', 'B-', 'Flat 5C, Banani, Dhaka', '+880-1966-666666', 'tahmina.akter@email.com', '+880-1766-666666', 'Shahin Akter'),
('Rahim Uddin', '1982-12-08', 'M', 'O-', 'House 78, Mohammadpur, Dhaka', '+880-1977-777777', 'rahim.uddin@email.com', '+880-1777-777777', 'Salma Uddin'),
('Nadia Islam', '2010-04-14', 'F', 'A+', 'Apartment 2A, Bashundhara, Dhaka', '+880-1988-888888', 'nadia.parent@email.com', '+880-1788-888888', 'Imran Islam'),
('Farhan Ahmed', '1988-08-20', 'M', 'AB-', 'House 56, Lalmatia, Dhaka', '+880-1999-999999', 'farhan.ahmed@email.com', '+880-1799-999999', 'Sadia Ahmed'),
('Roxana Khatun', '1992-02-28', 'F', 'B+', 'Village: Kapasia, Gazipur', '+880-1900-111111', 'roxana.khatun@email.com', '+880-1700-111111', 'Mamun Khatun'),
('Shakib Hassan', '2005-06-12', 'M', 'O+', 'Flat 7D, Uttara Sector-3, Dhaka', '+880-1900-222222', 'shakib.parent@email.com', '+880-1700-222222', 'Nasrin Hassan'),
('Farzana Yasmin', '1980-10-05', 'F', 'A-', 'House 23, Badda, Dhaka', '+880-1900-333333', 'farzana.yasmin@email.com', '+880-1700-333333', 'Harun Yasmin'),
('Tanvir Khan', '1975-03-17', 'M', 'AB+', 'Holding 145, Rampura, Dhaka', '+880-1900-444444', 'tanvir.khan@email.com', '+880-1700-444444', 'Kulsum Khan'),
('Sabina Sultana', '1998-11-22', 'F', 'B-', 'Apartment 4F, Motijheel, Dhaka', '+880-1900-555555', 'sabina.sultana@email.com', '+880-1700-555555', 'Jahir Sultana'),
('Mahbub Alam', '1987-07-30', 'M', 'O-', 'House 89, Shyamoli, Dhaka', '+880-1900-666666', 'mahbub.alam@email.com', '+880-1700-666666', 'Rubina Alam');

-- Verify insertion
SELECT 
    patient_id,
    name,
    dob,
    gender,
    blood_group,
    phone
FROM patient
ORDER BY patient_id;

SELECT CONCAT('Inserted ', COUNT(*), ' patients') AS status FROM patient;

-- Show age distribution
SELECT 
    CASE 
        WHEN YEAR(CURDATE()) - YEAR(dob) < 18 THEN 'Child (0-17)'
        WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 18 AND 40 THEN 'Adult (18-40)'
        WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 41 AND 60 THEN 'Middle Age (41-60)'
        ELSE 'Senior (60+)'
    END AS age_group,
    COUNT(*) AS count
FROM patient
GROUP BY age_group
ORDER BY 
    CASE 
        WHEN YEAR(CURDATE()) - YEAR(dob) < 18 THEN 1
        WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 18 AND 40 THEN 2
        WHEN YEAR(CURDATE()) - YEAR(dob) BETWEEN 41 AND 60 THEN 3
        ELSE 4
    END;