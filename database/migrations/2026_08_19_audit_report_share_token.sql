-- Close report-link enumeration on /audit/pdf and /audit/download.
--
-- audit_reports.id is a sequential integer and both routes are unauthenticated,
-- so walking ids exposed every report ever run. Reports still need to be readable
-- from the link inside the report email — on a different device, outside the
-- session that produced them — so the link now carries a random token instead of
-- relying on the id being unguessable.

ALTER TABLE `audit_reports`
    ADD COLUMN `share_token` CHAR(64) NULL
        COMMENT 'Random token authorizing report links, NULL means session-only access'
        AFTER `status`,
    ADD UNIQUE INDEX `idx_audit_share_token` (`share_token`);

-- Give existing rows a token so links generated from now on work for them too.
-- Links already delivered cannot be retrofitted: they carry no token and will
-- only open for the browser that ran the scan.
UPDATE `audit_reports`
   SET `share_token` = SHA2(CONCAT(`id`, '-', RAND(), '-', UUID()), 256)
 WHERE `share_token` IS NULL;
