-- ===================================================
-- LandingFlow - Complete Database Schema
-- Version: 1.0.0
-- Database: MySQL 8.0+
-- Charset: utf8mb4 (Hebrew support)
-- ===================================================

CREATE DATABASE IF NOT EXISTS `landingflow`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `landingflow`;

-- ===================================================
-- USERS & AUTHENTICATION
-- ===================================================

CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `permissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `group` VARCHAR(50) NULL COMMENT 'crm, projects, hosting, monitoring, admin',
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `role_permissions` (
    `role_id` INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NULL,
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT UNSIGNED NULL,
    `avatar` VARCHAR(255) NULL,
    `status` ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `email_verified_at` TIMESTAMP NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- LEADS (CRM)
-- ===================================================

CREATE TABLE `leads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(20) NULL,
    `company` VARCHAR(150) NULL,
    `website` VARCHAR(255) NULL,
    `source` ENUM('website', 'audit', 'referral', 'social', 'phone', 'email', 'other') DEFAULT 'website',
    `source_detail` VARCHAR(255) NULL COMMENT 'Specific source info',
    `status` ENUM('new', 'contacted', 'qualified', 'proposal_sent', 'negotiation', 'won', 'lost') DEFAULT 'new',
    `score` INT DEFAULT 0 COMMENT 'Lead quality score 0-100',
    `interest` VARCHAR(100) NULL COMMENT 'Service of interest',
    `budget` DECIMAL(10,2) NULL,
    `notes` TEXT NULL,
    `assigned_to` INT UNSIGNED NULL COMMENT 'Staff member assigned',
    `consent_given` TINYINT(1) DEFAULT 0,
    `consent_date` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_leads_status` (`status`),
    INDEX `idx_leads_source` (`source`),
    INDEX `idx_leads_assigned` (`assigned_to`),
    INDEX `idx_leads_created` (`created_at`),
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `lead_notes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `content` TEXT NOT NULL,
    `type` ENUM('note', 'call', 'email', 'meeting', 'whatsapp') DEFAULT 'note',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_leadnotes_lead` (`lead_id`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- AUDIT REPORTS
-- ===================================================

CREATE TABLE `audit_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NULL,
    `url` VARCHAR(500) NOT NULL,
    `overall_score` DECIMAL(5,2) NULL,
    `seo_score` DECIMAL(5,2) NULL,
    `security_score` DECIMAL(5,2) NULL,
    `legal_score` DECIMAL(5,2) NULL,
    `accessibility_score` DECIMAL(5,2) NULL,
    `performance_score` DECIMAL(5,2) NULL,
    `total_checks` INT DEFAULT 0,
    `passed_checks` INT DEFAULT 0,
    `failed_checks` INT DEFAULT 0,
    `full_report` JSON NULL COMMENT 'Complete audit results as JSON',
    `recommendations` JSON NULL COMMENT 'Array of recommendations',
    `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_url` (`url`(255)),
    INDEX `idx_audit_lead` (`lead_id`),
    INDEX `idx_audit_score` (`overall_score`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- WEBSITE PROJECTS
-- ===================================================

CREATE TABLE `website_projects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `type` ENUM('landing_page', 'business_site', 'ecommerce', 'custom') DEFAULT 'business_site',
    `package` ENUM('starter', 'business', 'premium') DEFAULT 'business',
    `url` VARCHAR(255) NULL,
    `staging_url` VARCHAR(255) NULL,
    `status` ENUM('new_request', 'in_review', 'in_development', 'testing', 'delivered', 'on_hold', 'cancelled') DEFAULT 'new_request',
    `progress` INT DEFAULT 0 COMMENT '0-100 percentage',
    `price` DECIMAL(10,2) NULL,
    `start_date` DATE NULL,
    `deadline` DATE NULL,
    `completed_at` TIMESTAMP NULL,
    `assigned_to` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `features` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_projects_status` (`status`),
    INDEX `idx_projects_type` (`type`),
    INDEX `idx_projects_lead` (`lead_id`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `project_updates` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `content` TEXT NOT NULL,
    `status_from` ENUM('new_request', 'in_review', 'in_development', 'testing', 'delivered', 'on_hold', 'cancelled') NULL,
    `status_to` ENUM('new_request', 'in_review', 'in_development', 'testing', 'delivered', 'on_hold', 'cancelled') NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_updates_project` (`project_id`),
    FOREIGN KEY (`project_id`) REFERENCES `website_projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- HOSTING ACCOUNTS
-- ===================================================

CREATE TABLE `hosting_accounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NULL,
    `domain` VARCHAR(255) NOT NULL,
    `hosting_plan` VARCHAR(100) NOT NULL,
    `hosting_provider` VARCHAR(100) DEFAULT 'Hostinger',
    `start_date` DATE NOT NULL,
    `expiration_date` DATE NOT NULL,
    `renewal_price` DECIMAL(10,2) NULL,
    `auto_renew` TINYINT(1) DEFAULT 0,
    `status` ENUM('active', 'expiring_soon', 'expired', 'suspended', 'cancelled') DEFAULT 'active',
    `control_panel_url` VARCHAR(255) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hosting_status` (`status`),
    INDEX `idx_hosting_expiry` (`expiration_date`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- MONITORING
-- ===================================================

CREATE TABLE `monitoring_websites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `hosting_account_id` INT UNSIGNED NULL,
    `lead_id` INT UNSIGNED NULL,
    `url` VARCHAR(500) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `check_interval` INT DEFAULT 5 COMMENT 'Minutes between checks',
    `status` ENUM('online', 'offline', 'issues', 'paused') DEFAULT 'online',
    `last_checked_at` TIMESTAMP NULL,
    `ssl_expires_at` DATE NULL,
    `ssl_status` ENUM('valid', 'expiring_soon', 'expired', 'not_configured') DEFAULT 'not_configured',
    `uptime_percentage` DECIMAL(5,2) DEFAULT 100.00,
    `response_time_ms` INT NULL,
    `alert_email` TINYINT(1) DEFAULT 1,
    `alert_whatsapp` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_monitor_status` (`status`),
    INDEX `idx_monitor_ssl` (`ssl_status`),
    FOREIGN KEY (`hosting_account_id`) REFERENCES `hosting_accounts`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE `monitoring_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `website_id` INT UNSIGNED NOT NULL,
    `status` ENUM('up', 'down') NOT NULL,
    `http_code` INT NULL,
    `response_time_ms` INT NULL,
    `error_message` VARCHAR(500) NULL,
    `checked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_logs_website` (`website_id`),
    INDEX `idx_logs_checked` (`checked_at`),
    FOREIGN KEY (`website_id`) REFERENCES `monitoring_websites`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `monitoring_alerts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `website_id` INT UNSIGNED NOT NULL,
    `type` ENUM('down', 'up', 'ssl_expiring', 'ssl_expired', 'slow_response') NOT NULL,
    `message` TEXT NOT NULL,
    `sent_email` TINYINT(1) DEFAULT 0,
    `sent_whatsapp` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_alerts_website` (`website_id`),
    FOREIGN KEY (`website_id`) REFERENCES `monitoring_websites`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================================================
-- USER CONSENTS (GDPR/Privacy Law Compliance)
-- ===================================================

CREATE TABLE `user_consents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lead_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) NULL,
    `consent_type` VARCHAR(100) NOT NULL COMMENT 'privacy_policy, terms_of_service, cookies, marketing',
    `consent_given` TINYINT(1) NOT NULL,
    `consent_text` TEXT NULL COMMENT 'Text version shown to user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_consents_lead` (`lead_id`),
    INDEX `idx_consents_type` (`consent_type`),
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- ACTIVITY LOGS (Audit Trail)
-- ===================================================

CREATE TABLE `activity_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NULL COMMENT 'lead, project, user, etc.',
    `entity_id` INT UNSIGNED NULL,
    `details` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activity_user` (`user_id`),
    INDEX `idx_activity_entity` (`entity_type`, `entity_id`),
    INDEX `idx_activity_created` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- BLOG / CONTENT
-- ===================================================

CREATE TABLE `blog_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `blog_posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id` INT UNSIGNED NULL,
    `category_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `excerpt` TEXT NULL,
    `content` LONGTEXT NOT NULL,
    `featured_image` VARCHAR(255) NULL,
    `meta_title` VARCHAR(255) NULL,
    `meta_description` TEXT NULL,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `is_featured` TINYINT(1) DEFAULT 0,
    `published_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_blog_slug` (`slug`),
    INDEX `idx_blog_category` (`category_id`),
    INDEX `idx_blog_status` (`status`),
    INDEX `idx_blog_published` (`published_at`),
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ===================================================
-- CONTACT / INQUIRIES
-- ===================================================

CREATE TABLE `contact_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL,
    `subject` VARCHAR(255) NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('unread', 'read', 'replied', 'archived') DEFAULT 'unread',
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contact_status` (`status`)
) ENGINE=InnoDB;

-- ===================================================
-- SETTINGS
-- ===================================================

CREATE TABLE `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `type` VARCHAR(20) DEFAULT 'string' COMMENT 'string, integer, boolean, json',
    `group` VARCHAR(50) NULL COMMENT 'general, seo, social, email',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_settings_key` (`key`),
    INDEX `idx_settings_group` (`group`)
) ENGINE=InnoDB;

-- ===================================================
-- PASSWORD RESET TOKENS
-- ===================================================

CREATE TABLE `password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    INDEX `idx_resets_email` (`email`),
    INDEX `idx_resets_token` (`token`)
) ENGINE=InnoDB;

-- ===================================================
-- EMAIL VERIFICATION TOKENS
-- ===================================================

CREATE TABLE `email_verification_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `used` TINYINT(1) DEFAULT 0,
    INDEX `idx_verification_token` (`token`),
    INDEX `idx_verification_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================================================
-- API TOKENS (for future API)
-- ===================================================

CREATE TABLE `api_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `token` VARCHAR(100) NOT NULL UNIQUE,
    `last_used_at` TIMESTAMP NULL,
    `expires_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_api_user` (`user_id`),
    INDEX `idx_api_token` (`token`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ===================================================
-- RECEIPTS
-- ===================================================

CREATE TABLE `receipts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_number` VARCHAR(20) NOT NULL UNIQUE,
    `customer_name` VARCHAR(255) NOT NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `transaction_id` VARCHAR(100) DEFAULT NULL,
    `service_description` TEXT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `receipt_date` DATE NOT NULL,
    `pdf_path` VARCHAR(500) DEFAULT NULL,
    `emailed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- DEFAULT DATA
-- ===================================================

-- Insert default roles
INSERT INTO `roles` (`name`, `slug`, `description`) VALUES
('מנהל מערכת', 'admin', 'גישה מלאה לכל המערכת'),
('צוות', 'staff', 'גישה לניהול לידים, פרויקטים ו-CRM'),
('לקוח', 'client', 'גישה לצפייה בפרויקטים אישיים');

-- Insert default permissions
INSERT INTO `permissions` (`name`, `slug`, `group`) VALUES
('צפייה בלוח בקרה', 'dashboard.view', 'admin'),
('ניהול משתמשים', 'users.manage', 'admin'),
('ניהול לידים', 'leads.manage', 'crm'),
('צפייה בלידים', 'leads.view', 'crm'),
('ניהול פרויקטים', 'projects.manage', 'projects'),
('צפייה בפרויקטים', 'projects.view', 'projects'),
('ניהול אחסון', 'hosting.manage', 'hosting'),
('צפייה באחסון', 'hosting.view', 'hosting'),
('ניהול ניטור', 'monitoring.manage', 'monitoring'),
('צפייה בניטור', 'monitoring.view', 'monitoring'),
('ניהול ביקורת אתרים', 'audit.manage', 'admin'),
('צפייה בביקורות', 'audit.view', 'admin'),
('ניהול בלוג', 'blog.manage', 'admin'),
('ניהול הגדרות', 'settings.manage', 'admin');

-- Assign all permissions to admin role
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Assign CRM and project view permissions to staff
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `slug` IN ('dashboard.view', 'leads.view', 'leads.manage', 'projects.view', 'hosting.view', 'monitoring.view', 'audit.view');

-- Insert default admin user (password: Admin123!)
-- Password hash for 'Admin123!'
INSERT INTO `users` (`name`, `email`, `password`, `role_id`, `status`) VALUES
('מנהל מערכת', 'admin@landingflow.co.il', '$2y$12$LJ3m4ys3Gql.ZhF0qLkiKeL0BMFFz9k7hB3oCEfFaPUUZQy0V2oOe', 1, 'active');

-- Insert default settings
INSERT INTO `settings` (`key`, `value`, `type`, `group`) VALUES
('site_name', 'LandingFlow', 'string', 'general'),
('site_description', 'פיתוח אתרים, דפי נחיתה, אחסון, ניטור וניהול לידים במקום אחד', 'string', 'general'),
('whatsapp_number', '972528529448', 'string', 'social'),
('contact_email', 'info@landingflow.co.il', 'string', 'general'),
('contact_phone', '052-8529448', 'string', 'general'),
('meta_keywords', 'פיתוח אתרים, דפי נחיתה, אחסון אתרים, ניטור אתרים', 'string', 'seo'),
('google_analytics', '', 'string', 'seo'),
('facebook_pixel', '', 'string', 'seo');
