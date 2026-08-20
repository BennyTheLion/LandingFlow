-- ===================================================
-- LandingFlow Lead Engine — schema (docs/lead-engine-spec.md §4)
-- Version: 1.0.0
-- Database: MySQL 8.0+
-- Charset: utf8mb4 (Hebrew support)
--
-- Run once:  mysql -u root landingflow < database/migrations/2026_08_19_lead_engine.sql
--
-- Deviation from spec §4: integer surrogate keys instead of uuid, to match
-- every other table in database/schema.sql and keep FKs consistent.
-- ===================================================

-- ---------------------------------------------------
-- PROSPECTS — one row per business, deduplicated by domain
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `prospects` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `business_name` VARCHAR(200) NOT NULL,
    `domain` VARCHAR(190) NOT NULL COMMENT 'Deduplication key — host without www',
    `url` VARCHAR(500) NOT NULL,
    `niche` VARCHAR(50) NULL COMMENT 'dental_clinic, law_firm, aesthetics, contractor, ...',
    `city` VARCHAR(100) NULL,
    `source` ENUM('google_places', 'meta_ads', 'manual', 'referral', 'csv') DEFAULT 'manual',
    `source_ref` VARCHAR(255) NULL COMMENT 'place_id or other source identifier',
    `phone` VARCHAR(40) NULL,
    `email` VARCHAR(255) NULL,
    `contact_name` VARCHAR(100) NULL COMMENT 'Owner first name — critical for reply rate',
    `contact_role` VARCHAR(100) NULL,
    `spends_on_ads` TINYINT(1) DEFAULT 0 COMMENT 'Hot-lead flag (§6 score bonus)',
    `broken_form` TINYINT(1) DEFAULT 0 COMMENT 'Manually verified by a human — never automated (§11.5)',
    -- 'blocked' = the homepage could not be audited because a WAF/CDN refused our
    -- identified crawler (401/403/429) or robots.txt disallows it. Distinct from
    -- 'closed' on purpose: the business is real and may still be worth a manual
    -- look, so it must not be silently discarded.
    `status` ENUM('new','audited','enriched','drafted','approved','sent','replied','closed','blocked','rejected','do_not_contact') DEFAULT 'new',
    `hot_score` INT NULL COMMENT 'Latest audit score, denormalized for sorting',
    `primary_issue` VARCHAR(40) NULL COMMENT 'Latest audit primary issue, denormalized',
    `last_audit_at` TIMESTAMP NULL,
    `crm_lead_id` INT UNSIGNED NULL COMMENT 'Set when a reply promotes this prospect into the CRM',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_prospects_domain` (`domain`),
    INDEX `idx_prospects_status` (`status`),
    INDEX `idx_prospects_score` (`hot_score`),
    INDEX `idx_prospects_niche` (`niche`),
    INDEX `idx_prospects_source` (`source`),
    INDEX `idx_prospects_created` (`created_at`),
    FOREIGN KEY (`crm_lead_id`) REFERENCES `leads`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- PROSPECT_AUDITS — audit history; keeping every run is a sales asset (§10)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `prospect_audits` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `prospect_id` INT UNSIGNED NOT NULL,
    `run_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `perf_mobile` TINYINT UNSIGNED NULL COMMENT 'PageSpeed mobile 0-100',
    `perf_desktop` TINYINT UNSIGNED NULL,
    `seo_score` TINYINT UNSIGNED NULL,
    `a11y_score` TINYINT UNSIGNED NULL,
    `security_score` TINYINT UNSIGNED NULL,
    `has_ssl` TINYINT(1) DEFAULT 0,
    `has_accessibility_statement` TINYINT(1) DEFAULT 0,
    `has_analytics` TINYINT(1) DEFAULT 0 COMMENT 'gtag / _ga / GTM',
    `has_meta_pixel` TINYINT(1) DEFAULT 0 COMMENT 'fbq',
    `has_click_to_call` TINYINT(1) DEFAULT 0 COMMENT 'tel: on the homepage',
    `mobile_viewport_ok` TINYINT(1) DEFAULT 0,
    `contact_form_found` TINYINT(1) DEFAULT 0,
    `hot_score` TINYINT UNSIGNED NULL COMMENT '0-100, see §6',
    `primary_issue` ENUM('broken_form','no_analytics_with_ads','slow_mobile','no_accessibility','no_click_to_call','weak_seo','none') NULL,
    `perf_source` ENUM('pagespeed','heuristic') DEFAULT 'heuristic' COMMENT 'Where perf_mobile came from',
    `fetch_ok` TINYINT(1) DEFAULT 1 COMMENT '0 when the homepage could not be fetched',
    `raw_json` JSON NULL COMMENT 'Full engine output, retained',
    INDEX `idx_paudits_prospect` (`prospect_id`),
    INDEX `idx_paudits_run` (`run_at`),
    INDEX `idx_paudits_score` (`hot_score`),
    FOREIGN KEY (`prospect_id`) REFERENCES `prospects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- OUTREACH_DRAFTS — nothing here sends itself (§1, §9)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `outreach_drafts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `prospect_id` INT UNSIGNED NOT NULL,
    `audit_id` INT UNSIGNED NULL,
    `channel` ENUM('email', 'whatsapp') DEFAULT 'email',
    `subject` VARCHAR(255) NULL,
    `body` TEXT NULL COMMENT 'LLM draft, editable',
    `video_brief` TEXT NULL COMMENT 'Recording script (§8)',
    `video_url` VARCHAR(500) NULL COMMENT 'Filled in by hand after recording — required to approve',
    `status` ENUM('draft', 'approved', 'sent', 'rejected', 'expired') DEFAULT 'draft',
    `approval_token` CHAR(64) NULL COMMENT 'HMAC of the token payload — never the raw token',
    `token_expires_at` TIMESTAMP NULL COMMENT '72h from issue (§9)',
    `token_used_at` TIMESTAMP NULL COMMENT 'Single use',
    `followup_of` INT UNSIGNED NULL COMMENT 'Parent draft when this is a follow-up',
    `followup_step` TINYINT UNSIGNED DEFAULT 0 COMMENT '0=first touch, 1..3 = day 3/7/14',
    `generated_by` ENUM('llm', 'template') DEFAULT 'template',
    `rejected_reason` VARCHAR(255) NULL,
    `approved_at` TIMESTAMP NULL,
    `sent_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_drafts_prospect` (`prospect_id`),
    INDEX `idx_drafts_status` (`status`),
    INDEX `idx_drafts_token` (`approval_token`),
    INDEX `idx_drafts_sent` (`sent_at`),
    FOREIGN KEY (`prospect_id`) REFERENCES `prospects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`audit_id`) REFERENCES `prospect_audits`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`followup_of`) REFERENCES `outreach_drafts`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- OUTREACH_EVENTS — timeline + follow-up tasks
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `outreach_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `draft_id` INT UNSIGNED NOT NULL,
    `type` ENUM('sent','opened','clicked','replied','followup_1','followup_2','followup_3','cancelled') NOT NULL,
    `at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `meta_json` JSON NULL,
    INDEX `idx_events_draft` (`draft_id`),
    INDEX `idx_events_type` (`type`),
    FOREIGN KEY (`draft_id`) REFERENCES `outreach_drafts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- DO_NOT_CONTACT — checked before every send, no exceptions (§4, §11.3)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `do_not_contact` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `domain` VARCHAR(190) NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(40) NULL,
    `reason` VARCHAR(255) NULL,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_dnc_domain` (`domain`),
    INDEX `idx_dnc_email` (`email`),
    INDEX `idx_dnc_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- LEAD_ENGINE_RUNS — pipeline run log (§10 "הרצות")
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_engine_runs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `trigger` ENUM('cron', 'manual') DEFAULT 'manual',
    `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `finished_at` TIMESTAMP NULL,
    `sourced` INT DEFAULT 0,
    `skipped_duplicate` INT DEFAULT 0,
    `skipped_dnc` INT DEFAULT 0,
    `audited` INT DEFAULT 0,
    `below_threshold` INT DEFAULT 0,
    `enriched` INT DEFAULT 0,
    `drafted` INT DEFAULT 0,
    `errors` INT DEFAULT 0,
    `log_json` JSON NULL COMMENT 'Per-stage detail: what was skipped and why',
    INDEX `idx_runs_started` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- LEAD_ENGINE_SETTINGS — runtime knobs, overrides env defaults (§10 "הגדרות")
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `lead_engine_settings` (
    `key` VARCHAR(60) NOT NULL PRIMARY KEY,
    `value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seeded OFF: these rows override the env constants, so a freshly migrated
-- install crawls nothing and sends nothing until someone enables it from
-- /admin/lead-engine/settings.
INSERT INTO `lead_engine_settings` (`key`, `value`) VALUES
    ('hot_score_threshold', '55'),
    ('max_daily_sends', '8'),
    ('min_minutes_between_sends', '5'),
    ('send_window_start', '09:00'),
    ('send_window_end', '18:00'),
    ('pipeline_enabled', '0'),
    ('sending_halted', '1'),
    ('active_niches', 'dental_clinic,law_firm,aesthetics,contractor'),
    ('active_cities', 'תל אביב,ירושלים,חיפה,באר שבע'),
    ('closed_retention_months', '12')
ON DUPLICATE KEY UPDATE `key` = `key`;
