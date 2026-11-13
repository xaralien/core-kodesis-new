-- =====================================================================
--  PERBAIKAN: tambah uid + rapikan out_bank
--  Jalankan di database CORE BANK, SETELAH outgoing_ref_setting_upgrade.sql.
--  Jalankan per bagian. Jika satu baris ALTER error "Duplicate column"
--  atau "Duplicate key name", berarti sudah pernah dijalankan: lewati.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Tambah uid (AUTO_INCREMENT, PRIMARY KEY). Kunci lama tetap UNIQUE
--    sehingga tidak bisa dobel.
-- ---------------------------------------------------------------------
ALTER TABLE out_setting
  DROP PRIMARY KEY,
  ADD COLUMN uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
  ADD UNIQUE KEY uk_out_setting_kunci (kunci);

ALTER TABLE out_pay_methode
  DROP PRIMARY KEY,
  ADD COLUMN uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
  ADD UNIQUE KEY uk_out_pay_methode_kode (kode);

ALTER TABLE out_bank
  DROP PRIMARY KEY,
  ADD COLUMN uid INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST,
  ADD UNIQUE KEY uk_out_bank_coa (coa);

-- out_ref sudah punya uid sejak awal, tidak perlu diubah.

-- ---------------------------------------------------------------------
-- 2. out_bank: kolom urutan (jika belum ada) + perbaiki kolom hold.
--    Nilai 1/2/3 di hold sebenarnya nilai urutan yang salah kolom.
-- ---------------------------------------------------------------------
ALTER TABLE out_bank ADD COLUMN urutan INT NOT NULL DEFAULT 0 AFTER nama;

UPDATE out_bank SET urutan = 1, hold = '0' WHERE coa = '12001';
UPDATE out_bank SET urutan = 2, hold = '0' WHERE coa = '12002';
UPDATE out_bank SET urutan = 3, hold = '0' WHERE coa = '12004';

-- Kolom hold hanya boleh '0' (Ready) atau '1' (Hold)
UPDATE out_bank SET hold = '0' WHERE hold NOT IN ('0', '1') OR hold IS NULL;

-- ---------------------------------------------------------------------
-- 3. Cek hasil
-- ---------------------------------------------------------------------
SELECT * FROM out_bank ORDER BY urutan;
