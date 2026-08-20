-- Adds the reaudited counter, separate from audited, so a run's history
-- distinguishes fresh audits from periodic reaudits of closed/sent/blocked
-- prospects (see LeadEnginePipeline::stageReaudit()).
ALTER TABLE `lead_engine_runs`
    ADD COLUMN `reaudited` INT DEFAULT 0 AFTER `audited`;
