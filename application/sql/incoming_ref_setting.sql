-- =====================================================================
--  Referensi & setting modul INCOMING (controller Incominghlp)
--  Jalankan di database CORE BANK, SETELAH SQL outgoing
--  (warehouse_ref_setting*.sql dan warehouse_ref_uid_fix.sql), karena
--  incoming memakai tabel yang sama: all_setting, all_ref,
--  all_pay_methode, all_bank.
--
--  Nilai awal = nilai hardcode lama, jadi perhitungan tidak berubah.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. Setting khusus incoming (kunci in_*). Tidak menimpa nilai yang
--    sudah diubah.
--    Setting umum dipakai bersama outgoing: ppn_rate, min_sewa_gudang,
--    biaya_adm_1 (20.000), biaya_adm_0 (3.000), cdc_rate, coa_piutang,
--    limit_saldo_deposit.
-- ---------------------------------------------------------------------
INSERT INTO all_setting (kunci, nilai, keterangan) VALUES
('in_surcharge_dg_rate',        '600',         'Incoming: surcharge Dangerous Goods per kg chargeable'),
('in_coa_pendapatan_default',   '41003',       'Incoming: COA pendapatan jika maskapai belum punya coa_pendapatan'),
('in_kode_invoice',             '.IN.',        'Incoming: sisipan nomor invoice: <origin><in_kode_invoice><bulan tahun><nomor>'),
('in_prefix_sj',                'HPLI-',       'Incoming: prefix nomor surat jalan di cetakan invoice'),
('in_coa_list_debit_depo',      '210',         'Incoming daftar invoice: COA debit deposit'),
('in_coa_list_kredit_depo',     '41003',       'Incoming daftar invoice: COA kredit deposit'),
('in_coa_list_debit_transfer',  '12002,12001', 'Incoming daftar invoice: COA debit transfer'),
('in_coa_list_kredit_transfer', '41003',       'Incoming daftar invoice: COA kredit transfer')
ON DUPLICATE KEY UPDATE kunci = kunci;

-- ---------------------------------------------------------------------
-- 2. COA pendapatan per maskapai incoming.
--    Jika ALTER error "Duplicate column name", kolom sudah ada: lewati.
--    Nama dicocokkan tanpa membedakan huruf besar/kecil.
-- ---------------------------------------------------------------------
ALTER TABLE in_pesawat ADD COLUMN coa_pendapatan VARCHAR(20) NULL;

UPDATE in_pesawat SET coa_pendapatan = '41011' WHERE UPPER(TRIM(nama)) = 'BATIK'    AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE in_pesawat SET coa_pendapatan = '41009' WHERE UPPER(TRIM(nama)) = 'CITILINK' AND (coa_pendapatan IS NULL OR coa_pendapatan = '');
UPDATE in_pesawat SET coa_pendapatan = '41014' WHERE UPPER(TRIM(nama)) = 'FLYJAYA'  AND (coa_pendapatan IS NULL OR coa_pendapatan = '');

-- ---------------------------------------------------------------------
-- 3. Cek: maskapai yang belum punya COA akan memakai 41003
-- ---------------------------------------------------------------------
SELECT uid, nama, coa_pendapatan FROM in_pesawat ORDER BY nama;
