-- schema.sql

CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(36) PRIMARY KEY,
    `firebase_uid` VARCHAR(128) UNIQUE NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `display_name` VARCHAR(255),
    `role` ENUM('officer', 'admin', 'public') NOT NULL DEFAULT 'public',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `documents` (
    `id` VARCHAR(36) PRIMARY KEY,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL UNIQUE,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT NOT NULL,
    `uploader_uid` VARCHAR(36) NOT NULL,
    `status` ENUM('UPLOADED', 'PROCESSING', 'EXTRACTED', 'REVIEW REQUIRED', 'UNDER REVIEW', 'CORRECTION REQUIRED', 'VERIFIED', 'FAILED') NOT NULL DEFAULT 'UPLOADED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`uploader_uid`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `extracted_fields` (
    `id` VARCHAR(36) PRIMARY KEY,
    `document_id` VARCHAR(36) NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `ai_value` TEXT,
    `confidence` DECIMAL(5,2),
    `source_page` INT,
    `status` ENUM('pending', 'review_required', 'corrected', 'verified') NOT NULL DEFAULT 'pending',
    `officer_value` TEXT,
    `final_value` TEXT,
    FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `validation_issues` (
    `id` VARCHAR(36) PRIMARY KEY,
    `document_id` VARCHAR(36) NOT NULL,
    `field_name` VARCHAR(100) NOT NULL,
    `reason` TEXT NOT NULL,
    `severity` ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL,
    `status` ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
    FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` VARCHAR(36) PRIMARY KEY,
    `recipient_uid` VARCHAR(36) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `target_id` VARCHAR(36),
    `severity` ENUM('info', 'warning', 'error', 'success') NOT NULL DEFAULT 'info',
    `is_read` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`recipient_uid`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` VARCHAR(36) PRIMARY KEY,
    `actor_uid` VARCHAR(36) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) NOT NULL,
    `target_id` VARCHAR(36) NOT NULL,
    `old_value` TEXT,
    `new_value` TEXT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `verification_records` (
    `id` VARCHAR(36) PRIMARY KEY,
    `document_id` VARCHAR(36) NOT NULL UNIQUE,
    `verification_id` VARCHAR(100) NOT NULL UNIQUE,
    `officer_uid` VARCHAR(36) NOT NULL,
    `verified_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_uid`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `digitization_requests` (
    `id` VARCHAR(36) PRIMARY KEY,
    `request_id` VARCHAR(100) NOT NULL UNIQUE,
    `applicant_name` VARCHAR(255) NOT NULL,
    `contact_info` VARCHAR(255),
    `village` VARCHAR(100),
    `taluk` VARCHAR(100),
    `district` VARCHAR(100),
    `document_type` VARCHAR(100),
    `survey_number` VARCHAR(100),
    `description` TEXT,
    `document_id` VARCHAR(36) NOT NULL,
    `uploader_uid` VARCHAR(36) NOT NULL,
    `status` ENUM('SUBMITTED', 'RECEIVED', 'PROCESSING', 'UNDER REVIEW', 'CORRECTION REQUIRED', 'VERIFIED', 'COMPLETED', 'REJECTED') NOT NULL DEFAULT 'SUBMITTED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`document_id`) REFERENCES `documents`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploader_uid`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
