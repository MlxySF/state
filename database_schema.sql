-- Database schema for State Team Registration System
-- Simple registration storage without student portal functionality

CREATE TABLE IF NOT EXISTS `registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name_en` varchar(255) NOT NULL COMMENT 'English Name',
  `name_cn` varchar(255) DEFAULT NULL COMMENT 'Chinese Name (Optional)',
  `ic` varchar(20) NOT NULL COMMENT 'IC Number',
  `age` int(11) DEFAULT NULL COMMENT 'Age in 2026',
  `school` varchar(255) NOT NULL COMMENT 'School Name',
  `status` varchar(50) NOT NULL COMMENT 'State Team or Backup Team',
  `phone` varchar(20) NOT NULL COMMENT 'Contact Phone',
  `email` varchar(255) NOT NULL COMMENT 'Parent Email',
  `level` varchar(100) DEFAULT NULL COMMENT 'Training Level',
  `events` text COMMENT 'Selected Events (comma-separated)',
  `schedule` text COMMENT 'Selected Training Schedule (comma-separated)',
  `parent_name` varchar(255) NOT NULL COMMENT 'Parent/Guardian Name',
  `parent_ic` varchar(20) NOT NULL COMMENT 'Parent/Guardian IC',
  `form_date` varchar(50) DEFAULT NULL COMMENT 'Registration Date',
  `signature_base64` longtext COMMENT 'Parent Signature (Base64)',
  `pdf_base64` longtext COMMENT 'Signed PDF Agreement (Base64)',
  `raw_json` longtext COMMENT 'Complete JSON data backup',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_ic` (`ic`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='State Team Registrations 2026';

-- Example query to view all registrations
-- SELECT id, name_en, name_cn, ic, age, school, status, phone, email, created_at 
-- FROM registrations 
-- ORDER BY created_at DESC;

-- Example query to generate registration ID
-- SELECT CONCAT('STATE-2026-', LPAD(id, 4, '0')) as registration_id, name_en, email 
-- FROM registrations;
