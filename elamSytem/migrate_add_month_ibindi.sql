-- Run once on existing MySQL/MariaDB databases (adds ukwezi + ibindi columns).
ALTER TABLE `imibare`
  ADD COLUMN `month` tinyint unsigned DEFAULT NULL AFTER `itorero_id`,
  ADD COLUMN `ibindi` varchar(1000) DEFAULT NULL AFTER `month`;
