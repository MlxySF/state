-- Fix Database Column Types for Signature and Schedule
-- Run this SQL in your phpMyAdmin or MySQL console

-- First, let's check what the current column types are
-- SHOW COLUMNS FROM registrations;

-- Fix signature_base64 column - change to LONGTEXT to hold large base64 data
ALTER TABLE `registrations` 
MODIFY COLUMN `signature_base64` LONGTEXT NULL COMMENT 'Base64 encoded signature image';

-- Fix schedule column - change to TEXT to hold longer schedule descriptions
ALTER TABLE `registrations` 
MODIFY COLUMN `schedule` TEXT NULL COMMENT 'Training schedule details';

-- Also fix other base64 columns while we're at it
ALTER TABLE `registrations` 
MODIFY COLUMN `payment_receipt_base64` LONGTEXT NULL COMMENT 'Base64 encoded payment receipt';

ALTER TABLE `registrations` 
MODIFY COLUMN `signed_pdf_base64` LONGTEXT NULL COMMENT 'Base64 encoded signed PDF';

-- Fix events column to hold longer text
ALTER TABLE `registrations` 
MODIFY COLUMN `events` TEXT NULL COMMENT 'Selected events/classes';

-- Verify the changes
SHOW COLUMNS FROM registrations WHERE Field IN ('signature_base64', 'schedule', 'events', 'payment_receipt_base64', 'signed_pdf_base64');

-- Expected output:
-- signature_base64      | LONGTEXT
-- schedule              | TEXT
-- events                | TEXT  
-- payment_receipt_base64| LONGTEXT
-- signed_pdf_base64     | LONGTEXT