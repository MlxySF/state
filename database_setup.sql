-- ============================================
-- Wushu Registration System Database Schema
-- ============================================

-- Drop existing tables if they exist
DROP TABLE IF EXISTS monthly_stats;
DROP TABLE IF EXISTS registrations;

-- Main registrations table
CREATE TABLE registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    
    -- Student Information
    name_en VARCHAR(255) NOT NULL,
    name_cn VARCHAR(255),
    ic VARCHAR(20) NOT NULL,
    age INT NOT NULL,
    school VARCHAR(255) NOT NULL,
    status ENUM('Student 学生', 'State Team 州队', 'Backup Team 后备队') NOT NULL,
    
    -- Contact Information
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    
    -- Training Details
    level VARCHAR(50),
    events TEXT NOT NULL,
    schedule TEXT NOT NULL,
    class_count INT DEFAULT 0,
    
    -- Parent/Guardian Information
    parent_name VARCHAR(255) NOT NULL,
    parent_ic VARCHAR(20) NOT NULL,
    signature_base64 LONGTEXT,
    
    -- Payment Information
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_receipt_base64 LONGTEXT,
    payment_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    
    -- Documents
    signed_pdf_base64 LONGTEXT,
    form_date VARCHAR(20),
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_payment_status (payment_status),
    INDEX idx_registration_number (registration_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monthly statistics table for smart analytics
CREATE TABLE monthly_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year INT NOT NULL,
    month INT NOT NULL,
    total_registrations INT DEFAULT 0,
    total_revenue DECIMAL(10,2) DEFAULT 0,
    state_team_count INT DEFAULT 0,
    backup_team_count INT DEFAULT 0,
    student_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger to update monthly stats automatically when new registration is added
DELIMITER $$

CREATE TRIGGER after_registration_insert
AFTER INSERT ON registrations
FOR EACH ROW
BEGIN
    DECLARE reg_year INT;
    DECLARE reg_month INT;
    
    SET reg_year = YEAR(NEW.created_at);
    SET reg_month = MONTH(NEW.created_at);
    
    INSERT INTO monthly_stats (year, month, total_registrations, total_revenue, 
                               state_team_count, backup_team_count, student_count)
    VALUES (reg_year, reg_month, 1, NEW.payment_amount,
            IF(NEW.status = 'State Team 州队', 1, 0),
            IF(NEW.status = 'Backup Team 后备队', 1, 0),
            IF(NEW.status = 'Student 学生', 1, 0))
    ON DUPLICATE KEY UPDATE
        total_registrations = total_registrations + 1,
        total_revenue = total_revenue + NEW.payment_amount,
        state_team_count = state_team_count + IF(NEW.status = 'State Team 州队', 1, 0),
        backup_team_count = backup_team_count + IF(NEW.status = 'Backup Team 后备队', 1, 0),
        student_count = student_count + IF(NEW.status = 'Student 学生', 1, 0),
        updated_at = CURRENT_TIMESTAMP;
END$$

-- Trigger to update monthly stats when registration is deleted
CREATE TRIGGER after_registration_delete
AFTER DELETE ON registrations
FOR EACH ROW
BEGIN
    DECLARE reg_year INT;
    DECLARE reg_month INT;
    
    SET reg_year = YEAR(OLD.created_at);
    SET reg_month = MONTH(OLD.created_at);
    
    UPDATE monthly_stats
    SET total_registrations = total_registrations - 1,
        total_revenue = total_revenue - OLD.payment_amount,
        state_team_count = state_team_count - IF(OLD.status = 'State Team 州队', 1, 0),
        backup_team_count = backup_team_count - IF(OLD.status = 'Backup Team 后备队', 1, 0),
        student_count = student_count - IF(OLD.status = 'Student 学生', 1, 0),
        updated_at = CURRENT_TIMESTAMP
    WHERE year = reg_year AND month = reg_month;
END$$

DELIMITER ;

-- Sample query to verify setup
-- SELECT * FROM registrations;
-- SELECT * FROM monthly_stats ORDER BY year DESC, month DESC;