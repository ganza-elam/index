-- Correct Report v2: meeting column, bank_slips.month, no amaturo CFMS in new data
USE elam_system;

ALTER TABLE mapato_pastor
  ADD COLUMN IF NOT EXISTS meeting varchar(500) DEFAULT NULL AFTER icyacumi;

UPDATE mapato_pastor
SET meeting = icyacumi_cya_cms
WHERE (meeting IS NULL OR meeting = '') AND icyacumi_cya_cms IS NOT NULL AND icyacumi_cya_cms != '';

ALTER TABLE bank_slips
  ADD COLUMN IF NOT EXISTS month tinyint unsigned DEFAULT NULL AFTER intara_id;
