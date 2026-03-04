-- ============================================================================
-- Hospital Management System (HMS) - Week 8
-- Sample Data: MEDICINE
-- ============================================================================

USE medicore_db;

-- Insert medicines with realistic stock levels and prices
INSERT INTO medicine (med_name, generic_name, category, manufacturer, unit_price, stock_qty, reorder_level, expiry_date, description) VALUES
-- Cardiovascular medications
('Atorvastatin 20mg', 'Atorvastatin', 'Cardiovascular', 'Square Pharmaceuticals', 5.50, 500, 100, '2027-12-31', 'Cholesterol-lowering medication'),
('Amlodipine 5mg', 'Amlodipine', 'Cardiovascular', 'Beximco Pharma', 3.20, 750, 150, '2027-06-30', 'Blood pressure medication'),
('Aspirin 75mg', 'Acetylsalicylic Acid', 'Cardiovascular', 'Renata Limited', 1.50, 1000, 200, '2028-03-15', 'Blood thinner'),
('Clopidogrel 75mg', 'Clopidogrel', 'Cardiovascular', 'Incepta Pharma', 8.75, 300, 80, '2027-09-20', 'Antiplatelet medication'),

-- Antibiotics
('Amoxicillin 500mg', 'Amoxicillin', 'Antibiotic', 'Square Pharmaceuticals', 6.00, 600, 120, '2026-11-30', 'Broad-spectrum antibiotic'),
('Azithromycin 500mg', 'Azithromycin', 'Antibiotic', 'Beximco Pharma', 12.50, 400, 100, '2027-02-28', 'Macrolide antibiotic'),
('Ciprofloxacin 500mg', 'Ciprofloxacin', 'Antibiotic', 'Renata Limited', 7.80, 350, 80, '2027-07-15', 'Fluoroquinolone antibiotic'),
('Metronidazole 400mg', 'Metronidazole', 'Antibiotic', 'Incepta Pharma', 4.25, 500, 100, '2027-05-10', 'Antiprotozoal medication'),

-- Pain relief and anti-inflammatory
('Paracetamol 500mg', 'Paracetamol', 'Analgesic', 'Square Pharmaceuticals', 1.00, 2000, 400, '2028-06-30', 'Pain reliever and fever reducer'),
('Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Beximco Pharma', 2.50, 800, 200, '2027-10-20', 'Anti-inflammatory pain reliever'),
('Tramadol 50mg', 'Tramadol', 'Opioid Analgesic', 'Renata Limited', 15.00, 200, 50, '2027-04-15', 'Moderate to severe pain relief'),
('Diclofenac 50mg', 'Diclofenac', 'NSAID', 'Incepta Pharma', 3.75, 450, 100, '2027-08-25', 'Anti-inflammatory medication'),

-- Diabetes medications
('Metformin 500mg', 'Metformin', 'Antidiabetic', 'Square Pharmaceuticals', 2.80, 900, 180, '2028-01-31', 'Type 2 diabetes medication'),
('Glimepiride 2mg', 'Glimepiride', 'Antidiabetic', 'Beximco Pharma', 4.50, 400, 80, '2027-11-15', 'Blood sugar control'),
('Insulin Glargine 100IU/ml', 'Insulin Glargine', 'Antidiabetic', 'Novo Nordisk', 850.00, 50, 15, '2026-12-31', 'Long-acting insulin'),

-- Respiratory medications
('Salbutamol Inhaler', 'Salbutamol', 'Bronchodilator', 'GSK Bangladesh', 250.00, 150, 30, '2027-09-30', 'Asthma relief inhaler'),
('Montelukast 10mg', 'Montelukast', 'Anti-asthmatic', 'Square Pharmaceuticals', 18.50, 200, 50, '2027-06-20', 'Asthma and allergy control'),
('Cetirizine 10mg', 'Cetirizine', 'Antihistamine', 'Renata Limited', 2.00, 600, 120, '2028-02-28', 'Allergy relief'),

-- Gastrointestinal medications
('Omeprazole 20mg', 'Omeprazole', 'Proton Pump Inhibitor', 'Beximco Pharma', 3.50, 700, 140, '2027-12-15', 'Acid reflux medication'),
('Ranitidine 150mg', 'Ranitidine', 'H2 Blocker', 'Incepta Pharma', 2.25, 500, 100, '2027-07-30', 'Heartburn relief'),
('Domperidone 10mg', 'Domperidone', 'Antiemetic', 'Square Pharmaceuticals', 1.80, 450, 90, '2027-10-10', 'Nausea and vomiting relief'),

-- Vitamins and supplements
('Vitamin D3 60000 IU', 'Cholecalciferol', 'Vitamin', 'Renata Limited', 25.00, 300, 60, '2028-03-31', 'Vitamin D supplement'),
('Calcium + Vitamin D', 'Calcium Carbonate', 'Mineral Supplement', 'Square Pharmaceuticals', 8.00, 400, 80, '2028-05-15', 'Bone health supplement'),
('Multivitamin', 'Mixed Vitamins', 'Vitamin', 'Beximco Pharma', 12.00, 350, 70, '2027-11-30', 'Daily multivitamin'),

-- Neurological medications
('Pregabalin 75mg', 'Pregabalin', 'Neuropathic Pain', 'Incepta Pharma', 22.50, 180, 40, '2027-08-15', 'Nerve pain medication'),
('Levetiracetam 500mg', 'Levetiracetam', 'Antiepileptic', 'Square Pharmaceuticals', 35.00, 120, 30, '2027-04-20', 'Seizure control medication');

-- Verify insertion
SELECT 
    medicine_id,
    med_name,
    category,
    unit_price,
    stock_qty,
    reorder_level,
    expiry_date
FROM medicine
ORDER BY category, med_name;

-- Show medicines needing reorder
SELECT 
    med_name,
    stock_qty,
    reorder_level,
    (reorder_level - stock_qty) AS qty_to_order
FROM medicine
WHERE stock_qty <= reorder_level
ORDER BY (reorder_level - stock_qty) DESC;

-- Show medicines expiring soon (within 6 months)
SELECT 
    med_name,
    category,
    stock_qty,
    expiry_date,
    DATEDIFF(expiry_date, CURDATE()) AS days_to_expiry
FROM medicine
WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
ORDER BY expiry_date;

-- Summary by category
SELECT 
    category,
    COUNT(*) AS med_count,
    SUM(stock_qty) AS total_stock,
    ROUND(SUM(unit_price * stock_qty), 2) AS total_value
FROM medicine
GROUP BY category
ORDER BY total_value DESC;

SELECT CONCAT('Inserted ', COUNT(*), ' medicines') AS status FROM medicine;