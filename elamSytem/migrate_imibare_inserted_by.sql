-- Track which admin inserted each Insert Data (imibare) record
ALTER TABLE imibare
  ADD COLUMN IF NOT EXISTS inserted_by int(11) DEFAULT NULL AFTER total;

-- MySQL < 8.0: run manually if IF NOT EXISTS fails:
-- ALTER TABLE imibare ADD COLUMN inserted_by int(11) DEFAULT NULL AFTER total;
-- ALTER TABLE imibare ADD KEY idx_imibare_inserted_by (inserted_by);
