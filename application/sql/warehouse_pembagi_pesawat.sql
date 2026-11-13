-- =====================================================================
--  PEMBAGI VOLUME PER MASKAPAI (Kemasan SMU Outgoing)
--  Volume = P x L x T (cm) / pembagi
--
--  Untuk database yang SUDAH menjalankan SQL warehouse sebelumnya.
--  Jalankan di database CORE BANK. Jika ALTER error "Duplicate column
--  name", kolom sudah ada: lanjutkan baris berikutnya.
--
--  Nilai awal sama dengan perilaku lama: BATIK 5000, lainnya 6000.
-- =====================================================================

-- Pembagi bawaan untuk maskapai yang belum diatur
INSERT INTO all_setting (kunci, nilai, keterangan) VALUES
('pembagi_default', '6000', 'Pembagi volume bawaan jika maskapai belum punya pembagi (P x L x T / pembagi)')
ON DUPLICATE KEY UPDATE kunci = kunci;

-- Kolom pembagi per maskapai (NULL = pakai pembagi_default)
ALTER TABLE out_pesawat ADD COLUMN pembagi INT NULL;

UPDATE out_pesawat SET pembagi = 5000 WHERE UPPER(TRIM(nama)) = 'BATIK' AND pembagi IS NULL;

-- Cek hasil
SELECT nama, pembagi FROM out_pesawat ORDER BY nama;
