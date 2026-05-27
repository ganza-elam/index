-- Correct Report: pastor mapato + bank slips
USE elam_system;

CREATE TABLE IF NOT EXISTS `mapato_pastor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intara_id` int(11) NOT NULL,
  `month` tinyint unsigned NOT NULL,
  `icyacumi` varchar(500) DEFAULT NULL,
  `icyacumi_cya_cms` varchar(500) DEFAULT NULL,
  `amaturo` varchar(500) DEFAULT NULL,
  `amaturo_bya_cms` varchar(500) DEFAULT NULL,
  `revival` varchar(500) DEFAULT NULL,
  `ss` varchar(500) DEFAULT NULL,
  `filide` varchar(500) DEFAULT NULL,
  `umusaruro` varchar(500) DEFAULT NULL,
  `ituro` varchar(500) DEFAULT NULL,
  `total` decimal(15,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_mapato_pastor_intara` (`intara_id`),
  KEY `idx_mapato_pastor_month` (`month`),
  CONSTRAINT `fk_mapato_pastor_intara` FOREIGN KEY (`intara_id`) REFERENCES `intara` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `bank_slips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intara_id` int(11) NOT NULL,
  `slip_number` varchar(100) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bank_slip_number` (`slip_number`),
  KEY `idx_bank_slips_intara` (`intara_id`),
  CONSTRAINT `fk_bank_slips_intara` FOREIGN KEY (`intara_id`) REFERENCES `intara` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
