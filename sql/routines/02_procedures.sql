USE medicore_db;

DROP PROCEDURE IF EXISTS sp_dispense_medicine;

DELIMITER $$

CREATE PROCEDURE sp_dispense_medicine(IN p_rx_id INT)
BEGIN
    -- Cursor variables
    DECLARE v_done         INT DEFAULT 0;
    DECLARE v_detail_id    INT;
    DECLARE v_medicine_id  INT;
    DECLARE v_quantity     INT;
    DECLARE v_stock_qty    INT;
    DECLARE v_med_name     VARCHAR(200);
    DECLARE v_error_msg    VARCHAR(500);

    -- Cursor over all prescription lines for this Rx
    DECLARE cur_details CURSOR FOR
        SELECT detail_id, medicine_id, quantity
        FROM prescription_detail
        WHERE rx_id = p_rx_id;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SELECT 'ERROR: Dispensing failed — transaction rolled back' AS message;
    END;

    START TRANSACTION;

    -- Validate prescription exists and is active
    IF (SELECT COUNT(*) FROM prescription WHERE rx_id = p_rx_id AND status = 'ACTIVE') = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Prescription not found or not ACTIVE';
    END IF;

    OPEN cur_details;

    read_loop: LOOP
        FETCH cur_details INTO v_detail_id, v_medicine_id, v_quantity;
        IF v_done THEN LEAVE read_loop; END IF;

        -- Check stock
        SELECT stock_qty, med_name INTO v_stock_qty, v_med_name
        FROM medicine WHERE medicine_id = v_medicine_id;

        IF v_stock_qty < v_quantity THEN
            CLOSE cur_details;
            SET v_error_msg = CONCAT('Insufficient stock for: ', v_med_name);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_error_msg;
        END IF;

        -- Deduct stock
        UPDATE medicine
        SET stock_qty  = stock_qty - v_quantity,
            updated_at = NOW()
        WHERE medicine_id = v_medicine_id;

        -- Mark detail as dispensed
        UPDATE prescription_detail
        SET dispensed_qty = v_quantity
        WHERE detail_id = v_detail_id;

    END LOOP;

    CLOSE cur_details;

    -- Update prescription status
    UPDATE prescription SET status = 'DISPENSED', updated_at = NOW() WHERE rx_id = p_rx_id;

    COMMIT;
    SELECT p_rx_id AS prescription_id, 'SUCCESS: Prescription dispensed' AS message;
END$$

DELIMITER ;

SELECT 'Procedure sp_dispense_medicine created successfully!' AS status;