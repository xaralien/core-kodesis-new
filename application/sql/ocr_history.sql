-- Tabel riwayat Sistem Tesseract OCR
CREATE TABLE IF NOT EXISTS `ocr_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nip` VARCHAR(50) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `lang` VARCHAR(20) NOT NULL DEFAULT 'ind+eng',
  `psm` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `engine` ENUM('server','browser') NOT NULL DEFAULT 'server',
  `result_text` MEDIUMTEXT,
  `confidence` DECIMAL(5,2) DEFAULT NULL,
  `duration` DECIMAL(8,2) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nip` (`nip`),
  KEY `idx_file_name` (`file_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
