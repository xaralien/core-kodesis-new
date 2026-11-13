-- =====================================================================
--  ORIGIN & PREFIX WAREHOUSE pindah ke all_setting
--  (sebelumnya dibaca dari tabel utility lalu disimpan di session)
--
--    origin : kode bandara/warehouse, contoh HLP
--             dipakai di nomor invoice (HLP.OUT-JASTER-..., HLP.IN....),
--             cetakan CSD, BTB, invoice, dan halaman tracking
--    prefix : awalan nomor BTB, contoh HLPO -> HLPO-000123
--
--  Jalankan di database CORE BANK.
--  Jika nilai dibiarkan KOSONG, aplikasi tetap membaca kolom origin /
--  prefix dari tabel utility (Id = 1), jadi tidak ada yang rusak.
-- =====================================================================

INSERT INTO all_setting (kunci, nilai, keterangan) VALUES
('origin', '', 'Kode origin warehouse, contoh HLP. Kosong = ambil dari tabel utility'),
('prefix', '', 'Prefix nomor BTB, contoh HLPO. Kosong = ambil dari tabel utility')
ON DUPLICATE KEY UPDATE kunci = kunci;

-- ---------------------------------------------------------------------
-- Isi nilainya (pilih salah satu cara):
--
-- Cara 1: ketik langsung (sesuaikan dengan warehouse ini)
--   UPDATE all_setting SET nilai = 'HLP'  WHERE kunci = 'origin';
--   UPDATE all_setting SET nilai = 'HLPO' WHERE kunci = 'prefix';
--
-- Cara 2: salin dari tabel utility (jika satu server MySQL).
--   Ganti NAMA_DB_UTAMA dengan nama database utama aplikasi
--   (database yang berisi tabel users, memo, utility).
--   UPDATE all_setting SET nilai = (SELECT origin FROM NAMA_DB_UTAMA.utility WHERE Id = 1) WHERE kunci = 'origin';
--   UPDATE all_setting SET nilai = (SELECT prefix FROM NAMA_DB_UTAMA.utility WHERE Id = 1) WHERE kunci = 'prefix';
--
-- Cara 3: lewat menu Warehouse > Outgoing WH > Setting Tarif & COA (level 905)
-- ---------------------------------------------------------------------

SELECT kunci, nilai FROM all_setting WHERE kunci IN ('origin', 'prefix');
